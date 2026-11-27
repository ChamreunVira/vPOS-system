<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));
        $status = $request->input('status');
        $method = $request->input('method');
        $date = $request->input('date');

        $paymentQuery = Payment::query()
            ->with(['order.customer', 'paymentMethod'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('reference', 'like', '%' . $search . '%')
                        ->orWhereHas('order', function (Builder $orderQuery) use ($search): void {
                            $orderQuery->where('order_number', 'like', '%' . $search . '%')
                                ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                                    $customerQuery->where('name', 'like', '%' . $search . '%')
                                        ->orWhere('phone', 'like', '%' . $search . '%');
                                });
                        });
                });
            })
            ->when($status && $status !== 'all', fn(Builder $query) => $query->where('status', $status))
            ->when($method && $method !== 'all', function (Builder $query) use ($method): void {
                $query->whereHas('paymentMethod', fn(Builder $q) => $q->where('code', $method));
            })
            ->when($date, function (Builder $query) use ($date): void {
                $query->where(function (Builder $q) use ($date): void {
                    $q->whereDate('paid_at', $date)
                        ->orWhere(fn(Builder $sq) => $sq->whereNull('paid_at')->whereDate('created_at', $date));
                });
            })
            ->orderByDesc('id');

        $payments = $paymentQuery->paginate(15, ['*'], 'payments_page')->withQueryString();

        $cashMovements = CashMovement::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'cash_page')
            ->withQueryString();

        $today = now()->toDateString();

        $todayPayments = Payment::query()
            ->with('paymentMethod')
            ->where(function (Builder $q) use ($today): void {
                $q->whereDate('paid_at', $today)
                    ->orWhere(fn(Builder $sq) => $sq->whereNull('paid_at')->whereDate('created_at', $today));
            })
            ->where('status', 'paid')
            ->get();

        $cashSales = (float) $todayPayments->filter(fn(Payment $p) => $p->paymentMethod?->code === 'cash')->sum('amount');
        $khqrSales = (float) $todayPayments->filter(fn(Payment $p) => $p->paymentMethod?->code === 'khqr')->sum('amount');
        $cardSales = (float) $todayPayments->filter(fn(Payment $p) => $p->paymentMethod?->code === 'card')->sum('amount');

        $todayMovements = CashMovement::query()
            ->whereDate('created_at', $today)
            ->get();

        $cashIn = (float) $todayMovements->where('type', 'in')->sum('amount');
        $cashOut = (float) $todayMovements->where('type', 'out')->sum('amount');

        $openingFloat = (float) (CashSession::query()->whereDate('opened_at', $today)->value('opening_float') ?? 0.00);
        $expectedInDrawer = $openingFloat + $cashSales + $cashIn - $cashOut;
        $counted = (float) (CashSession::query()->whereDate('closed_at', $today)->value('closing_amount') ?? $expectedInDrawer);

        $summary = [
            'opening_float' => $openingFloat,
            'cash_sales' => $cashSales,
            'khqr_sales' => $khqrSales,
            'card_sales' => $cardSales,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'expected_in_drawer' => $expectedInDrawer,
            'counted' => $counted,
        ];

        $paymentMethods = PaymentMethod::query()->get();
        $paymentMethodOptions = $paymentMethods->where('is_enable', true)->pluck('name', 'code')->all();

        return view('payments.index', [
            'payments' => $payments,
            'cashMovements' => $cashMovements,
            'summary' => $summary,
            'paymentMethods' => $paymentMethods,
            'paymentMethodOptions' => $paymentMethodOptions,
        ]);
    }

    public function cashMovement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        CashMovement::create([
            'user_id' => $request->user()->id,
            'cash_session_id' => null,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        $label = $validated['type'] === 'in' ? 'Cash in' : 'Cash out';

        return back()->with('success', "{$label} recorded successfully.");
    }
}
