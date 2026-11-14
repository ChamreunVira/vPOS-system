<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Static mini-mart data used by every screen.
 *
 * Each method returns plain arrays shaped like the Eloquent models you will build later
 * (Product, Category, Order, Customer, ...). Swap a call such as MockData::products()
 * for Product::query()->get() when the real logic is ready; the Blade views only read keys.
 */
final class MockData
{
    // ---------------------------------------------------------------- catalogue

    public static function categories(): Collection
    {
        $counts = self::products()->countBy('category_id');

        return collect([
            [1, 'Beverages', 'Soft drinks, water, beer and canned coffee', 'active'],
            [2, 'Noodles & Instant Food', 'Instant noodles, canned and packaged meals', 'active'],
            [3, 'Snacks', 'Chips, biscuits and sweets', 'active'],
            [4, 'Rice & Cooking', 'Rice, oil, sauces and sugar', 'active'],
            [5, 'Dairy & Eggs', 'Milk, eggs and chilled items', 'active'],
            [6, 'Household', 'Cleaning and everyday home supplies', 'active'],
            [7, 'Personal Care', 'Soap, shampoo and dental care', 'active'],
            [8, 'Pchum Ben Specials', 'Seasonal offerings and gift packs', 'inactive'],
        ])->map(fn ($row) => [
            'id' => $row[0],
            'name' => $row[1],
            'description' => $row[2],
            'status' => $row[3],
            'products_count' => $counts[$row[0]] ?? 0,
        ]);
    }

    public static function units(): Collection
    {
        return collect([
            ['id' => 1, 'name' => 'Piece', 'abbreviation' => 'pc', 'type' => 'Count', 'allow_decimal' => false, 'products_count' => 42, 'status' => 'active'],
            ['id' => 2, 'name' => 'Kilogram', 'abbreviation' => 'kg', 'type' => 'Weight', 'allow_decimal' => true, 'products_count' => 8, 'status' => 'active'],
            ['id' => 3, 'name' => 'Gram', 'abbreviation' => 'g', 'type' => 'Weight', 'allow_decimal' => true, 'products_count' => 5, 'status' => 'active'],
            ['id' => 4, 'name' => 'Liter', 'abbreviation' => 'L', 'type' => 'Volume', 'allow_decimal' => true, 'products_count' => 6, 'status' => 'active'],
            ['id' => 5, 'name' => 'Milliliter', 'abbreviation' => 'ml', 'type' => 'Volume', 'allow_decimal' => true, 'products_count' => 11, 'status' => 'active'],
            ['id' => 6, 'name' => 'Pack', 'abbreviation' => 'pk', 'type' => 'Packaging', 'allow_decimal' => false, 'products_count' => 9, 'status' => 'active'],
            ['id' => 7, 'name' => 'Box', 'abbreviation' => 'box', 'type' => 'Packaging', 'allow_decimal' => false, 'products_count' => 4, 'status' => 'active'],
            ['id' => 8, 'name' => 'Bottle', 'abbreviation' => 'btl', 'type' => 'Packaging', 'allow_decimal' => false, 'products_count' => 0, 'status' => 'inactive'],
        ]);
    }

