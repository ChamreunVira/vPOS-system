<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->value('value');
        });

        // Defensive recovery: if cache returned an incomplete object or an old Eloquent model instance
        if (is_object($value)) {
            Cache::forget("setting.{$key}");
            $value = static::where('key', $key)->value('value');
        }

        if ($value === null) {
            return $default;
        }

        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        $encoded = is_string($value) ? $value : json_encode($value);

        static::updateOrCreate(['key' => $key], ['value' => $encoded]);

        Cache::forget("setting.{$key}");
    }

    /**
     * Flush all cached settings.
     */
    public static function flushCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }
    }

    /**
     * Load all settings as a nested array matching the view's expected shape.
     *
     * @return array<string, mixed>
     */
    public static function allAsArray(): array
    {
        $defaults = static::defaults();
        $stored = static::all()->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])->all();

        $result = $defaults;

        foreach ($stored as $key => $rawValue) {
            $decoded = json_decode($rawValue, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $rawValue;

            $parts = explode('.', $key, 2);
            if (count($parts) === 2) {
                [$group, $field] = $parts;
                if (isset($result[$group]) && is_array($result[$group])) {
                    $result[$group][$field] = $value;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Default settings matching the view's shape.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'business' => [
                'name' => config('vpos.store', 'My Shop'),
                'phone' => '',
                'email' => '',
                'vat_number' => '',
                'address' => '',
            ],
            'currency' => [
                'primary' => config('vpos.currency', 'USD'),
                'secondary' => 'KHR',
                'rate' => config('vpos.exchange_rate', 4100),
                'vat_enabled' => config('vpos.vat_rate', 0) > 0,
                'vat_rate' => config('vpos.vat_rate', 10),
                'prices_include_vat' => false,
            ],
            'receipt' => [
                'header' => 'Thank you for shopping with us!',
                'footer' => 'Goods sold are not returnable after 3 days.',
                'paper' => '80mm',
                'show_logo' => true,
                'show_khr' => true,
                'auto_print' => false,
            ],
            'payments' => [
                ['key' => 'cash',  'label' => 'Cash',           'enabled' => true,  'hint' => 'Count the cash drawer at the end of every shift.'],
                ['key' => 'khqr',  'label' => 'KHQR (Bakong)', 'enabled' => true,  'hint' => 'Customers scan the QR with any Cambodian banking app.'],
                ['key' => 'card',  'label' => 'Card',           'enabled' => false, 'hint' => 'Requires a connected card terminal.'],
            ],
            'khqr' => [
                'account_name' => '',
                'bakong_id' => '',
                'merchant_city' => 'Phnom Penh',
            ],
        ];
    }
}
