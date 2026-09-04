<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoIpUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geoip:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download the latest free DB-IP Lite country MMDB files (CC BY 4.0)';

    private const BASE_URL = 'https://github.com/sapics/ip-location-db/releases/latest/download/';

    private const FILES = [
        'dbip-country-ipv4.mmdb',
        'dbip-country-ipv6.mmdb',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dir = storage_path('geoip');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach (self::FILES as $file) {
            $url = self::BASE_URL.$file;
            $target = $dir.DIRECTORY_SEPARATOR.$file;

            $this->info("Downloading {$file} ...");

            try {
                $response = Http::timeout(120)->connectTimeout(30)
                    ->withOptions(['stream' => true])
                    ->get($url);

                if ($response->failed()) {
                    $this->error("Failed to download {$file}: HTTP {$response->status()}");

                    return self::FAILURE;
                }

                $size = (int) $response->header('Content-Length', 0);
                $this->info("Response received ({$this->formatBytes($size)}). Writing to disk ...");

                $handle = fopen($target, 'wb');
                $stream = $response->toPsrResponse()->getBody();
                $total = 0;

                while (! $stream->eof()) {
                    $chunk = $stream->read(8192);
                    fwrite($handle, $chunk);
                    $total += strlen($chunk);
                }

                fclose($handle);

                $this->info("Saved {$file} ({$this->formatBytes($total)}).");
            } catch (\Throwable $e) {
                $this->error("Failed to download {$file}: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        Cache::increment('geoip:cache_version');

        $this->info('Geo-IP database updated. Cached country lookups will refresh on next request.');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}