    public static function products(): Collection
    {
        // id, name, sku, barcode, category_id, cost, price, stock, min stock, emoji, units sold this month
        $rows = [
            [1, 'Angkor Beer 330ml Can', 'BEV-001', '8850001000011', 1, 0.62, 0.90, 240, 48, '🍺', 412],
            [2, 'Coca-Cola 330ml Can', 'BEV-002', '8850001000028', 1, 0.35, 0.50, 180, 48, '🥤', 388],
            [3, 'Drinking Water 500ml', 'BEV-003', '8850001000035', 1, 0.15, 0.25, 320, 100, '💧', 640],
            [4, 'Iced Coffee Can 240ml', 'BEV-004', '8850001000042', 1, 0.55, 0.85, 14, 24, '☕', 156],
            [5, 'Mama Noodles Pork Flavor', 'NDL-001', '8850002000019', 2, 0.22, 0.35, 15, 40, '🍜', 502],
            [6, 'Canned Sardines 155g', 'NDL-002', '8850002000026', 2, 0.80, 1.20, 46, 12, '🥫', 74],
            [7, "Lay's Classic Chips 52g", 'SNK-001', '8850003000010', 3, 0.45, 0.75, 8, 24, '🥔', 233],
            [8, 'Chocolate Biscuit Pack', 'SNK-002', '8850003000027', 3, 0.30, 0.50, 64, 20, '🍪', 198],
            [9, 'Jasmine Rice 5kg', 'RCE-001', '8850004000017', 4, 3.80, 4.75, 36, 10, '🍚', 121],
            [10, 'Fish Sauce 700ml', 'RCE-002', '8850004000024', 4, 1.10, 1.60, 28, 10, '🐟', 87],
            [11, 'Palm Sugar 500g', 'RCE-003', '8850004000031', 4, 1.20, 1.75, 22, 8, '🍯', 63],
            [12, 'Cooking Oil 1L', 'RCE-004', '8850004000048', 4, 1.90, 2.60, 24, 10, '🫗', 109],
            [13, 'Eggs 10 Pack', 'DRY-001', '8850005000016', 5, 1.30, 1.80, 40, 15, '🥚', 275],
            [14, 'Fresh Milk 1L', 'DRY-002', '8850005000023', 5, 1.40, 1.95, 3, 12, '🥛', 92],
            [15, 'Laundry Detergent 800g', 'HSE-001', '8850006000013', 6, 2.20, 3.10, 0, 8, '🧺', 58],
            [16, 'Toothpaste 120g', 'PCR-001', '8850007000011', 7, 1.00, 1.50, 31, 10, '🪥', 66],
        ];
        $categoryNames = [1 => 'Beverages', 2 => 'Noodles & Instant Food', 3 => 'Snacks', 4 => 'Rice & Cooking', 5 => 'Dairy & Eggs', 6 => 'Household', 7 => 'Personal Care'];

        return collect($rows)->map(fn ($r) => [
            'id' => $r[0], 'name' => $r[1], 'sku' => $r[2], 'barcode' => $r[3],
            'category_id' => $r[4], 'category' => $categoryNames[$r[4]],
            'cost' => $r[5], 'price' => $r[6], 'stock' => $r[7], 'min_stock' => $r[8],
            'emoji' => $r[9], 'image' => null, 'sold' => $r[10],
            'status' => 'active',
            'description' => 'Everyday mini-mart item. Replace this text with your own product description.',
        ]);
    }

    public static function product(int $id): array
    {
        return self::products()->firstWhere('id', $id) ?? abort(404);
    }

    public static function stockLevel(array $product): string
    {
        return match (true) {
            $product['stock'] <= 0 => 'out_of_stock',
            $product['stock'] <= $product['min_stock'] => 'low_stock',
            default => 'in_stock',
        };
    }

    public static function lowStockProducts(): Collection
    {
        return self::products()->filter(fn ($p) => $p['stock'] <= $p['min_stock'])->values();
    }

    // ---------------------------------------------------------------- customers

