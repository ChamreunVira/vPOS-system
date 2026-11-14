<?php

namespace App\Http\Controllers;

use App\Enums\SoftDeleteStatus;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::query()->withCount(['productUnits as products_count'])->orderBy('name')->get();

        return view('units.index', ['units' => $units]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        Unit::create($data);

        return redirect()->route('units.index')->with('status', 'Unit created.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $unit->update($this->validatedData($request));

        return redirect()->route('units.index')->with('status', 'Unit updated.');
    }

    /**
     * @return array{name: string, abbreviation: string, type: string, allow_decimal: bool, status: string}
     */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'allow_decimal' => ['required', 'boolean'],
            'status' => ['required', 'string', 'in:'.implode(',', SoftDeleteStatus::all())],
        ]);

        $data['allow_decimal'] = $request->boolean('allow_decimal');

        return $data;
    }
}
