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

        $versionOf = static function (string $file): ?string {
            $name = basename($file);

            return preg_match('/v?(\d+\.\d+\.\d+)/', $name, $m) ? $m[1] : null;
        };

        // Sort by the version in the filename, not filemtime: a deploy or
        // fresh clone gives every APK the same timestamp, which could make
        // the endpoint hand out an older build. mtime is only a tie-breaker.
        usort($files, function (string $a, string $b) use ($versionOf) {
            $va = $versionOf($a);
            $vb = $versionOf($b);

            if ($va === null && $vb === null) {
                return filemtime($b) <=> filemtime($a);
            }
            if ($va === null) {
                return 1;
            }
            if ($vb === null) {
                return -1;
            }

            $byVersion = version_compare($vb, $va);

            return $byVersion !== 0
                ? $byVersion
                : filemtime($b) <=> filemtime($a);
        });

        $file = $files[0];
        $filename = basename($file);

        // The version baked into the filename (e.g. app-v1.2.0.apk) is the
        // source of truth — the file itself may outpace the app_version
        // setting if a deploy skipped the settings update.
        $version = $versionOf($file) ?? (string) (AppSetting::get($versionSetting) ?? '1.0.0');

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