<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisitionItem;
use App\Services\Inventory\RequisitionWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PurchaseRequisitionController extends Controller
{
    protected RequisitionWorkflowService $workflowService;

    public function __construct(RequisitionWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function index(): View
    {
        return view('inventory/requisitions/index');
    }

    public function create(): View
    {
        $pr = new PurchaseRequisition();
        $pr->pr_number = PurchaseRequisition::generatePrNumber();
        $pr->status = PurchaseRequisition::STATUS_DRAFT;
        $pr->requesting_user_id = auth()->id();
        
        return view('inventory/requisitions/edit')
            ->with('item', $pr)
            ->with('item_type', 'create');
    }

    public function store(Request $request): RedirectResponse
    {
        $pr = new PurchaseRequisition();
        $pr->fill($request->all());
        $pr->pr_number = $request->input('pr_number', PurchaseRequisition::generatePrNumber());
        $pr->status = PurchaseRequisition::STATUS_DRAFT;
        $pr->requesting_user_id = auth()->id();
        $pr->created_by = auth()->id();
        $pr->updated_by = auth()->id();

        if ($pr->save()) {
            return redirect()->route('inventory.requisitions.edit', $pr)
                ->with('success', trans('admin/inventory/requisitions/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($pr->getErrors());
    }

    public function show(PurchaseRequisition $purchaseRequisition): View
    {
        $purchaseRequisition->load(['items', 'requestingUser', 'department', 'approver', 'rejector']);
        
        return view('inventory/requisitions/view')
            ->with('pr', $purchaseRequisition);
    }

    public function edit(PurchaseRequisition $purchaseRequisition): View
    {
        $purchaseRequisition->load(['items']);
        
        return view('inventory/requisitions/edit')
            ->with('item', $purchaseRequisition)
            ->with('item_type', 'edit');
    }

    public function update(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        if (!$purchaseRequisition->isDraft()) {
            return redirect()->route('inventory.requisitions.show', $purchaseRequisition)
                ->with('error', trans('admin/inventory/requisitions/message.error.not_editable'));
        }

        $purchaseRequisition->fill($request->all());
        $purchaseRequisition->updated_by = auth()->id();

        if ($purchaseRequisition->save()) {
            return redirect()->route('inventory.requisitions.show', $purchaseRequisition)
                ->with('success', trans('admin/inventory/requisitions/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($purchaseRequisition->getErrors());
    }

    public function destroy(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        if (!$purchaseRequisition->isDraft()) {
            return redirect()->route('inventory.requisitions.index')
                ->with('error', trans('admin/inventory/requisitions/message.error.cannot_delete'));
        }

        $purchaseRequisition->delete();
        
        return redirect()->route('inventory.requisitions.index')
            ->with('success', trans('admin/inventory/requisitions/message.delete.success'));
    }

    public function submit(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        if (!$purchaseRequisition->isSubmittable()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/requisitions/message.error.cannot_submit'));
        }

        if ($this->workflowService->submitForApproval($purchaseRequisition)) {
            return redirect()->route('inventory.requisitions.show', $purchaseRequisition)
                ->with('success', trans('admin/inventory/requisitions/message.success.submitted'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to submit requisition']);
    }

    public function approve(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        if ($this->workflowService->approve($purchaseRequisition, auth()->user(), $request->input('comments'))) {
            return redirect()->route('inventory.requisitions.show', $purchaseRequisition)
                ->with('success', trans('admin/inventory/requisitions/message.success.approved'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to approve requisition']);
    }

    public function reject(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $reason = $request->input('rejection_reason');
        
        if (!$reason) {
            return redirect()->back()
                ->with('error', 'Rejection reason is required');
        }

        if ($this->workflowService->reject($purchaseRequisition, auth()->user(), $reason)) {
            return redirect()->route('inventory.requisitions.show', $purchaseRequisition)
                ->with('success', trans('admin/inventory/requisitions/message.success.rejected'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to reject requisition']);
    }

    public function fulfill(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        if ($purchaseRequisition->markFulfilled()) {
            return redirect()->route('inventory.requisitions.show', $purchaseRequisition)
                ->with('success', trans('admin/inventory/requisitions/message.success.fulfilled'));
        }

        return redirect()->back()->withInput()->withErrors(['error' => 'Failed to mark as fulfilled']);
    }

    public function addItem(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        if (!$purchaseRequisition->isDraft()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/requisitions/message.error.not_editable'));
        }

        $item = new PurchaseRequisitionItem();
        $item->pr_id = $purchaseRequisition->id;
        $item->fill($request->all());
        $item->created_by = auth()->id();

        if ($item->save()) {
            $purchaseRequisition->recalculateTotal();
            return redirect()->route('inventory.requisitions.edit', $purchaseRequisition)
                ->with('success', trans('admin/inventory/requisitions/message.item_added'));
        }

        return redirect()->back()->withInput()->withErrors($item->getErrors());
    }

    public function updateItem(Request $request, PurchaseRequisition $purchaseRequisition, PurchaseRequisitionItem $item): RedirectResponse
    {
        if (!$purchaseRequisition->isDraft()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/requisitions/message.error.not_editable'));
        }

        $item->fill($request->all());
        $item->updated_by = auth()->id();

        if ($item->save()) {
            $purchaseRequisition->recalculateTotal();
            return redirect()->route('inventory.requisitions.edit', $purchaseRequisition)
                ->with('success', trans('admin/inventory/requisitions/message.item_updated'));
        }

        return redirect()->back()->withInput()->withErrors($item->getErrors());
    }

    public function destroyItem(PurchaseRequisition $purchaseRequisition, PurchaseRequisitionItem $item): RedirectResponse
    {
        if (!$purchaseRequisition->isDraft()) {
            return redirect()->back()
                ->with('error', trans('admin/inventory/requisitions/message.error.not_editable'));
        }

        $item->delete();
        $purchaseRequisition->recalculateTotal();
        
        return redirect()->route('inventory.requisitions.edit', $purchaseRequisition)
            ->with('success', trans('admin/inventory/requisitions/message.item_deleted'));
    }
}
