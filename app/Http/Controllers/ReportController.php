<?php

namespace App\Http\Controllers;

use App\Support\MockData;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'reports' => MockData::reports(),
            'products' => MockData::products(),
        ]);
    }
}
