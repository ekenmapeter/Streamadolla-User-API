<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key');

        if (! $key || ! hash_equals((string) config('app.api_key', ''), (string) $key)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing or invalid API key.',
            ], 401);
        }

        return $next($request);
    }
}