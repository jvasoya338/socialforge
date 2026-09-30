<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\ContentSource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateContentSourceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->bearerToken() ?: (string) $request->header('X-API-Key', '');

        if (empty($rawKey)) {
            return response()->json([
                'error' => 'unauthenticated',
                'message' => 'Missing API key. Provide Authorization: Bearer <key> or X-API-Key header.',
            ], 401);
        }

        $source = ContentSource::findActiveByRawApiKey($rawKey);

        if (! $source || ! $source->brand || $source->brand->status !== Brand::STATUS_ACTIVE) {
            return response()->json([
                'error' => 'invalid_api_key',
                'message' => 'Invalid, revoked, or inactive content source API key.',
            ], 401);
        }

        $source->update(['api_key_last_used_at' => now()]);

        $request->attributes->set('authenticated_content_source', $source);
        $request->attributes->set('authenticated_brand', $source->brand);

        return $next($request);
    }
}
