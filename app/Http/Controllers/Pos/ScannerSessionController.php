<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosScanSession;
use App\Services\PosScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScannerSessionController extends Controller
{
    public function __construct(
        public PosScannerService $scannerService
    ) {}

    /**
     * Create or retrieve active scanner session for the current cashier.
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->scannerService->getOrCreateSession($request->user());
        $session = $result['session'];

        $token = $result['pairing_token'];
        $code = $result['pairing_code'];

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'is_paired' => $session->isPaired(),
                'pairing_token' => $token,
                'pairing_code' => $code,
                'qr_url' => $token ? route('pos.scanner.mobile', ['token' => $token]) : null,
                'expires_at' => $session->expires_at->toIso8601String(),
                'paired_at' => $session->paired_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Retrieve the current session status and connection state.
     */
    public function status(Request $request): JsonResponse
    {
        $session = PosScanSession::where('user_id', $request->user()->id)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $session) {
            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => null,
                    'is_paired' => false,
                    'is_expired' => true,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'is_paired' => $session->isPaired(),
                'is_expired' => $session->isExpired(),
                'paired_at' => $session->paired_at?->toIso8601String(),
                'expires_at' => $session->expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Regenerate pairing token & pairing code for the session.
     */
    public function regenerate(Request $request): JsonResponse
    {
        $session = PosScanSession::where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if (! $session) {
            $session = new PosScanSession;
            $session->user_id = $request->user()->id;
        }

        $result = $this->scannerService->regenerateCredentials($session);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'is_paired' => false,
                'pairing_token' => $result['pairing_token'],
                'pairing_code' => $result['pairing_code'],
                'qr_url' => route('pos.scanner.mobile', ['token' => $result['pairing_token']]),
                'expires_at' => $session->expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Disconnect the paired device from desktop POS.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $session = PosScanSession::where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if ($session) {
            $this->scannerService->disconnect($session);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mobile scanner disconnected.',
        ]);
    }

    /**
     * Retrieve unprocessed scan events sequentially using incremental IDs.
     */
    public function events(Request $request): JsonResponse
    {
        $afterId = $request->query('after_id');
        $afterIdInt = is_numeric($afterId) ? (int) $afterId : null;

        $session = PosScanSession::where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if (! $session || ! $session->isPaired()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'events' => [],
                    'is_paired' => false,
                    'is_expired' => $session ? $session->isExpired() : true,
                ],
            ]);
        }

        $events = $this->scannerService->getEventsAfter($session, $afterIdInt);

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $events->map(fn ($event) => [
                    'id' => $event->id,
                    'barcode' => $event->barcode,
                    'created_at' => $event->created_at?->toIso8601String(),
                ])->values(),
                'is_paired' => true,
                'is_expired' => false,
            ],
        ]);
    }
}
