<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = config('services.admin_api_key');
        if (empty($key)) {
            return response()->json(['message' => 'Admin API key not configured.'], 503);
        }

        $provided = $request->header('X-Admin-Key') ?? $request->bearerToken();
        if ($provided !== $key) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
