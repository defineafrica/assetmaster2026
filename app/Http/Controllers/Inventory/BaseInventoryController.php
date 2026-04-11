<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

class BaseInventoryController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected $module_name = 'inventory';

    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function guessItemTypeFromRoute(): string
    {
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        
        if (str_contains($routeName, 'consumables')) {
            return 'consumables';
        }
        if (str_contains($routeName, 'accessories')) {
            return 'accessories';
        }
        
        return 'assets';
    }
}
