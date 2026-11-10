<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('settings.index', ['settings' => MockData::settings()]);
    }

    public function update(Request $request)
    {
        return $this->demo('Settings saved');
    }
}
