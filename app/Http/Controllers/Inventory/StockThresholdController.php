<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockThreshold\StockThreshold;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StockThresholdController extends Controller
{
    public function index(): View
    {
        return view('inventory/stock-thresholds/index');
    }

    public function create(): View
    {
        return view('inventory/stock-thresholds/edit')
            ->with('item', new StockThreshold());
    }

    public function store(Request $request): RedirectResponse
    {
        $threshold = new StockThreshold();
        $threshold->fill($request->all());
        $threshold->created_by = auth()->id();
        $threshold->updated_by = auth()->id();

        if ($threshold->save()) {
            return redirect()->route('inventory.stock-thresholds.index')
                ->with('success', trans('admin/inventory/stock-thresholds/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($threshold->getErrors());
    }

    public function show(StockThreshold $stockThreshold): View
    {
        return view('inventory/stock-thresholds/view')
            ->with('threshold', $stockThreshold);
    }

    public function edit(StockThreshold $stockThreshold): View
    {
        return view('inventory/stock-thresholds/edit')
            ->with('item', $stockThreshold);
    }

    public function update(Request $request, StockThreshold $stockThreshold): RedirectResponse
    {
        $stockThreshold->fill($request->all());
        $stockThreshold->updated_by = auth()->id();

        if ($stockThreshold->save()) {
            return redirect()->route('inventory.stock-thresholds.index')
                ->with('success', trans('admin/inventory/stock-thresholds/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($stockThreshold->getErrors());
    }

    public function destroy(StockThreshold $stockThreshold): RedirectResponse
    {
        $stockThreshold->delete();
        return redirect()->route('inventory.stock-thresholds.index')
            ->with('success', trans('admin/inventory/stock-thresholds/message.delete.success'));
    }
}
