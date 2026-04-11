@extends('layouts/default')

@section('title')
Goods Received Note - {{ $grn->grn_number }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="box-title">
                            GRN: {{ $grn->grn_number }}
                            @switch($grn->status)
                                @case('draft')
                                <span class="label label-default">Draft</span>
                                @break
                                @case('posted')
                                <span class="label label-info">Posted</span>
                                @break
                                @case('locked')
                                <span class="label label-success">Locked</span>
                                @break
                            @endswitch
                        </h3>
                    </div>
                    <div class="col-md-6 text-right">
                        @if($grn->isEditable())
                        <a href="{{ route('inventory.grn.edit', $grn->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @endif
                        @if($grn->isPostable())
                        <form action="{{ route('inventory.grn.post', $grn->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Post GRN
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th>Supplier:</th>
                                <td>{{ $grn->supplier->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Received Date:</th>
                                <td>{{ $grn->received_date->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <th>PO Number:</th>
                                <td>{{ $grn->purchase_order_number ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Received By:</th>
                                <td>{{ $grn->receiver->fullName() ?? 'N/A' }}</td>
                            </tr>
                            @if($grn->approved_at)
                            <tr>
                                <th>Approved By:</th>
                                <td>{{ $grn->approver->fullName() ?? 'N/A' }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th>Total Expected:</th>
                                <td>{{ $summary['total_expected'] }}</td>
                            </tr>
                            <tr>
                                <th>Total Received:</th>
                                <td>{{ $summary['total_received'] }}</td>
                            </tr>
                            <tr>
                                <th>Total Accepted:</th>
                                <td class="text-success">{{ $summary['total_accepted'] }}</td>
                            </tr>
                            <tr>
                                <th>Total Rejected:</th>
                                <td class="text-danger">{{ $summary['total_rejected'] }}</td>
                            </tr>
                            <tr>
                                <th>Total Value:</th>
                                <td>{{ number_format($summary['total_value'], 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($grn->notes)
                <div class="row">
                    <div class="col-md-12">
                        <strong>Notes:</strong>
                        <p>{{ $grn->notes }}</p>
                    </div>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-12">
                        <h4>Items</h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Expected</th>
                                    <th>Received</th>
                                    <th>Accepted</th>
                                    <th>Rejected</th>
                                    <th>Unit Cost</th>
                                    <th>Total</th>
                                    @if($grn->isEditable())
                                    <th>Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grn->items as $item)
                                <tr>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->category->name ?? 'N/A' }}</td>
                                    <td>{{ $item->expected_quantity }}</td>
                                    <td>{{ $item->received_quantity }}</td>
                                    <td class="text-success">{{ $item->accepted_quantity }}</td>
                                    <td class="text-danger">{{ $item->rejected_quantity }}</td>
                                    <td>{{ number_format($item->unit_cost, 2) }}</td>
                                    <td>{{ number_format($item->total_cost, 2) }}</td>
                                    @if($grn->isEditable())
                                    <td>
                                        <a href="#" class="btn btn-xs btn-warning">Edit</a>
                                        <form action="{{ route('inventory.grn.items.destroy', [$grn->id, $item->id]) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No items in this GRN.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($grn->isEditable())
                <div class="row">
                    <div class="col-md-12">
                        <h4>Add Item</h4>
                        <form action="{{ route('inventory.grn.items.store', $grn->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="item_name">Item Name *</label>
                                        <input type="text" name="item_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="expected_quantity">Expected Qty</label>
                                        <input type="number" name="expected_quantity" class="form-control" value="1" min="1">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="received_quantity">Received Qty</label>
                                        <input type="number" name="received_quantity" class="form-control" value="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="unit_cost">Unit Cost</label>
                                        <input type="number" name="unit_cost" class="form-control" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="notes">Notes</label>
                                        <input type="text" name="notes" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Item</button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