    public static function customers(): Collection
    {
        return collect([
            [1, 'Sok Dara', '+855 12 345 678', 'dara.sok@example.com', 'Sangkat Boeung Keng Kang, Phnom Penh', 'active', 'Nov 3, 2026', 'Prefers KHQR payment.'],
            [2, 'Chan Sreyneang', '+855 96 222 4410', 'sreyneang@example.com', 'Tuol Kork, Phnom Penh', 'active', 'Nov 5, 2026', ''],
            [3, 'Vanna Pich', '+855 77 810 223', 'vanna.pich@example.com', 'Chamkar Mon, Phnom Penh', 'active', 'Oct 29, 2026', 'Buys rice in bulk every two weeks.'],
            [4, 'Kim Sophea', '+855 10 456 990', 'sophea.kim@example.com', 'Daun Penh, Phnom Penh', 'active', 'Oct 21, 2026', ''],
            [5, 'Heng Visal', '+855 69 120 774', 'visal.heng@example.com', 'Sen Sok, Phnom Penh', 'inactive', 'Aug 14, 2026', ''],
            [6, 'Chea Srey Mom', '+855 88 301 556', 'sreymom.chea@example.com', 'Russey Keo, Phnom Penh', 'active', 'Nov 1, 2026', 'Owns the noodle stall next door.'],
        ])->map(function ($r) {
            $orders = self::orders()->where('customer_id', $r[0]);

            return [
                'id' => $r[0], 'name' => $r[1], 'phone' => $r[2], 'email' => $r[3], 'address' => $r[4],
                'status' => $r[5], 'last_visit' => $r[6], 'notes' => $r[7],
                'orders_count' => $orders->count() + $r[0] * 3,
                'total_spent' => round($orders->sum('total') + $r[0] * 41.5, 2),
            ];
        });
    }

    public static function customer(int $id): array
    {
        return self::customers()->firstWhere('id', $id) ?? abort(404);
    }

    // ---------------------------------------------------------------- orders

    public static function orders(): Collection
    {
        $vat = config('vpos.vat_rate', 10);

        // id, customer, cashier, method, status, payment status, minutes ago, discount %, [[product, qty]...]
        $rows = [
            [1048, 1, 'Sreymao Lim', 'khqr', 'completed', 'paid', 12, 0, [[1, 6], [3, 2], [7, 3]]],
            [1047, null, 'Sreymao Lim', 'cash', 'completed', 'paid', 38, 0, [[5, 10], [13, 1]]],
            [1046, 2, 'Sreymao Lim', 'cash', 'completed', 'paid', 61, 5, [[9, 1], [10, 1], [12, 1]]],
            [1045, null, 'Dara Chum', 'card', 'completed', 'paid', 95, 0, [[2, 4], [8, 3], [16, 1]]],
            [1044, 3, 'Dara Chum', 'khqr', 'partially_refunded', 'paid', 140, 10, [[9, 2], [13, 2], [15, 1]]],
            [1043, null, 'Dara Chum', 'cash', 'completed', 'paid', 188, 0, [[3, 6], [4, 2]]],
            [1042, 6, 'Sreymao Lim', 'khqr', 'completed', 'paid', 240, 0, [[5, 20], [10, 2]]],
            [1041, null, 'Sreymao Lim', 'cash', 'refunded', 'refunded', 300, 0, [[14, 2]]],
            [1040, 4, 'Dara Chum', 'cash', 'pending', 'unpaid', 360, 0, [[11, 2], [12, 1]]],
            [1039, null, 'Sreymao Lim', 'card', 'cancelled', 'unpaid', 420, 0, [[7, 2]]],
        ];

        return collect($rows)->map(function ($r) use ($vat) {
            $customer = $r[1] ? self::customerName($r[1]) : 'Walk-in customer';
            $items = collect($r[8])->map(function ($pair) {
                $p = self::product($pair[0]);

                return ['product_id' => $p['id'], 'name' => $p['name'], 'emoji' => $p['emoji'], 'sku' => $p['sku'], 'price' => $p['price'], 'cost' => $p['cost'], 'quantity' => $pair[1], 'line_total' => round($p['price'] * $pair[1], 2)];
            });
            $subtotal = $items->sum('line_total');
            $discount = round($subtotal * $r[7] / 100, 2);
            $vatAmount = round(($subtotal - $discount) * $vat / 100, 2);

            return [
                'id' => $r[0], 'number' => '#INV-'.$r[0], 'customer_id' => $r[1], 'customer' => $customer,
                'cashier' => $r[2], 'method' => $r[3], 'status' => $r[4], 'payment_status' => $r[5],
                'placed_at' => now()->subMinutes($r[6]),
                'items' => $items->all(), 'items_count' => $items->sum('quantity'),
                'subtotal' => $subtotal, 'discount_percent' => $r[7], 'discount' => $discount,
                'vat' => $vatAmount, 'total' => round($subtotal - $discount + $vatAmount, 2),
            ];
        });
    }

