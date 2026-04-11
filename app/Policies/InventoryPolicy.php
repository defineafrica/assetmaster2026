<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inventory;

class InventoryPolicy extends CheckoutablePermissionsPolicy
{
    protected function columnName()
    {
        return 'inventories';
    }

    public function viewRequestable(User $user, Inventory $inventory = null)
    {
        return $user->hasAccess('inventories.view.requestable');
    }

    public function audit(User $user, Inventory $inventory = null)
    {
        return $user->hasAccess('inventories.audit');
    }
}
