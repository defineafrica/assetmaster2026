@props([
    'route' => null,
    'name' => 'default',
])

<!-- start inventories tab pane -->
@can('view', \App\Models\Inventory::class)

        <x-slot:header>
            {{ trans('general.inventories') }}
        </x-slot:header>

        <x-slot:bulkactions>
            <x-table.bulk-inventories />
        </x-slot:bulkactions>

        <x-slot:content>
            <x-table
                    show_column_search="true"
                    show_advanced_search="true"
                    fixed_right_number="2"
                    buttons="inventoryButtons"
                    api_url="{{ $route }}"
                    :presenter="\App\Presenters\InventoryPresenter::dataTableLayout()"
                    export_filename="export-{{ str_slug($name) }}-inventories-{{ date('Y-m-d') }}"
            />
        </x-slot:content>

@endcan
<!-- end inventories tab pane -->
