<?php

namespace App\Services;

use App\Models\PosScanEvent;
use App\Models\PosScanSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosScannerService
{
    /**
     * Characters used for unambiguous manual pairing codes (excludes 0, O, 1, I).
     */
    protected const PAIRING_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * Get or create a scanner session for the given cashier.
     *
     * @return array{session: PosScanSession, pairing_token: ?string, pairing_code: ?string}
     */
    public function getOrCreateSession(User $user): array
    {
        $session = PosScanSession::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($session && $session->isPaired()) {
            return [
                'session' => $session,
                'pairing_token' => null,
                'pairing_code' => null,
            ];
        }

        if (! $session) {
            $session = new PosScanSession;
            $session->user_id = $user->id;
        }

        $token = bin2hex(random_bytes(32));
        $code = $this->generatePairingCode();

        $session->pairing_token_hash = hash('sha256', $token);
        $session->pairing_code_hash = hash('sha256', $code);
        $session->device_token_hash = null;
        $session->paired_at = null;
        $session->expires_at = now()->addMinutes(2);
        $session->save();

        return [
            'session' => $session,
            'pairing_token' => $token,
            'pairing_code' => $code,
        ];
    }

    /**
     * Regenerate pairing credentials (QR token and manual code) with a fresh 2-minute lifetime.
     *
     * @return array{session: PosScanSession, pairing_token: string, pairing_code: string}
     */
    public function regenerateCredentials(PosScanSession $session): array
    {
        $token = bin2hex(random_bytes(32));
        $code = $this->generatePairingCode();

        $session->pairing_token_hash = hash('sha256', $token);
        $session->pairing_code_hash = hash('sha256', $code);
        $session->device_token_hash = null;
        $session->paired_at = null;
        $session->expires_at = now()->addMinutes(2);
        $session->save();

        return [
            'session' => $session,
            'pairing_token' => $token,
            'pairing_code' => $code,
        ];
    }

    /**
     * Pair a mobile device using a single-use QR token.
     *
     * @return array{device_token: string, session: PosScanSession}
     */
    public function pairViaQrToken(string $token): array
    {
        $tokenHash = hash('sha256', trim($token));

        return DB::transaction(function () use ($tokenHash) {
            /** @var PosScanSession|null $session */
            $session = PosScanSession::where('pairing_token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw ValidationException::withMessages([
                    'token' => ['Invalid pairing QR code.'],
                ]);
            }

            if ($session->isExpired()) {
                throw ValidationException::withMessages([
                    'token' => ['This pairing QR code has expired. Please refresh the QR code on the POS screen.'],
                ]);
            }

            if ($session->isPaired()) {
                throw ValidationException::withMessages([
                    'token' => ['This POS session is already paired with a mobile device.'],
                ]);
            }

            $deviceToken = bin2hex(random_bytes(32));

            $session->device_token_hash = hash('sha256', $deviceToken);
            $session->paired_at = now();
            $session->pairing_token_hash = null;
            $session->pairing_code_hash = null;
            $session->expires_at = now()->addHours(8);
            $session->save();

            return [
                'device_token' => $deviceToken,
                'session' => $session,
            ];
        });
    }

    /**
     * Pair a mobile device using a single-use manual 6-character code.
     *
     * @return array{device_token: string, session: PosScanSession}
     */
    public function pairViaCode(string $code): array
    {
        $normalizedCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
        $codeHash = hash('sha256', $normalizedCode);

        return DB::transaction(function () use ($codeHash) {
            /** @var PosScanSession|null $session */
            $session = PosScanSession::where('pairing_code_hash', $codeHash)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw ValidationException::withMessages([
                    'code' => ['Invalid pairing code.'],
                ]);
            }

            if ($session->isExpired()) {
                throw ValidationException::withMessages([
                    'code' => ['This pairing code has expired. Please generate a new code on the POS screen.'],
                ]);
            }

            if ($session->isPaired()) {
                throw ValidationException::withMessages([
                    'code' => ['This POS session is already paired with a mobile device.'],
                ]);
            }

            $deviceToken = bin2hex(random_bytes(32));

            $session->device_token_hash = hash('sha256', $deviceToken);
            $session->paired_at = now();
            $session->pairing_token_hash = null;
            $session->pairing_code_hash = null;
            $session->expires_at = now()->addHours(8);
            $session->save();

            return [
                'device_token' => $deviceToken,
                'session' => $session,
            ];
        });
    }

    /**
     * Record a scanned barcode event for the session.
     */
    public function recordScan(PosScanSession $session, string $barcode): PosScanEvent
    {
        $cleanBarcode = trim($barcode);

        if ($cleanBarcode === '' || strlen($cleanBarcode) > 100) {
            throw ValidationException::withMessages([
                'barcode' => ['Invalid barcode format.'],
            ]);
        }

        return PosScanEvent::create([
            'pos_scan_session_id' => $session->id,
            'barcode' => $cleanBarcode,
            'created_at' => now(),
        ]);
    }

    /**
     * Fetch unprocessed scan events after a given event cursor.
     *
     * @return Collection<int, PosScanEvent>
     */
    public function getEventsAfter(PosScanSession $session, ?int $afterId = null): Collection
    {
        $query = PosScanEvent::where('pos_scan_session_id', $session->id);

        if ($afterId !== null && $afterId > 0) {
            $query->where('id', '>', $afterId);
        }

        $events = $query->orderBy('id', 'asc')->limit(50)->get();

        if ($events->isNotEmpty()) {
            PosScanEvent::whereIn('id', $events->pluck('id'))
                ->whereNull('processed_at')
                ->update(['processed_at' => now()]);
        }

        return $events;
    }

    /**
     * Disconnect the paired device from the session.
     */
    public function disconnect(PosScanSession $session): void
    {
        $session->device_token_hash = null;
        $session->paired_at = null;
        $session->expires_at = now()->addMinutes(2);
        $session->save();
    }

    /**
     * Generate an unambiguous 6-character uppercase pairing code.
     */
    protected function generatePairingCode(): string
    {
        $length = strlen(self::PAIRING_ALPHABET);
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= self::PAIRING_ALPHABET[random_int(0, $length - 1)];
        }

        return $code;
    }
}
