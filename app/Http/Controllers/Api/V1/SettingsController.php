<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $settings = AppSetting::all()->mapWithKeys(fn (AppSetting $s) => [
            $s->key => $s->value,
        ]);

        $version = $settings['app_version'] ?? null;
        $minVersion = $settings['min_app_version'] ?? null;

        $outdated = $version !== null && $minVersion !== null
            && version_compare((string) $minVersion, ($request->header('X-App-Version', '0.0.0'))) > 0;

        return response()->json([
            'success' => true,
            'settings' => $settings,
            'maintenance_mode' => (bool) ($settings['maintenance_mode'] ?? false),
            'force_update' => $outdated,
            'apk' => $this->latestApk(),
        ]);
    }

    /**
     * Public endpoint used by the app to check for APK updates before login.
     */
    public function latest(Request $request)
    {
        $settings = AppSetting::all()->mapWithKeys(fn (AppSetting $s) => [
            $s->key => $s->value,
        ]);

        $version = $settings['app_version'] ?? null;
        $minVersion = $settings['min_app_version'] ?? null;

        $outdated = $version !== null && $minVersion !== null
            && version_compare((string) $minVersion, ($request->header('X-App-Version', '0.0.0'))) > 0;

        return response()->json([
            'success' => true,
            'app_version' => $version,
            'min_app_version' => $minVersion,
            'maintenance_mode' => (bool) ($settings['maintenance_mode'] ?? false),
            'force_update' => $outdated,
            'apk' => $this->latestApk(),
        ]);
    }

    /**
     * Locates the newest APK in public/download so the app can auto-update
     * without the Play Store. Returns null when no APK is published.
     *
     * @return array{version: string, url: string, size: int, filename: string}|null
     */
    private function latestApk(): ?array
    {
        $files = glob(public_path('download/*.apk')) ?: [];

        if (empty($files)) {
            return null;
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        $file = $files[0];
        $filename = basename($file);

        $version = (string) (AppSetting::get('app_version') ?? '1.0.0');

        return [
            'version' => $version,
            'url' => url('download/' . $filename),
            'size' => (int) filesize($file),
            'filename' => $filename,
        ];
    }
}