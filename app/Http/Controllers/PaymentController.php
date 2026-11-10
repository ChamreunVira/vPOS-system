<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('payments.index', [
            'payments' => MockData::payments(),
            'cashMovements' => MockData::cashMovements(),
            'summary' => MockData::dailyCashSummary(),
        ]);
    }

    public function cashMovement(Request $request)
    {
        return $this->demo('Cash movement recorded');
    }
}
