<?php

namespace App\Http\Controllers;

use App\Support\MockData;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $data = MockData::dashboard();

        return view('dashboard.index', [
            'stats' => $data['stats'],
            'charts' => $data['charts'],
            'totalProducts' => MockData::products()->count(),
            'activeCategories' => MockData::categories()->where('status', 'active')->count(),
            'lowStockProducts' => MockData::lowStockProducts(),
            'recentOrders' => MockData::orders()->take(5),
            'bestSellers' => MockData::bestSellers(),
        ]);
    }
}
