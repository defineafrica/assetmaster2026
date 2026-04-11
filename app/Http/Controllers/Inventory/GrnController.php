<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Models\Inventory\GoodsReceivedNote\GrnItem;
use App\Models\Inventory\GoodsReceivedNote\GrnInspection;
use App\Services\Inventory\GrnService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class GrnController extends Controller
{
    protected GrnService $grnService;

    public function __construct(GrnService $grnService)
    {
        $this->grnService = $grnService;
    }

    public function index(): View
    {
        return view('inventory/grn/index');
    }

    public function create(): View
    {
        $grn = new GoodsReceivedNote();
        $grn->grn_number = GoodsReceivedNote::generateGrnNumber();
        $grn->received_date = now()->format('Y-m-d');
        $grn->status = GoodsReceivedNote::STATUS_DRAFT;
        
        return view('inventory/grn/edit')
            ->with('item', $grn)
            ->with('item_type', 'create');
    }

    public function store(Request $request): RedirectResponse
    {
        $grn = new GoodsReceivedNote();
        $grn->fill($request->all());
        $grn->grn_number = $request->input('grn_number', GoodsReceivedNote::generateGrnNumber());
        $grn->status = GoodsReceivedNote::STATUS_DRAFT;
        $grn->created_by = auth()->id();
        $grn->updated_by = auth()->id();

        if ($grn->save()) {
            return redirect()->route('inventory.grn.edit', $grn)
                ->with('success', trans('admin/inventory/grn/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($grn->getErrors());
    }

    public function show(GoodsReceivedNote $grn): View
    {
        $grn->load(['items', 'inspections', 'supplier', 'receiver', 'approver']);
        
        return view('inventory/grn/view')
            ->with('grn', $grn);
    }

    public function edit(GoodsReceivedNote $grn): View
    {
        $grn->load(['items', 'supplier']);
        
        return view('inventory/grn/edit')
            ->with('item', $grn)
            ->with('item_type', 'edit');
    }

    public function update(Request $request, GoodsReceivedNote $grn): RedirectResponse
    {
        if (!$grn->isEditable()) {
            return redirect()->route('inventory.grn.show', $grn)
                ->with('error', trans('admin/inventory/grn/message.error.not_editable'));
        }

        $grn->fill($request->all());
        $grn->updated_by = auth()->id();

        if ($grn->save()) {
            return redirect()->route('inventory.grn.show', $grn)
                ->with('success', trans('admin/inventory/grn/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($grn->getErrors());
    }

    public function destroy(GoodsReceivedNote $grn): RedirectResponse
    {
        if (!$grn->isEditable()) {
            return redirect()->route('inventory.grn.index')
                ->with('error', trans('admin/inventory/grn/message.error.not_deletable'));
        }

        $grn->delete();
        
        return redirect()->route('inventory.grn.index')
            ->with('success', trans('admin/inventory/grn/message.delete.success'));
    }

    public function post(Request $request, GoodsReceivedNote $grn): RedirectResponse
    {
        if (!$grn->isPostable()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/grn/message.error.cannot_post'));
        }

        if ($this->grnService->postGrn($grn)) {
            return redirect()->route('inventory.grn.show', $grn)
                ->with('success', trans('admin/inventory/grn/message.success.posted'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to post GRN']);
    }

    public function lock(Request $request, GoodsReceivedNote $grn): RedirectResponse
    {
        if (!$grn->isLockable()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/grn/message.error.cannot_lock'));
        }

        if ($this->grnService->lockGrn($grn)) {
            return redirect()->route('inventory.grn.show', $grn)
                ->with('success', trans('admin/inventory/grn/message.success.locked'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to lock GRN']);
    }

    public function approve(Request $request, GoodsReceivedNote $grn): RedirectResponse
    {
        $grn->approved_by = auth()->id();
        $grn->approved_at = now();
        $grn->inspection_status = 'approved';
        $grn->inspection_notes = $request->input('inspection_notes', '');

        if ($grn->save()) {
            return redirect()->route('inventory.grn.show', $grn)
                ->with('success', trans('admin/inventory/grn/message.success.approved'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to approve GRN']);
    }

    public function addItem(Request $request, GoodsReceivedNote $grn): RedirectResponse
    {
        if (!$grn->isEditable()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/grn/message.error.not_editable'));
        }

        $item = new GrnItem();
        $item->grn_id = $grn->id;
        $item->fill($request->all());
        $item->created_by = auth()->id();

        if ($item->save()) {
            return redirect()->route('inventory.grn.edit', $grn)
                ->with('success', trans('admin/inventory/grn/message.item_added'));
        }

        return redirect()->back()->withInput()->withErrors($item->getErrors());
    }

    public function updateItem(Request $request, GoodsReceivedNote $grn, GrnItem $item): RedirectResponse
    {
        if (!$grn->isEditable()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/grn/message.error.not_editable'));
        }

        $item->fill($request->all());
        $item->updated_by = auth()->id();

        if ($item->save()) {
            return redirect()->route('inventory.grn.edit', $grn)
                ->with('success', trans('admin/inventory/grn/message.item_updated'));
        }

        return redirect()->back()->withInput()->withErrors($item->getErrors());
    }

    public function destroyItem(GoodsReceivedNote $grn, GrnItem $item): RedirectResponse
    {
        if (!$grn->isEditable()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/grn/message.error.not_editable'));
        }

        $item->delete();
        
        return redirect()->route('inventory.grn.edit', $grn)
            ->with('success', trans('admin/inventory/grn/message.item_deleted'));
    }

    public function inspect(Request $request, GoodsReceivedNote $grn, GrnItem $item): RedirectResponse
    {
        $inspection = new GrnInspection();
        $inspection->grn_id = $grn->id;
        $inspection->grn_item_id = $item->id;
        $inspection->inspector_id = auth()->id();
        $inspection->result = $request->input('result');
        $inspection->notes = $request->input('notes');
        $inspection->inspected_at = now();

        if ($inspection->save()) {
            return redirect()->route('inventory.grn.show', $grn)
                ->with('success', trans('admin/inventory/grn/message.inspection_recorded'));
        }

        return redirect()->back()->withInput()->withErrors($inspection->getErrors());
    }
}
