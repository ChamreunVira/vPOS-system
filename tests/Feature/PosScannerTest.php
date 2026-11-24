<?php

use App\Models\PosScanSession;
use App\Models\User;

beforeEach(function (): void {
    $this->cashier = User::factory()->create();
    $this->otherCashier = User::factory()->create();
});

test('guest cannot access desktop scanner endpoints', function () {
    $this->postJson(route('pos.scanner.session'))->assertUnauthorized();
    $this->getJson(route('pos.scanner.status'))->assertUnauthorized();
    $this->postJson(route('pos.scanner.regenerate'))->assertUnauthorized();
    $this->postJson(route('pos.scanner.disconnect'))->assertUnauthorized();
    $this->getJson(route('pos.scanner.events'))->assertUnauthorized();
});

test('cashier can create a scanner session with QR token and manual pairing code', function () {
    $response = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'is_paired' => false,
            ],
        ]);

    $data = $response->json('data');
    expect($data['pairing_token'])->toBeString()->toHaveLength(64)
        ->and($data['pairing_code'])->toBeString()->toHaveLength(6)
        ->and($data['qr_url'])->toContain('/pos/scanner?token=')
        ->and($data['expires_at'])->toBeString();

    $this->assertDatabaseHas('pos_scan_sessions', [
        'id' => $data['session_id'],
        'user_id' => $this->cashier->id,
        'pairing_token_hash' => hash('sha256', $data['pairing_token']),
        'pairing_code_hash' => hash('sha256', $data['pairing_code']),
        'device_token_hash' => null,
    ]);
});

test('cashier can check scanner session status', function () {
    $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));

    $response = $this->actingAs($this->cashier)->getJson(route('pos.scanner.status'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'is_paired' => false,
                'is_expired' => false,
            ],
        ]);
});

test('cashier can regenerate pairing credentials', function () {
    $firstResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $firstCode = $firstResponse->json('data.pairing_code');
    $firstToken = $firstResponse->json('data.pairing_token');

    $regenResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.regenerate'));

    $regenResponse->assertOk();
    $newCode = $regenResponse->json('data.pairing_code');
    $newToken = $regenResponse->json('data.pairing_token');

    expect($newCode)->not->toBe($firstCode)
        ->and($newToken)->not->toBe($firstToken);
});

test('mobile scanner page can be rendered', function () {
    $response = $this->get(route('pos.scanner.mobile', ['code' => 'ABC123', 'token' => 'xyz']));

    $response->assertOk()
        ->assertSee('vPOS Mobile Scanner');
});

test('mobile device can pair using valid QR token (Method A)', function () {
    $createResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $qrToken = $createResponse->json('data.pairing_token');

    $pairResponse = $this->postJson(route('pos.scanner.pair.qr'), [
        'token' => $qrToken,
    ]);

    $pairResponse->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'cashier_name' => $this->cashier->name,
            ],
        ]);

    $deviceToken = $pairResponse->json('data.device_token');
    expect($deviceToken)->toBeString()->toHaveLength(64);

    // Verify session in database is updated and pairing token hash is cleared (single-use)
    $this->assertDatabaseHas('pos_scan_sessions', [
        'user_id' => $this->cashier->id,
        'device_token_hash' => hash('sha256', $deviceToken),
        'pairing_token_hash' => null,
        'pairing_code_hash' => null,
    ]);
});