    public static function order(int $id): array
    {
        return self::orders()->firstWhere('id', $id) ?? abort(404);
    }

    private static function customerNames(): array
    {
        return [1 => 'Sok Dara', 2 => 'Chan Sreyneang', 3 => 'Vanna Pich', 4 => 'Kim Sophea', 5 => 'Heng Visal', 6 => 'Chea Srey Mom'];
    }

    private static function customerName(int $id): string
    {
        return self::customerNames()[$id];
    }

    // ---------------------------------------------------------------- inventory

    public static function stockMovements(): Collection
    {
        // id, product, type, signed quantity, balance, reference, user, note, minutes ago
        return collect([
            [1, 5, 'sale', -10, 15, '#INV-1047', 'Sreymao Lim', '', 38],
            [2, 14, 'adjustment', -4, 3, 'ADJ-0031', 'Rithy Heng', 'Expired milk removed', 150],
            [3, 1, 'stock_in', 120, 240, 'PO-0212', 'Rithy Heng', 'Angkor Beer delivery', 320],
            [4, 2, 'stock_in', 96, 180, 'PO-0212', 'Rithy Heng', 'Coca-Cola delivery', 322],
            [5, 15, 'sale', -1, 0, '#INV-1044', 'Dara Chum', '', 140],
            [6, 13, 'return', 1, 40, '#INV-1044', 'Dara Chum', 'Customer return', 120],
            [7, 7, 'stock_out', -6, 8, 'OUT-0008', 'Rithy Heng', 'Damaged packaging', 600],
            [8, 9, 'stock_in', 20, 36, 'PO-0209', 'Rithy Heng', 'Rice supplier', 1440],
            [9, 4, 'sale', -2, 14, '#INV-1043', 'Dara Chum', '', 188],
        ])->map(fn ($r) => [
            'id' => $r[0], 'product' => self::product($r[1]), 'type' => $r[2], 'quantity' => $r[3],
            'balance' => $r[4], 'reference' => $r[5], 'user' => $r[6], 'note' => $r[7], 'at' => now()->subMinutes($r[8]),
        ]);
    }

    // ---------------------------------------------------------------- finance

    public static function expenseCategories(): array
    {
        return ['Rent', 'Electricity & Water', 'Salaries', 'Transport', 'Supplies', 'Marketing', 'Maintenance', 'Other'];
    }

    public static function expenses(): Collection
    {
        return collect([
            [1, 'Nov 1, 2026', 'Rent', 'Shop rent for November', 450.00, 'cash', 'Rithy Heng'],
            [2, 'Nov 2, 2026', 'Electricity & Water', 'EDC electricity bill (October)', 138.40, 'khqr', 'Rithy Heng'],
            [3, 'Nov 3, 2026', 'Salaries', 'Part-time cashier wage', 220.00, 'cash', 'Rithy Heng'],
            [4, 'Nov 4, 2026', 'Transport', 'Tuk-tuk delivery to wholesaler', 12.50, 'cash', 'Dara Chum'],
            [5, 'Nov 4, 2026', 'Supplies', 'Plastic bags and receipt paper', 28.75, 'cash', 'Dara Chum'],
            [6, 'Nov 5, 2026', 'Marketing', 'Facebook page promotion', 15.00, 'card', 'Rithy Heng'],
            [7, 'Nov 5, 2026', 'Maintenance', 'Fridge repair', 35.00, 'cash', 'Rithy Heng'],
        ])->map(fn ($r) => [
            'id' => $r[0], 'date' => $r[1], 'category' => $r[2], 'description' => $r[3],
            'amount' => $r[4], 'method' => $r[5], 'created_by' => $r[6],
        ]);
    }

