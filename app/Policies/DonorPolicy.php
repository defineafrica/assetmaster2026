<?php

namespace App\Policies;

class DonorPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'donors';
    }

}