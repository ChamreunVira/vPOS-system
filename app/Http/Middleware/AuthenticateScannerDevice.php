<?php

namespace App\Http\Middleware;

use App\Models\PosScanSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateScannerDevice
{
    /**
     * Handle an incoming request from a mobile scanner device.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Device-Token');

        if (! $token || ! is_string($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Scanner device token is required.',
            ], 401);
        }

        $tokenHash = hash('sha256', trim($token));

        $session = PosScanSession::where('device_token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Device session is invalid or has expired. Please re-pair your device.',
            ], 401);
        }

        $request->attributes->set('scanner_session', $session);

        return $next($request);
    }
}