    public static function payments(): Collection
    {
        return self::orders()
            ->filter(fn ($o) => in_array($o['payment_status'], ['paid', 'refunded'], true))
            ->map(fn ($o) => [
                'id' => $o['id'], 'at' => $o['placed_at'], 'order' => $o['number'], 'customer' => $o['customer'],
                'method' => $o['method'], 'amount' => $o['total'], 'status' => $o['payment_status'],
                'reference' => strtoupper($o['method']).'-'.($o['id'] * 7 + 1200),
            ])->values();
    }

    public static function cashMovements(): Collection
    {
        return collect([
            ['9:00 AM', 'in', 'Opening float', 200.00, 'Rithy Heng'],
            ['11:20 AM', 'out', 'Pay delivery driver', 15.00, 'Sreymao Lim'],
            ['1:45 PM', 'in', 'Top up change (small notes)', 50.00, 'Rithy Heng'],
            ['3:10 PM', 'out', 'Buy ice for cooler', 4.00, 'Dara Chum'],
        ])->map(fn ($r) => ['time' => $r[0], 'type' => $r[1], 'reason' => $r[2], 'amount' => $r[3], 'user' => $r[4]]);
    }

    public static function dailyCashSummary(): array
    {
        return [
            'opening_float' => 200.00, 'cash_sales' => 426.25, 'khqr_sales' => 612.40, 'card_sales' => 245.85,
            'cash_in' => 50.00, 'cash_out' => 19.00, 'expected_in_drawer' => 657.25, 'counted' => 657.25,
        ];
    }

    // ---------------------------------------------------------------- dashboard & reports

