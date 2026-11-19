<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $range] = $this->resolveDateRange($request);

        $reports = [
            'sales' => $this->buildSalesReport($from, $to),
            'revenue' => $this->buildRevenueReport($from, $to),
            'expenses' => $this->buildExpensesReport($from, $to),
        ];

        $products = Product::query()
            ->with(['category', 'unit'])
            ->orderBy('name')
            ->get();

        return view('reports.index', [
            'reports' => $reports,
            'products' => $products,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
            'currentRange' => $range,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveDateRange($request);

        $sales = $this->buildSalesReport($from, $to);
        $revenue = $this->buildRevenueReport($from, $to);
        $expenses = $this->buildExpensesReport($from, $to);

        $filename = 'reports-export-'.$from->format('Ymd').'-to-'.$to->format('Ymd').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($sales, $revenue, $expenses): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['--- DAILY SALES REPORT ---']);
            fputcsv($handle, ['Date', 'Orders Count', 'Net Sales ($)', 'Discounts ($)']);
            foreach ($sales as $row) {
                fputcsv($handle, [$row[0], $row[1], number_format($row[2], 2, '.', ''), number_format($row[3], 2, '.', '')]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['--- REVENUE & PROFIT REPORT ---']);
            fputcsv($handle, ['Period', 'Revenue ($)', 'Cost of Goods ($)', 'Gross Profit ($)', 'Margin (%)']);
            foreach ($revenue as $row) {
                $margin = $row[1] > 0 ? round(($row[3] / $row[1]) * 100) : 0;
                fputcsv($handle, [
                    $row[0],
                    number_format($row[1], 2, '.', ''),
                    number_format($row[2], 2, '.', ''),
                    number_format($row[3], 2, '.', ''),
                    $margin.'%',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['--- EXPENSES BY CATEGORY ---']);
            fputcsv($handle, ['Category', 'Amount ($)']);
            foreach ($expenses as $row) {
                fputcsv($handle, [$row[0], number_format($row[1], 2, '.', '')]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolveDateRange(Request $request): array
    {
        $range = $request->input('range', '7d');
        $now = now();

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse((string) $request->input('from'))->startOfDay();
            $to = Carbon::parse((string) $request->input('to'))->endOfDay();
        } else {
            [$from, $to] = match ($range) {
                'today' => [$now->clone()->startOfDay(), $now->clone()->endOfDay()],
                '30d' => [$now->clone()->subDays(29)->startOfDay(), $now->clone()->endOfDay()],
                'month' => [$now->clone()->startOfMonth()->startOfDay(), $now->clone()->endOfDay()],
                default => [$now->clone()->subDays(6)->startOfDay(), $now->clone()->endOfDay()],
            };
        }

        if ($from->isAfter($to)) {
            [$from, $to] = [$to->clone()->startOfDay(), $from->clone()->endOfDay()];
        }

        return [$from, $to, $range];
    }

    /**
     * @return array<int, array{0: string, 1: int, 2: float, 3: float}>
     */
    protected function buildSalesReport(Carbon $from, Carbon $to): array
    {
        $orders = Order::query()
            ->where(function (Builder $query) use ($from, $to): void {
                $query->whereBetween('place_at', [$from, $to])
                    ->orWhere(fn (Builder $q) => $q->whereNull('place_at')->whereBetween('created_at', [$from, $to]));
            })
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $period = CarbonPeriod::create($from->toDateString(), $to->toDateString());
        $sales = [];

        foreach ($period as $date) {
            $dateString = $date->toDateString();
            $dayOrders = $orders->filter(function (Order $order) use ($dateString): bool {
                $orderDate = ($order->place_at ?? $order->created_at)?->toDateString();

                return $orderDate === $dateString;
            });

            $ordersCount = $dayOrders->count();
            $netSales = (float) $dayOrders->sum('total');
            $discounts = (float) $dayOrders->sum('discount_amount');

            $sales[] = [
                $date->format('M j'),
                $ordersCount,
                $netSales,
                $discounts,
            ];
        }

        return $sales;
    }

    /**
     * @return array<int, array{0: string, 1: float, 2: float, 3: float}>
     */
    protected function buildRevenueReport(Carbon $from, Carbon $to): array
    {
        $orders = Order::query()
            ->with(['items.product'])
            ->where(function (Builder $query) use ($from, $to): void {
                $query->whereBetween('place_at', [$from, $to])
                    ->orWhere(fn (Builder $q) => $q->whereNull('place_at')->whereBetween('created_at', [$from, $to]));
            })
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $diffInDays = $from->diffInDays($to) + 1;

        if ($diffInDays > 14) {
            // Group by 7-day chunks / weeks
            $revenue = [];
            $currentStart = $from->clone();
            $weekNumber = 1;

            while ($currentStart->lte($to)) {
                $currentEnd = $currentStart->clone()->addDays(6)->endOfDay();
                if ($currentEnd->gt($to)) {
                    $currentEnd = $to->clone();
                }

                $weekOrders = $orders->filter(function (Order $order) use ($currentStart, $currentEnd): bool {
                    $orderDate = $order->place_at ?? $order->created_at;

                    return $orderDate && $orderDate->betweenIncluded($currentStart, $currentEnd);
                });

                $rev = (float) $weekOrders->sum('total');
                $cost = 0.0;
                foreach ($weekOrders as $order) {
                    foreach ($order->items as $item) {
                        $unitCost = (float) ($item->unit_cost > 0 ? $item->unit_cost : ($item->product?->cost ?? 0));
                        $cost += (float) $item->quantity * $unitCost;
                    }
                }
                $profit = $rev - $cost;

                $revenue[] = [
                    'Week '.$weekNumber.' ('.$currentStart->format('M j').' - '.$currentEnd->format('M j').')',
                    $rev,
                    $cost,
                    $profit,
                ];

                $currentStart = $currentEnd->clone()->addSecond()->startOfDay();
                $weekNumber++;
            }

            return $revenue;
        }

        // 14 days or less: breakdown by day
        $period = CarbonPeriod::create($from->toDateString(), $to->toDateString());
        $revenue = [];

        foreach ($period as $date) {
            $dateString = $date->toDateString();
            $dayOrders = $orders->filter(function (Order $order) use ($dateString): bool {
                $orderDate = ($order->place_at ?? $order->created_at)?->toDateString();

                return $orderDate === $dateString;
            });

            $rev = (float) $dayOrders->sum('total');
            $cost = 0.0;
            foreach ($dayOrders as $order) {
                foreach ($order->items as $item) {
                    $unitCost = (float) ($item->unit_cost > 0 ? $item->unit_cost : ($item->product?->cost ?? 0));
                    $cost += (float) $item->quantity * $unitCost;
                }
            }
            $profit = $rev - $cost;

            $revenue[] = [
                $date->format('M j'),
                $rev,
                $cost,
                $profit,
            ];
        }

        return $revenue;
    }

    /**
     * @return array<int, array{0: string, 1: float}>
     */
    protected function buildExpensesReport(Carbon $from, Carbon $to): array
    {
        $expenseCategories = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where(function (Builder $query) use ($from, $to): void {
                $query->whereBetween('expenses.expense_date', [
                    $from->copy()->startOfDay()->toDateTimeString(),
                    $to->copy()->endOfDay()->toDateTimeString(),
                ])->orWhere(function (Builder $sub) use ($from, $to): void {
                    $sub->whereDate('expenses.expense_date', '>=', $from->toDateString())
                        ->whereDate('expenses.expense_date', '<=', $to->toDateString());
                });
            })
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return $expenseCategories->map(fn ($row) => [
            (string) $row->category,
            (float) $row->total,
        ])->all();
    }
}
