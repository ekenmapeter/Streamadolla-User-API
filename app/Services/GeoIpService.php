<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use MaxMind\Db\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Throwable;

class GeoIpService
{
    private ?Reader $readerV4 = null;

    private ?Reader $readerV6 = null;

    /**
     * Resolve the ISO 3166-1 alpha-2 country code for an IP address using
     * the offline DB-IP Lite MMDB database. Returns null for private,
     * reserved or unresolvable addresses (callers fall back to the default
     * reward rate). Results are cached per-IP to avoid repeated disk reads.
     */
    public function countryCode(?string $ip): ?string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        $isV6 = str_contains($ip, ':');

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        return Cache::remember($this->cacheKey($ip), now()->addHours(24), function () use ($ip, $isV6) {
            return $this->lookup($ip, $isV6);
        });
    }

    private function cacheKey(string $ip): string
    {
        $version = (int) Cache::get('geoip:cache_version', 1);

        return "geoip:country:v{$version}:{$ip}";
    }

    private function lookup(string $ip, bool $isV6): ?string
    {
        try {
            $reader = $isV6 ? $this->readerV6() : $this->readerV4();

            if (! $reader) {
                return null;
            }

            $record = $reader->get($ip);

            if (! is_array($record)) {
                return null;
            }

            $code = data_get($record, 'country_code')
                ?? data_get($record, 'country.iso_code')
                ?? data_get($record, 'registered_country.iso_code');

            return is_string($code) && strlen($code) === 2 ? strtoupper($code) : null;
        } catch (Throwable $e) {
            Log::warning('Geo-IP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function readerV4(): ?Reader
    {
        if ($this->readerV4 !== null) {
            return $this->readerV4;
        }

        return $this->readerV4 = $this->open(storage_path('geoip/dbip-country-ipv4.mmdb'));
    }

    private function readerV6(): ?Reader
    {
        if ($this->readerV6 !== null) {
            return $this->readerV6;
        }

        return $this->readerV6 = $this->open(storage_path('geoip/dbip-country-ipv6.mmdb'));
    }

    private function open(string $path): ?Reader
    {
        if (! is_file($path)) {
            Log::warning('Geo-IP database file missing. Run "php artisan geoip:update".', ['path' => $path]);

            return null;
        }

        try {
            return new Reader($path);
        } catch (InvalidDatabaseException $e) {
            Log::warning('Geo-IP database invalid. Run "php artisan geoip:update".', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}