    public static function dashboard(): array
    {
        return [
            'stats' => [
                'todays_sales' => 1284.50, 'todays_sales_delta' => 12.6,
                'total_revenue' => 38920.75, 'total_revenue_delta' => 8.1,
                'estimated_profit' => 9730.20, 'estimated_profit_delta' => 5.4,
                'total_expenses' => 7850.40, 'total_expenses_delta' => -2.3,
            ],
            'charts' => [
                'today' => self::series(['8a', '10a', '12p', '2p', '4p', '6p', '8p'], [60, 120, 310, 190, 280, 340, 150], [48, 96, 270, 210, 240, 300, 120]),
                'week' => self::series(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], [820, 760, 910, 880, 1180, 1520, 1284], [740, 800, 850, 910, 1050, 1390, 1220]),
                'month' => self::series(['Wk 1', 'Wk 2', 'Wk 3', 'Wk 4'], [6420, 7180, 6890, 8260], [5980, 6540, 7010, 7420]),
            ],
        ];
    }

    private static function series(array $labels, array $values, array $previous): array
    {
        return collect($labels)->map(fn ($label, $i) => ['label' => $label, 'value' => $values[$i], 'previous' => $previous[$i]])->all();
    }

    public static function bestSellers(int $limit = 5): Collection
    {
        return self::products()->sortByDesc('sold')->take($limit)->values();
    }

    public static function reports(): array
    {
        return [
            'sales' => [
                ['Nov 2', 41, 912.40, 0.0], ['Nov 3', 48, 1043.15, 12.50], ['Nov 4', 39, 884.60, 0.0],
                ['Nov 5', 52, 1284.50, 8.20], ['Nov 6', 44, 976.30, 0.0], ['Nov 7', 63, 1520.85, 15.00],
            ],
            'revenue' => [
                ['Week 1', 6420.00, 4780.00, 1640.00], ['Week 2', 7180.00, 5310.00, 1870.00],
                ['Week 3', 6890.00, 5120.00, 1770.00], ['Week 4', 8260.00, 6050.00, 2210.00],
            ],
            'expenses' => [
                ['Rent', 450.00], ['Salaries', 220.00], ['Electricity & Water', 138.40], ['Maintenance', 35.00],
                ['Supplies', 28.75], ['Marketing', 15.00], ['Transport', 12.50],
            ],
        ];
    }

    // ---------------------------------------------------------------- people & settings

    public static function employees(): Collection
    {
        return collect([
            [1, 'Rithy Heng', 'rithy@vpos.test', '+855 12 700 100', 'Admin', 'active', 'Today, 8:42 AM'],
            [2, 'Sreymao Lim', 'sreymao@vpos.test', '+855 96 700 200', 'Cashier', 'active', 'Today, 8:55 AM'],
            [3, 'Dara Chum', 'dara@vpos.test', '+855 77 700 300', 'Cashier', 'active', 'Yesterday, 5:10 PM'],
            [4, 'Bopha Ouk', 'bopha@vpos.test', '+855 10 700 400', 'Manager', 'active', 'Nov 4, 3:20 PM'],
            [5, 'Sokha Meas', 'sokha@vpos.test', '+855 69 700 500', 'Cashier', 'inactive', 'Sep 30, 6:00 PM'],
        ])->map(fn ($r) => ['id' => $r[0], 'name' => $r[1], 'email' => $r[2], 'phone' => $r[3], 'role' => $r[4], 'status' => $r[5], 'last_login' => $r[6]]);
    }

    public static function roles(): array
    {
        $modules = ['Dashboard', 'POS / Checkout', 'Products', 'Categories', 'Inventory', 'Orders & Refunds', 'Customers', 'Expenses', 'Payments', 'Reports', 'Employees', 'Settings'];
        $actions = ['view', 'create', 'edit', 'delete'];

        // module => [Admin, Manager, Cashier] allowed actions
        $grants = [
            'Dashboard' => [$actions, ['view'], []],
            'POS / Checkout' => [$actions, ['view', 'create'], ['view', 'create']],
            'Products' => [$actions, ['view', 'create', 'edit'], ['view']],
            'Categories' => [$actions, ['view', 'create', 'edit'], ['view']],
            'Inventory' => [$actions, ['view', 'create', 'edit'], ['view']],
            'Orders & Refunds' => [$actions, ['view', 'edit'], ['view']],
            'Customers' => [$actions, ['view', 'create', 'edit'], ['view', 'create']],
            'Expenses' => [$actions, ['view', 'create', 'edit'], []],
            'Payments' => [$actions, ['view'], []],
            'Reports' => [$actions, ['view'], []],
            'Employees' => [$actions, ['view'], []],
            'Settings' => [$actions, [], []],
        ];

        return [
            'names' => ['Admin', 'Manager', 'Cashier'],
            'descriptions' => ['Full access to every module', 'Runs the shop day to day', 'Works the checkout counter'],
            'modules' => $modules,
            'actions' => $actions,
            'grants' => $grants,
        ];
    }

    public static function settings(): array
    {
        return [
            'business' => ['name' => 'Sala Mini Mart', 'phone' => '+855 23 888 999', 'email' => 'hello@salamart.example', 'address' => 'No. 128, Street 310, Boeung Keng Kang 1, Phnom Penh', 'vat_number' => 'K001-901234567'],
            'currency' => ['primary' => 'USD', 'secondary' => 'KHR', 'rate' => 4100, 'vat_enabled' => true, 'vat_rate' => 10, 'prices_include_vat' => false],
            'receipt' => ['header' => 'Thank you for shopping with us!', 'footer' => 'Goods sold are not returnable after 3 days.', 'paper' => '80mm', 'show_logo' => true, 'show_khr' => true, 'auto_print' => false],
            'payments' => [
                ['key' => 'cash', 'label' => 'Cash', 'enabled' => true, 'hint' => 'Count the cash drawer at the end of every shift.'],
                ['key' => 'khqr', 'label' => 'KHQR (Bakong)', 'enabled' => true, 'hint' => 'Customers scan the QR with any Cambodian banking app.'],
                ['key' => 'card', 'label' => 'Card', 'enabled' => false, 'hint' => 'Requires a connected card terminal.'],
            ],
            'khqr' => ['account_name' => 'SALA MINI MART', 'bakong_id' => 'salamart@aba', 'merchant_city' => 'Phnom Penh'],
        ];
    }
}
