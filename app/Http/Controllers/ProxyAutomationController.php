<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ProxyAutomationController extends Controller
{
    /**
     * Manually trigger the Premium Proxy hunting and distribution.
     * GET /api/proxies/refresh
     */
    public function refresh()
    {
        Log::info("ProxyAutomationController: Manual proxy refresh triggered.");

        try {
            // Run the Artisan command programmatically
            $exitCode = Artisan::call('proxies:fetch-premium');
            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => 'Premium Proxy rotation processed.',
                'output'  => $output,
            ]);
        } catch (\Exception $e) {
            Log::error("ProxyAutomationController: Refresh failed — " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to rotate proxies.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
