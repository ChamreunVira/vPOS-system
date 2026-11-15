<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt - {{ $order->order_number }}</title>
    <style>
        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            background: #f4f4f5;
            color: #18181b;
            margin: 0;
            padding: 20px;
        }
        .receipt-box {
            max-width: 320px;
            margin: 0 auto;
            background: #fff;
            padding: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.4;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #71717a; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; }
        .items-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .items-table th { text-align: left; border-bottom: 1px solid #e4e4e7; padding-bottom: 4px; }
        .items-table td { padding: 4px 0; vertical-align: top; }
        .btn-print {
            display: inline-block;
            background: #18181b;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            margin-bottom: 16px;
            cursor: pointer;
            border: none;
        }
    </style>
</head>
<body>
    <div class="center no-print">
        <button onclick="window.print()" class="btn-print">Print Receipt</button>
    </div>

    <div class="receipt-box">
        <div class="center">
            <h2 class="bold" style="margin: 0; font-size: 16px;">{{ config('app.name', 'vPOS') }}</h2>
            <p style="margin: 4px 0; color: #71717a;">POS & Mini Mart System</p>
            <p style="margin: 2px 0;">Invoice: <span class="bold">{{ $order->order_number }}</span></p>
            <p style="margin: 2px 0; color: #71717a;">{{ $order->placed_at->format('d/m/Y H:i:s') }}</p>
            <p style="margin: 2px 0; color: #71717a;">Cashier: {{ $order->cashier_name }}</p>
        </div>

        <div class="divider"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">${{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="right">${{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="row">
            <span>Subtotal:</span>
            <span class="bold">${{ number_format((float) $order->subtotal, 2) }}</span>
        </div>

        @if ((float) $order->discount_amount > 0)
            <div class="row">
                <span>Discount ({{ $order->discount_percent }}%):</span>
                <span>-${{ number_format((float) $order->discount_amount, 2) }}</span>
            </div>
        @endif

        <div class="row">
            <span>VAT ({{ $order->tax_rate }}%):</span>
            <span>${{ number_format((float) $order->tax_amount, 2) }}</span>
        </div>

        <div class="divider"></div>

        <div class="row" style="font-size: 14px;">
            <span class="bold">TOTAL USD:</span>
            <span class="bold">${{ number_format((float) $order->total, 2) }}</span>
        </div>
        <div class="row" style="color: #71717a;">
            <span>TOTAL KHR:</span>
            <span>៛{{ number_format((float) $order->total * 4100, 0) }}</span>
        </div>

        <div class="divider"></div>

        <div class="row">
            <span>Payment Method:</span>
            <span>{{ strtoupper($order->payment_method) }}</span>
        </div>
        <div class="row">
            <span>Payment Status:</span>
            <span class="bold">{{ strtoupper($order->payment_status) }}</span>
        </div>

        <div class="divider"></div>

        <div class="center" style="margin-top: 12px; color: #71717a;">
            <p style="margin: 2px 0;">Thank you for shopping with us!</p>
            <p style="margin: 2px 0; font-size: 10px;">Please keep this receipt for exchange/refund within 3 days.</p>
        </div>
    </div>
</body>
</html>