test('QR token cannot be used twice (single-use)', function () {
    $createResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $qrToken = $createResponse->json('data.pairing_token');

    // First attempt succeeds
    $this->postJson(route('pos.scanner.pair.qr'), ['token' => $qrToken])->assertOk();

    // Second attempt fails
    $retryResponse = $this->postJson(route('pos.scanner.pair.qr'), ['token' => $qrToken]);
    $retryResponse->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('expired QR token is rejected', function () {
    $session = PosScanSession::create([
        'user_id' => $this->cashier->id,
        'pairing_token_hash' => hash('sha256', 'expired_token'),
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->postJson(route('pos.scanner.pair.qr'), [
        'token' => 'expired_token',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('mobile device can pair using manual code (Method B)', function () {
    $createResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $pairingCode = $createResponse->json('data.pairing_code');

    // Test with lowercase and hyphen: "k7x-9pq"
    $formattedCode = strtolower(substr($pairingCode, 0, 3).'-'.substr($pairingCode, 3));

    $pairResponse = $this->postJson(route('pos.scanner.pair.code'), [
        'code' => $formattedCode,
    ]);

    $pairResponse->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'cashier_name' => $this->cashier->name,
            ],
        ]);

    $deviceToken = $pairResponse->json('data.device_token');
    expect($deviceToken)->toBeString();
});

test('invalid or expired pairing code is rejected', function () {
    $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));

    // Wrong code
    $this->postJson(route('pos.scanner.pair.code'), ['code' => 'WRONG1'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);

    // Expired code
    PosScanSession::create([
        'user_id' => $this->cashier->id,
        'pairing_code_hash' => hash('sha256', 'EXP123'),
        'expires_at' => now()->subMinute(),
    ]);

    $this->postJson(route('pos.scanner.pair.code'), ['code' => 'EXP123'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('paired mobile device can check status and submit scanned barcodes', function () {
    $createResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $qrToken = $createResponse->json('data.pairing_token');

    $pairResponse = $this->postJson(route('pos.scanner.pair.qr'), ['token' => $qrToken]);
    $deviceToken = $pairResponse->json('data.device_token');

    // Check device status via X-Device-Token header
    $statusResponse = $this->withHeader('X-Device-Token', $deviceToken)
        ->getJson(route('pos.scanner.device.status'));

    $statusResponse->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'is_paired' => true,
                'cashier_name' => $this->cashier->name,
            ],
        ]);

    // Submit barcode
    $scanResponse = $this->withHeader('X-Device-Token', $deviceToken)
        ->postJson(route('pos.scanner.device.scan'), [
            'barcode' => '8850123456789',
        ]);

    $scanResponse->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'barcode' => '8850123456789',
            ],
        ]);

    $this->assertDatabaseHas('pos_scan_events', [
        'barcode' => '8850123456789',
    ]);
});

test('submitting scan with missing or invalid device token returns 401', function () {
    $this->postJson(route('pos.scanner.device.scan'), [
        'barcode' => '123456',
    ])->assertUnauthorized();

    $this->withHeader('X-Device-Token', 'invalid_token_12345')
        ->postJson(route('pos.scanner.device.scan'), [
            'barcode' => '123456',
        ])->assertUnauthorized();
});

test('desktop POS receives scan events sequentially via polling with after_id cursor', function () {
    $createResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $qrToken = $createResponse->json('data.pairing_token');

    $pairResponse = $this->postJson(route('pos.scanner.pair.qr'), ['token' => $qrToken]);
    $deviceToken = $pairResponse->json('data.device_token');

    // Mobile submits 2 barcodes
    $this->withHeader('X-Device-Token', $deviceToken)
        ->postJson(route('pos.scanner.device.scan'), ['barcode' => 'CODE-1']);
    $this->withHeader('X-Device-Token', $deviceToken)
        ->postJson(route('pos.scanner.device.scan'), ['barcode' => 'CODE-2']);

    // Desktop polls events from start (after_id = 0)
    $eventsResponse = $this->actingAs($this->cashier)->getJson(route('pos.scanner.events', ['after_id' => 0]));

    $eventsResponse->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'is_paired' => true,
            ],
        ]);

    $events = $eventsResponse->json('data.events');
    expect($events)->toHaveCount(2)
        ->and($events[0]['barcode'])->toBe('CODE-1')
        ->and($events[1]['barcode'])->toBe('CODE-2');

    $lastEventId = $events[1]['id'];

    // Mobile submits a 3rd barcode
    $this->withHeader('X-Device-Token', $deviceToken)
        ->postJson(route('pos.scanner.device.scan'), ['barcode' => 'CODE-3']);

    // Polling with after_id = $lastEventId only returns the new event
    $nextPoll = $this->actingAs($this->cashier)->getJson(route('pos.scanner.events', ['after_id' => $lastEventId]));
    $nextEvents = $nextPoll->json('data.events');

    expect($nextEvents)->toHaveCount(1)
        ->and($nextEvents[0]['barcode'])->toBe('CODE-3');
});

test('cashier cannot access or receive scan events from another cashier session', function () {
    // Cashier A pairs device and submits barcode
    $createA = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $pairA = $this->postJson(route('pos.scanner.pair.qr'), ['token' => $createA->json('data.pairing_token')]);
    $tokenA = $pairA->json('data.device_token');

    $this->withHeader('X-Device-Token', $tokenA)
        ->postJson(route('pos.scanner.device.scan'), ['barcode' => 'CASHIER-A-ITEM']);

    // Cashier B creates their own session
    $this->actingAs($this->otherCashier)->postJson(route('pos.scanner.session'));

    // Cashier B polling events sees 0 events (isolated)
    $pollB = $this->actingAs($this->otherCashier)->getJson(route('pos.scanner.events', ['after_id' => 0]));
    $pollB->assertOk();
    expect($pollB->json('data.events'))->toBeEmpty();
});

test('mobile device can disconnect from session', function () {
    $createResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $pairResponse = $this->postJson(route('pos.scanner.pair.qr'), ['token' => $createResponse->json('data.pairing_token')]);
    $deviceToken = $pairResponse->json('data.device_token');

    // Mobile calls disconnect
    $disconnectResponse = $this->withHeader('X-Device-Token', $deviceToken)
        ->postJson(route('pos.scanner.device.disconnect'));

    $disconnectResponse->assertOk()
        ->assertJson(['success' => true]);

    // Submitting barcode now fails with 401
    $this->withHeader('X-Device-Token', $deviceToken)
        ->postJson(route('pos.scanner.device.scan'), ['barcode' => 'NEW-ITEM'])
        ->assertUnauthorized();

    // Desktop status shows unpaired
    $statusResponse = $this->actingAs($this->cashier)->getJson(route('pos.scanner.status'));
    expect($statusResponse->json('data.is_paired'))->toBeFalse();
});

test('desktop cashier can disconnect mobile device', function () {
    $createResponse = $this->actingAs($this->cashier)->postJson(route('pos.scanner.session'));
    $pairResponse = $this->postJson(route('pos.scanner.pair.qr'), ['token' => $createResponse->json('data.pairing_token')]);
    $deviceToken = $pairResponse->json('data.device_token');

    // Desktop cashier disconnects
    $this->actingAs($this->cashier)->postJson(route('pos.scanner.disconnect'))->assertOk();

    // Mobile device is now unauthorized
    $this->withHeader('X-Device-Token', $deviceToken)
        ->postJson(route('pos.scanner.device.scan'), ['barcode' => 'TEST'])
        ->assertUnauthorized();
});
