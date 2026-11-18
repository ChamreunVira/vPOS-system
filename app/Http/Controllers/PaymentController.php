<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Models\Payment;
use App\Support\MockData;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {

        $payments = Payment::query()
            ->with(['order', 'paymentMethod', 'proccessor'])
            ->get();
        // dd($payments->toArray());

        return view('payments.index', [
            'payments' => $payments,
            'cashMovements' => MockData::cashMovements(),
            'summary' => MockData::dailyCashSummary(),
        ]);
    }

    public function store() {}

    public function cashMovement(Request $request)
    {
        return $this->demo('Cash movement recorded');
    }
}
