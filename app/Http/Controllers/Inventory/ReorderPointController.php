<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ReorderPoint\ReorderPoint;
use App\Services\Inventory\ReorderPointEngine;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReorderPointController extends Controller
{
    protected ReorderPointEngine $reorderPointEngine;

    public function __construct(ReorderPointEngine $reorderPointEngine)
    {
        $this->reorderPointEngine = $reorderPointEngine;
    }

    public function index(): View
    {
        return view('inventory/reorder-points/index');
    }

    public function create(): View
    {
        return view('inventory/reorder-points/edit')
            ->with('item', new ReorderPoint());
    }

    public function store(Request $request): RedirectResponse
    {
        $reorderPoint = new ReorderPoint();
        $reorderPoint->fill($request->all());
        $reorderPoint->created_by = auth()->id();
        $reorderPoint->updated_by = auth()->id();

        if ($reorderPoint->save()) {
            return redirect()->route('inventory.reorder-points.index')
                ->with('success', trans('admin/inventory/reorder-points/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($reorderPoint->getErrors());
    }

    public function show(ReorderPoint $reorderPoint): View
    {
        return view('inventory/reorder-points/view')
            ->with('reorderPoint', $reorderPoint);
    }

    public function edit(ReorderPoint $reorderPoint): View
    {
        return view('inventory/reorder-points/edit')
            ->with('item', $reorderPoint);
    }

    public function update(Request $request, ReorderPoint $reorderPoint): RedirectResponse
    {
        $reorderPoint->fill($request->all());
        $reorderPoint->updated_by = auth()->id();

        if ($reorderPoint->save()) {
            return redirect()->route('inventory.reorder-points.show', $reorderPoint)
                ->with('success', trans('admin/inventory/reorder-points/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($reorderPoint->getErrors());
    }

    public function destroy(ReorderPoint $reorderPoint): RedirectResponse
    {
        $reorderPoint->delete();
        
        return redirect()->route('inventory.reorder-points.index')
            ->with('success', trans('admin/inventory/reorder-points/message.delete.success'));
    }

    public function report(): View
    {
        $report = $this->reorderPointEngine->generateReorderReport();
        
        return view('inventory/reorder-points/report')
            ->with('report', $report);
    }
}
