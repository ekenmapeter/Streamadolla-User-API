<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaystackService
{
    private string $secretKey;

    private string $baseUrl = 'https://api.paystack.co';

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?? (string) config('services.paystack.secret_key');
    }

    public function initialize(array $params): array
    {
        $response = Http::withToken($this->secretKey)
            ->acceptJson()
            ->post($this->baseUrl . '/transaction/initialize', $params);

        if ($response->failed()) {
            Log::error('Paystack initialize failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Unable to initialize payment with Paystack.');
        }

        return $response->json('data', []);
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->acceptJson()
            ->get($this->baseUrl . '/transaction/verify/' . rawurlencode($reference));

        if ($response->failed()) {
            Log::error('Paystack verify failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Unable to verify transaction with Paystack.');
        }

        return $response->json('data', []);
    }

    public function verifyWebhookSignature(string $signature, string $payload): bool
    {
        $secret = config('services.paystack.webhook_secret', '');

        if ($secret === '') {
            return true;
        }

        $expected = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}