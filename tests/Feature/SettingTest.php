<?php

use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Cash', 'is_enable' => true]);
    PaymentMethod::firstOrCreate(['code' => 'khqr'], ['name' => 'KHQR', 'is_enable' => true]);
    PaymentMethod::firstOrCreate(['code' => 'card'], ['name' => 'Card', 'is_enable' => false]);
});

test('user can view settings page with all tabs', function (): void {
    $response = $this->actingAs($this->user)->get(route('settings.index'));

    $response->assertOk()
        ->assertSee('Settings')
        ->assertSee('Business profile')
        ->assertSee('Currency & VAT')
        ->assertSee('Receipt')
        ->assertSee('Payment methods');
});

test('user can save business profile settings', function (): void {
    $response = $this->actingAs($this->user)
        ->put(route('settings.update'), [
            'business' => [
                'name' => 'My Test Shop',
                'phone' => '+855 12 345 678',
                'email' => 'shop@test.com',
                'vat_number' => 'K001-123456',
                'address' => 'No. 1, Street 1, Phnom Penh',
            ],
            'currency' => [
                'primary' => 'USD',
                'secondary' => 'KHR',
                'rate' => 4100,
                'vat_rate' => 10,
            ],
            'receipt' => ['paper' => '80mm'],
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    expect(Setting::get('business.name'))->toBe('My Test Shop')
        ->and(Setting::get('business.phone'))->toBe('+855 12 345 678')
        ->and(Setting::get('business.email'))->toBe('shop@test.com')
        ->and(Setting::get('business.vat_number'))->toBe('K001-123456');
});

test('user can save currency and vat settings', function (): void {
    $response = $this->actingAs($this->user)
        ->put(route('settings.update'), [
            'business' => ['name' => 'Test Shop'],
            'currency' => [
                'primary' => 'USD',
                'secondary' => 'KHR',
                'rate' => 4100,
                'vat_enabled' => '1',
                'vat_rate' => 10,
                'prices_include_vat' => '0',
            ],
            'receipt' => ['paper' => '80mm'],
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    expect((bool) Setting::get('currency.vat_enabled'))->toBeTrue()
        ->and((float) Setting::get('currency.vat_rate'))->toBe(10.0)
        ->and(Setting::get('currency.primary'))->toBe('USD');
});

test('user can save receipt settings', function (): void {
    $response = $this->actingAs($this->user)
        ->put(route('settings.update'), [
            'business' => ['name' => 'Test Shop'],
            'currency' => ['primary' => 'USD', 'rate' => 4100],
            'receipt' => [
                'paper' => '58mm',
                'header' => 'Welcome to our shop!',
                'footer' => 'No returns after 7 days.',
                'show_logo' => '1',
                'show_khr' => '0',
                'auto_print' => '1',
            ],
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    expect(Setting::get('receipt.paper'))->toBe('58mm')
        ->and(Setting::get('receipt.header'))->toBe('Welcome to our shop!')
        ->and(Setting::get('receipt.footer'))->toBe('No returns after 7 days.')
        ->and((bool) Setting::get('receipt.auto_print'))->toBeTrue();
});

test('user can enable and disable payment methods', function (): void {
    // Enable cash and khqr, leave card off
    $response = $this->actingAs($this->user)
        ->put(route('settings.update'), [
            'business' => ['name' => 'Test Shop'],
            'currency' => ['primary' => 'USD', 'rate' => 4100],
            'receipt' => ['paper' => '80mm'],
            'payments' => ['cash' => '1', 'khqr' => '1'],
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    expect(PaymentMethod::where('code', 'cash')->value('is_enable'))->toBe(1)
        ->and(PaymentMethod::where('code', 'khqr')->value('is_enable'))->toBe(1)
        ->and(PaymentMethod::where('code', 'card')->value('is_enable'))->toBe(0);
});

test('user can save khqr account settings', function (): void {
    $response = $this->actingAs($this->user)
        ->put(route('settings.update'), [
            'business' => ['name' => 'Test Shop'],
            'currency' => ['primary' => 'USD', 'rate' => 4100],
            'receipt' => ['paper' => '80mm'],
            'khqr' => [
                'account_name' => 'MY SHOP LLC',
                'bakong_id' => 'myshop@acleda',
                'merchant_city' => 'Siem Reap',
            ],
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    expect(Setting::get('khqr.account_name'))->toBe('MY SHOP LLC')
        ->and(Setting::get('khqr.bakong_id'))->toBe('myshop@acleda')
        ->and(Setting::get('khqr.merchant_city'))->toBe('Siem Reap');
});

test('user can upload a business logo', function (): void {
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->actingAs($this->user)
        ->put(route('settings.update'), [
            'business' => ['name' => 'Test Shop'],
            'currency' => ['primary' => 'USD', 'rate' => 4100],
            'receipt' => ['paper' => '80mm'],
            'logo' => $logo,
        ]);

    $response->assertRedirect()->assertSessionHas('success');

    $logoPath = Setting::get('business.logo');
    expect($logoPath)->not()->toBeNull();
    Storage::disk('public')->assertExists((string) $logoPath);
});

test('settings page shows saved values on next visit', function (): void {
    Setting::set('business.name', 'Saved Shop Name');
    Setting::set('business.phone', '+855 99 888 777');
    Setting::set('receipt.header', 'Thanks for buying from us!');

    $response = $this->actingAs($this->user)->get(route('settings.index'));

    $response->assertOk()
        ->assertSee('Saved Shop Name')
        ->assertSee('+855 99 888 777')
        ->assertSee('Thanks for buying from us!');
});

test('settings validation rejects invalid input', function (): void {
    $response = $this->actingAs($this->user)
        ->put(route('settings.update'), [
            'business' => ['name' => ''],          // required
            'currency' => [
                'primary' => 'XYZ',               // invalid currency
                'rate' => -100,                 // must be >= 1
            ],
            'receipt' => ['paper' => '120mm'],    // invalid paper
        ]);

    $response->assertSessionHasErrors(['business.name', 'currency.primary', 'currency.rate', 'receipt.paper']);
});
