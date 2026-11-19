<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::allAsArray();

        // Merge live PaymentMethod enabled states from the database
        $allMethods = PaymentMethod::all()->keyBy('code');
        foreach ($settings['payments'] as &$method) {
            $dbMethod = $allMethods->get($method['key']);
            if ($dbMethod !== null) {
                $method['enabled'] = (bool) $dbMethod->is_enable;
            }
        }
        unset($method);

        // Load saved logo path
        $settings['business']['logo'] = Setting::get('business.logo');

        return view('settings.index', ['settings' => $settings]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'business.name' => ['required', 'string', 'max:255'],
            'business.phone' => ['nullable', 'string', 'max:50'],
            'business.email' => ['nullable', 'email', 'max:255'],
            'business.vat_number' => ['nullable', 'string', 'max:100'],
            'business.address' => ['nullable', 'string', 'max:500'],
            'currency.primary' => ['required', 'string', 'in:USD,KHR'],
            'currency.secondary' => ['nullable', 'string', 'in:USD,KHR,'],
            'currency.rate' => ['required', 'numeric', 'min:1'],
            'currency.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'receipt.paper' => ['required', 'string', 'in:58mm,80mm'],
            'receipt.header' => ['nullable', 'string', 'max:500'],
            'receipt.footer' => ['nullable', 'string', 'max:500'],
            'khqr.account_name' => ['nullable', 'string', 'max:255'],
            'khqr.bakong_id' => ['nullable', 'string', 'max:255'],
            'khqr.merchant_city' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        // --- Business profile ---
        foreach ($request->input('business', []) as $field => $value) {
            Setting::set("business.{$field}", (string) $value);
        }

        // --- Logo upload ---
        if ($request->hasFile('logo')) {
            $oldLogo = Setting::get('business.logo');
            if ($oldLogo && Storage::disk('public')->exists((string) $oldLogo)) {
                Storage::disk('public')->delete((string) $oldLogo);
            }
            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('business.logo', $path);
        }

        // --- Currency & VAT ---
        $currency = $request->input('currency', []);
        $currency['vat_enabled'] = $request->boolean('currency.vat_enabled');
        $currency['prices_include_vat'] = $request->boolean('currency.prices_include_vat');
        foreach ($currency as $field => $value) {
            Setting::set("currency.{$field}", $value);
        }

        // --- Receipt ---
        $receipt = $request->input('receipt', []);
        $receipt['show_logo'] = $request->boolean('receipt.show_logo');
        $receipt['show_khr'] = $request->boolean('receipt.show_khr');
        $receipt['auto_print'] = $request->boolean('receipt.auto_print');
        foreach ($receipt as $field => $value) {
            Setting::set("receipt.{$field}", $value);
        }

        // --- Payment methods: sync PaymentMethod table ---
        $paymentToggles = $request->input('payments', []);
        $allMethods = [
            'cash' => 'Cash',
            'khqr' => 'KHQR',
            'card' => 'Card',
        ];
        foreach ($allMethods as $code => $name) {
            $enabled = isset($paymentToggles[$code]) && (bool) $paymentToggles[$code];
            PaymentMethod::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_enable' => $enabled]
            );
        }

        // --- KHQR ---
        foreach ($request->input('khqr', []) as $field => $value) {
            Setting::set("khqr.{$field}", (string) $value);
        }

        Setting::flushCache();

        return back()->with('success', 'Settings saved successfully.');
    }
}
