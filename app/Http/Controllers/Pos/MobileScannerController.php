<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosScanSession;
use App\Services\PosScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileScannerController extends Controller
{
    public function __construct(
        public PosScannerService $scannerService
    ) {}

    /**
     * Display the mobile barcode scanner interface.
     */
    public function index(Request $request): View
    {
        return view('pos.scanner', [
            'initialToken' => (string) $request->query('token', ''),
            'initialCode' => (string) $request->query('code', ''),
        ]);
    }

    /**
     * Pair device via QR token.
     */
    public function pairQr(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:128'],
        ]);

        $result = $this->scannerService->pairViaQrToken($request->string('token')->value());

        return response()->json([
            'success' => true,
            'message' => 'Mobile scanner connected successfully.',
            'data' => [
                'device_token' => $result['device_token'],
                'cashier_name' => $result['session']->user->name,
                'paired_at' => $result['session']->paired_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Pair device via manual 6-character pairing code.
     */
    public function pairCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:12'],
        ]);

        $result = $this->scannerService->pairViaCode($request->string('code')->value());

        return response()->json([
            'success' => true,
            'message' => 'Mobile scanner connected successfully.',
            'data' => [
                'device_token' => $result['device_token'],
                'cashier_name' => $result['session']->user->name,
                'paired_at' => $result['session']->paired_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Check if the authenticated mobile device is still connected.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var PosScanSession $session */
        $session = $request->attributes->get('scanner_session');

        return response()->json([
            'success' => true,
            'data' => [
                'is_paired' => $session->isPaired(),
                'cashier_name' => $session->user->name,
                'paired_at' => $session->paired_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Submit a scanned barcode to the POS session.
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => ['required', 'string', 'max:100'],
        ]);

        /** @var PosScanSession $session */
        $session = $request->attributes->get('scanner_session');

        $event = $this->scannerService->recordScan($session, $request->string('barcode')->value());

        return response()->json([
            'success' => true,
            'message' => 'Barcode submitted successfully.',
            'data' => [
                'event_id' => $event->id,
                'barcode' => $event->barcode,
                'created_at' => $event->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Disconnect the mobile device.
     */
    public function disconnect(Request $request): JsonResponse
    {
        /** @var PosScanSession $session */
        $session = $request->attributes->get('scanner_session');

        $this->scannerService->disconnect($session);

        return response()->json([
            'success' => true,
            'message' => 'Device disconnected.',
        ]);
    }
}
