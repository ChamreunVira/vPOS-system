<?php

use App\Support\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

test('vite dev server url rewrites host for mobile devices on LAN or hotspot', function () {
    $vite = app(Vite::class);
    $hotPath = public_path('/hot');

    File::put($hotPath, "http://localhost:5173\n");

    try {
        // Request from localhost
        $requestLocal = Request::create('http://localhost:8000');
        app()->instance('request', $requestLocal);
        expect($vite->devServerUrl())->toBe('http://localhost:5173');

        // Request from mobile device over hotspot / LAN IP
        $requestMobile = Request::create('http://192.168.43.50:8000');
        app()->instance('request', $requestMobile);
        expect($vite->devServerUrl())->toBe('http://192.168.43.50:5173');
    } finally {
        if (File::exists($hotPath)) {
            File::delete($hotPath);
        }
    }
});

test('vite falls back to built production assets when accessed via https tunnel or ngrok', function () {
    $vite = app(Vite::class);
    $hotPath = public_path('/hot');

    File::put($hotPath, "http://localhost:5173\n");

    try {
        // Request through ngrok tunnel
        $requestNgrok = Request::create('https://demo.ngrok-free.app/pos/scanner');
        $requestNgrok->headers->set('X-Forwarded-Proto', 'https');
        app()->instance('request', $requestNgrok);

        // Since build/manifest.json exists and request is through ngrok, isRunningHot should return false
        // so styles are served directly through the tunnel from build/assets/
        expect($vite->isRunningHot())->toBeFalse();
    } finally {
        if (File::exists($hotPath)) {
            File::delete($hotPath);
        }
    }
});
