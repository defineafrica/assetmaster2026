<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Watson\Validating\ValidatingTrait;

final class Donor extends SnipeModel
{
    use HasFactory;

    protected $table = 'donors';

    protected $rules = [
        'name' => 'required|max:255|unique:donors,name',
        'fax' => 'min:7|max:35|nullable',
        'phone' => 'min:7|max:35|nullable',
        'email' => 'email|max:150|nullable',
    ];

    protected $presenter = \App\Presenters\DonorPresenter::class;
    use Presentable;

    protected $injectUniqueIdentifier = true;
    use ValidatingTrait;
    use Searchable;
    
    protected $searchableAttributes = ['name', 'phone', 'fax', 'email', 'created_at', 'updated_at'];

    protected $searchableRelations = [];

    protected $fillable = [
        'name',
        'phone',
        'fax',
        'email',
        'created_by',
        'tag_color',
        'notes',
    ];

    public function isDeletable()
    {
        return Gate::allows('delete', $this)
            && (($this->assets_count ?? $this->assets()->count()) === 0)
            && (($this->accessories_count ?? $this->accessories()->count()) === 0)
            && (($this->licenses_count ?? $this->licenses()->count()) === 0)
            && (($this->components_count ?? $this->components()->count()) === 0)
            && (($this->consumables_count ?? $this->consumables()->count()) === 0);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'donor_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'donor_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'donor_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'donor_id');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'donor_id');
    }

    public function adminuser()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')->withTrashed();
    }

    public function scopeOrderByCreatedBy($query, $order)
    {
        return $query->leftJoin('users as admin_sort', 'donors.created_by', '=', 'admin_sort.id')->select('donors.*')->orderBy('admin_sort.first_name', $order)->orderBy('admin_sort.last_name', $order);
    }
}