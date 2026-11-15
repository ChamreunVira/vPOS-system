<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $yesterday = today()->subDay();

        // 1. Sales & Revenue
        $todaySales = round((float) Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereDate('place_at', $today)
            ->sum('total'), 2);

        $yesterdaySales = round((float) Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereDate('place_at', $yesterday)
            ->sum('total'), 2);

        $todaysSalesDelta = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : ($todaySales > 0 ? 100.0 : 0.0);

        $totalRevenue = round((float) Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->sum('total'), 2);

        $thisMonthRevenue = round((float) Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereBetween('place_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total'), 2);

        $lastMonthRevenue = round((float) Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereBetween('place_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('total'), 2);

        $totalRevenueDelta = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($thisMonthRevenue > 0 ? 100.0 : 0.0);

        // 2. Estimated Profit (Gross Revenue - Cost of Goods Sold)
        $grossMargin = (float) OrderItem::query()
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['completed', 'partially_refunded']))
            ->selectRaw('COALESCE(SUM(line_total - (quantity * unit_cost)), 0) as gross')
            ->value('gross');

        $totalDiscounts = (float) Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->sum('discount_amount');

        $estimatedProfit = max(round($grossMargin - $totalDiscounts, 2), 0.0);
        $estimatedProfitDelta = $totalRevenueDelta;

        // 3. Expenses
        $totalExpenses = 0.0;
        $totalExpensesDelta = 0.0;
        if (Schema::hasTable('expenses')) {
            $totalExpenses = round((float) DB::table('expenses')->sum('amount'), 2);
        }

        // 4. Products & Categories
        $totalProducts = Product::query()->count();
        $activeCategories = Category::query()->where('status', 'active')->count();

        // 5. Low Stock Products
        $lowStockProducts = Product::query()
            ->with(['category', 'unit'])
            ->where(function ($query): void {
                $query->lowStock()->orWhere(fn ($q) => $q->outOfStock());
            })
            ->get();

        // 6. Recent Orders
        $recentOrders = Order::query()
            ->with(['customer', 'cashier', 'payments.paymentMethod'])
            ->latest('id')
            ->take(5)
            ->get();

        // 7. Best Sellers (Aggregated by real units sold)
        $bestSellerItems = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['completed', 'partially_refunded']))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with(['product.category', 'product.unit'])
            ->get();

        $bestSellers = $bestSellerItems->map(fn ($item) => [
            'id' => $item->product?->id,
            'name' => $item->product?->name ?? 'Unknown product',
            'category' => $item->product?->category?->name ?? 'Uncategorized',
            'image' => $item->product?->image,
            'sold' => (float) $item->total_sold,
        ]);

        if ($bestSellers->count() < 5) {
            $existingIds = $bestSellers->pluck('id')->filter()->all();
            $fillers = Product::query()
                ->with('category')
                ->whereNotIn('id', $existingIds)
                ->limit(5 - $bestSellers->count())
                ->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category' => $p->category?->name ?? 'Uncategorized',
                    'image' => $p->image,
                    'sold' => 0.0,
                ]);

            $bestSellers = $bestSellers->concat($fillers);
        }

        // 8. Chart Series
        $charts = [
            'today' => $this->buildTodayChartSeries(),
            'week' => $this->buildWeekChartSeries(),
            'month' => $this->buildMonthChartSeries(),
        ];

        return view('dashboard.index', [
            'stats' => [
                'todays_sales' => $todaySales,
                'todays_sales_delta' => $todaysSalesDelta,
                'total_revenue' => $totalRevenue,
                'total_revenue_delta' => $totalRevenueDelta,
                'estimated_profit' => $estimatedProfit,
                'estimated_profit_delta' => $estimatedProfitDelta,
                'total_expenses' => $totalExpenses,
                'total_expenses_delta' => $totalExpensesDelta,
            ],
            'charts' => $charts,
            'totalProducts' => $totalProducts,
            'activeCategories' => $activeCategories,
            'lowStockProducts' => $lowStockProducts,
            'recentOrders' => $recentOrders,
            'bestSellers' => $bestSellers,
        ]);
    }

    /**
     * @return array<int, array{label: string, value: float, previous: float}>
     */
    private function buildTodayChartSeries(): array
    {
        $labels = ['8a', '10a', '12p', '2p', '4p', '6p', '8p'];
        $todayValues = array_fill(0, 7, 0.0);
        $yesterdayValues = array_fill(0, 7, 0.0);

        $todayOrders = Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereDate('place_at', today())
            ->get(['total', 'place_at']);

        foreach ($todayOrders as $order) {
            $hour = (int) Carbon::parse($order->place_at)->format('H');
            $bin = $this->hourToBin($hour);
            $todayValues[$bin] += (float) $order->total;
        }

        $yesterdayOrders = Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereDate('place_at', today()->subDay())
            ->get(['total', 'place_at']);

        foreach ($yesterdayOrders as $order) {
            $hour = (int) Carbon::parse($order->place_at)->format('H');
            $bin = $this->hourToBin($hour);
            $yesterdayValues[$bin] += (float) $order->total;
        }

        return collect($labels)->map(fn ($label, $index) => [
            'label' => $label,
            'value' => round($todayValues[$index], 2),
            'previous' => round($yesterdayValues[$index], 2),
        ])->all();
    }

    /**
     * @return array<int, array{label: string, value: float, previous: float}>
     */
    private function buildWeekChartSeries(): array
    {
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $currentValues = array_fill(0, 7, 0.0);
        $previousValues = array_fill(0, 7, 0.0);

        $currentWeekOrders = Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereBetween('place_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->get(['total', 'place_at']);

        foreach ($currentWeekOrders as $order) {
            $dayIndex = (int) Carbon::parse($order->place_at)->dayOfWeekIso - 1;
            if ($dayIndex >= 0 && $dayIndex < 7) {
                $currentValues[$dayIndex] += (float) $order->total;
            }
        }

        $previousWeekOrders = Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereBetween('place_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->get(['total', 'place_at']);

        foreach ($previousWeekOrders as $order) {
            $dayIndex = (int) Carbon::parse($order->place_at)->dayOfWeekIso - 1;
            if ($dayIndex >= 0 && $dayIndex < 7) {
                $previousValues[$dayIndex] += (float) $order->total;
            }
        }

        return collect($labels)->map(fn ($label, $index) => [
            'label' => $label,
            'value' => round($currentValues[$index], 2),
            'previous' => round($previousValues[$index], 2),
        ])->all();
    }

    /**
     * @return array<int, array{label: string, value: float, previous: float}>
     */
    private function buildMonthChartSeries(): array
    {
        $labels = ['Wk 1', 'Wk 2', 'Wk 3', 'Wk 4'];
        $currentValues = array_fill(0, 4, 0.0);
        $previousValues = array_fill(0, 4, 0.0);

        $currentMonthOrders = Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereBetween('place_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get(['total', 'place_at']);

        foreach ($currentMonthOrders as $order) {
            $day = (int) Carbon::parse($order->place_at)->format('d');
            $bin = match (true) {
                $day <= 7 => 0,
                $day <= 14 => 1,
                $day <= 21 => 2,
                default => 3,
            };
            $currentValues[$bin] += (float) $order->total;
        }

        $previousMonthOrders = Order::query()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->whereBetween('place_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->get(['total', 'place_at']);

        foreach ($previousMonthOrders as $order) {
            $day = (int) Carbon::parse($order->place_at)->format('d');
            $bin = match (true) {
                $day <= 7 => 0,
                $day <= 14 => 1,
                $day <= 21 => 2,
                default => 3,
            };
            $previousValues[$bin] += (float) $order->total;
        }

        return collect($labels)->map(fn ($label, $index) => [
            'label' => $label,
            'value' => round($currentValues[$index], 2),
            'previous' => round($previousValues[$index], 2),
        ])->all();
    }

    private function hourToBin(int $hour): int
    {
        return match (true) {
            $hour < 9 => 0,
            $hour < 11 => 1,
            $hour < 13 => 2,
            $hour < 15 => 3,
            $hour < 17 => 4,
            $hour < 19 => 5,
            default => 6,
        };
    }
}
