<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Device;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;
use Illuminate\Support\Str;

class FetchPremiumProxies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxies:fetch-premium';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically fetch fresh Elite proxies and push to all devices.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("--- Premium Proxy Automation Started ---");
        
        // 1. Fetch from Geonode (High-quality Elite proxies)
        $this->comment("Fetching fresh IPs from Premium API...");
        $response = Http::timeout(15)->get('https://proxylist.geonode.com/api/proxy-list', [
            'limit' => 50,
            'page' => 1,
            'sort_by' => 'lastChecked',
            'sort_type' => 'desc',
            'protocols' => 'http,https',
            'anonymityLevel' => 'elite,anonymous',
        ]);

        if (!$response->successful()) {
            $this->error("Failed to connect to the Proxy API.");
            Log::error("FetchPremiumProxies: API failure.");
            return 1;
        }

        $proxies = $response->json()['data'] ?? [];
        if (empty($proxies)) {
            $this->warn("No Elite IPs available at the moment.");
            return 0;
        }

        $this->info("Captured " . count($proxies) . " fresh Premium IPs.");

        // 2. Distribute to devices
        $devices = Device::whereNotNull('fcm_token')->get();
        if ($devices->isEmpty()) {
            $this->warn("No active devices found to update.");
            return 0;
        }

        $messaging = $this->getMessaging();
        $successCount = 0;

        foreach ($devices as $index => $device) {
            // Pick a proxy (unique if possible, otherwise rotate)
            $proxyData = $proxies[$index % count($proxies)];
            $proxyUrl = "http://{$proxyData['ip']}:{$proxyData['port']}";

            // Update database
            $device->update(['proxy_url' => $proxyUrl]);

            // Push to device via FCM
            if ($messaging) {
                try {
                    $message = CloudMessage::withTarget('token', $device->fcm_token)
                        ->withData([
                            'command'    => 'config_update',
                            'proxy_url'  => (string)$proxyUrl,
                            'timestamp'  => (string)time(),
                            'command_id' => Str::uuid()->toString(),
                            'label'      => 'Premium IP Rotation'
                        ])
                        ->withAndroidConfig(['priority' => 'high']);

                    $messaging->send($message);
                    $this->line("  ✓ Device [{$device->name}]: Updated to {$proxyUrl}");
                    $successCount++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Device [{$device->name}]: FCM push failed — " . $e->getMessage());
                }
            } else {
                $this->line("  ✓ Device [{$device->name}]: DB updated (FCM skipped — missing config)");
            }
        }

        $this->info("--- Premium Rotation Complete: {$successCount} devices updated ---");
        Log::info("FetchPremiumProxies: Rotation complete. {$successCount} devices updated.");
        
        return 0;
    }

    /**
     * Initialize Firebase Messaging.
     */
    private function getMessaging()
    {
        try {
            return app(\App\Services\FirebaseMessagingService::class)->messaging();
        } catch (\Exception $e) {
            return null;
        }
    }
}
