<?php

namespace App\Services;

use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use RuntimeException;

class FirebaseMessagingService
{
    private Messaging $messaging;

    public function __construct()
    {
        $this->messaging = $this->resolveMessaging();
    }

    private function resolveMessaging(): Messaging
    {
        $credentialsPath = base_path(config('firebase.credentials'));

        if (! file_exists($credentialsPath)) {
            $storagePath = storage_path('app');
            foreach (glob($storagePath . '/*.json') ?: [] as $file) {
                if (str_contains($file, 'firebase-adminsdk')) {
                    $credentialsPath = $file;
                    break;
                }
            }
        }

        if (! file_exists($credentialsPath)) {
            throw new \RuntimeException("Firebase credentials file not found.");
        }

        return (new Factory)->withServiceAccount($credentialsPath)->createMessaging();
    }

    public function messaging(): Messaging
    {
        return $this->messaging;
    }

    public function platformCommand(string $platform): string
    {
        return match ($platform) {
            'spotify' => 'play_spotify',
            'apple_music' => 'play_applemusic',
            'tidal' => 'play_tidal',
            'iheart', 'iheartradio' => 'play_iheart',
            'audiomack' => 'play_audiomack',
            default => 'play_youtube',
        };
    }

    public function buildMessage(array $data, array $options = []): CloudMessage
    {
        $message = CloudMessage::withTarget('token', $options['token'])
            ->withData(array_merge([
                'timestamp' => (string) now()->timestamp,
                'command_id' => Str::uuid()->toString(),
            ], $data));

        $message = $message->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s']);

        if ($options['apns'] ?? true) {
            $message = $message->withApnsConfig([
                'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                'payload' => ['aps' => ['content-available' => 1]],
            ]);
        }

        return $message;
    }

    public function sendToToken(string $token, array $data, array $options = []): bool
    {
        try {
            $this->messaging->send($this->buildMessage($data, array_merge($options, ['token' => $token])));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function sendToDevice(array $data, ?string $fcmToken, array $options = []): bool
    {
        if (! $fcmToken) {
            return false;
        }

        return $this->sendToToken($fcmToken, $data, $options);
    }

    public function sendMulticast(array $tokens, array $data): array
    {
        $results = ['successful' => 0, 'failed' => 0, 'errors' => []];

        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $data)) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    public function notifToToken(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $notification = \Kreait\Firebase\Messaging\Notification::create($title, $body);
            $message = $this->buildMessage($data, ['token' => $token])
                ->withNotification($notification);

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}