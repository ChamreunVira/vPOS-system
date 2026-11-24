<?php

namespace App\Support;

use Illuminate\Foundation\Vite as BaseVite;

class Vite extends BaseVite
{
    /**
     * Determine if Vite is running in development / hot mode.
     * When accessed through an HTTPS tunnel (e.g. ngrok, Cloudflare Tunnel) or remote HTTPS proxy,
     * fall back to the built production assets in public/build/ if available, because tunnels
     * only forward port 8000 and cannot reach Vite dev server on port 5173.
     */
    public function isRunningHot(): bool
    {
        if (! parent::isRunningHot()) {
            return false;
        }

        $request = request();

        if ($request) {
            $host = $request->getHost();
            $isTunnel = str_contains($host, 'ngrok') || str_contains($host, 'trycloudflare');
            $isRemoteSecure = $request->isSecure() && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);

            if (($isTunnel || $isRemoteSecure) && is_file(public_path('build/manifest.json'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the URL of the running Vite development server.
     * Dynamically rewrites localhost / 127.0.0.1 to the incoming request host
     * so mobile devices on PAN / Hotspot / LAN can reach the Vite server.
     */
    public function devServerUrl(): ?string
    {
        $url = parent::devServerUrl();

        if (! $url) {
            return null;
        }

        $request = request();
        $requestHost = $request?->getHost();

        if ($requestHost && ! in_array($requestHost, ['localhost', '127.0.0.1', '::1'], true)) {
            $parsed = parse_url($url);
            $port = isset($parsed['port']) ? ':'.$parsed['port'] : ':5173';
            $scheme = ($request && $request->isSecure()) ? 'https' : ($parsed['scheme'] ?? 'http');

            return "{$scheme}://{$requestHost}{$port}";
        }

        return $url;
    }
}
