<?php

namespace App\Support;

use App\Models\AppSetting;

class ApkPublish
{
    /**
     * Locates the newest published APK in the given public folder.
     *
     * @return array{filename: string, url: string, version: string, size: int, sha256: string, released_at: string}|null
     */
    public static function latest(string $folder = 'download', string $versionSetting = 'app_version'): ?array
    {
        $folder = trim($folder, '/');
        $files = glob(public_path($folder . '/*.apk')) ?: [];

        if (empty($files)) {
            return null;
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        $file = $files[0];
        $filename = basename($file);

        $version = '';

        // The version baked into the filename (e.g. app-v1.2.0.apk) is the
        // source of truth — the file itself may outpace the app_version
        // setting if a deploy skipped the settings update.
        if (preg_match('/v?(\d+\.\d+\.\d+)/', $filename, $m)) {
            $version = $m[1];
        }
        if ($version === '') {
            $version = (string) (AppSetting::get($versionSetting) ?? '1.0.0');
        }

        return [
            'filename' => $filename,
            'url' => asset($folder . '/' . $filename),
            'version' => $version,
            'size' => (int) filesize($file),
            'sha256' => hash_file('sha256', $file),
            'released_at' => date('Y-m-d H:i:s', filemtime($file)),
        ];
    }

    public static function url(): ?string
    {
        return static::latest()['url'] ?? null;
    }
}