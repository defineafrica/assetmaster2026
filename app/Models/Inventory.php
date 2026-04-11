<?php

namespace App\Models;

use App\Events\CheckoutableCheckedOut;
use App\Exceptions\CheckoutNotAllowed;
use App\Helpers\Helper;
use App\Http\Traits\UniqueUndeletedTrait;
use App\Models\Traits\Acceptable;
use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\HasUploads;
use App\Models\Traits\Loggable;
use App\Models\Traits\Requestable;
use App\Models\Traits\Searchable;
use App\Presenters\Presentable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Watson\Validating\ValidatingTrait;

class Inventory extends Depreciable
{
    use CompanyableTrait;
    use HasUploads;
    use HasFactory, Loggable, Requestable, Presentable, SoftDeletes, ValidatingTrait, UniqueUndeletedTrait;

    public const LOCATION = 'location';
    public const INVENTORY = 'inventory';
    public const USER = 'user';

    use Acceptable;

    protected $presenter = \App\Presenters\InventoryPresenter::class;
    protected $with = ['model', 'adminuser'];

    protected $table = 'inventories';

    protected $injectUniqueIdentifier = true;

    protected $casts = [
        'purchase_date' => 'date',
        'eol_explicit' => 'boolean',
        'last_checkout' => 'datetime',
        'last_checkin' => 'datetime',
        'expected_checkin' => 'datetime:m-d-Y',
        'last_audit_date' => 'datetime',
        'next_audit_date' => 'datetime:m-d-Y',
        'model_id'       => 'integer',
        'status_id'      => 'integer',
        'company_id'     => 'integer',
        'location_id'    => 'integer',
        'rtd_company_id' => 'integer',
        'supplier_id'    => 'integer',
        'created_at'     => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    protected $rules = [
        'model_id'          => ['required', 'integer', 'exists:models,id,deleted_at,NULL', 'not_array'],
        'status_id'         => ['required', 'integer', 'exists:status_labels,id'],
        'inventory_tag'     => ['required', 'min:1', 'max:255', 'unique_undeleted:inventories,inventory_tag', 'not_array'],
        'name'              => ['nullable', 'max:255'],
        'company_id'        => ['nullable', 'integer', 'exists:companies,id'],
        'warranty_months'   => ['nullable', 'numeric', 'digits_between:0,240'],
        'last_checkout'     => ['nullable', 'date_format:Y-m-d H:i:s'],
        'last_checkin'      => ['nullable', 'date_format:Y-m-d H:i:s'],
        'expected_checkin'  => ['nullable', 'date'],
        'last_audit_date'   => ['nullable', 'date_format:Y-m-d H:i:s'],
        'next_audit_date'   => ['nullable', 'date'],
        'location_id'       => ['nullable', 'exists:locations,id', 'fmcs_location'],
        'rtd_location_id'   => ['nullable', 'exists:locations,id', 'fmcs_location'],
        'purchase_date'     => ['nullable', 'date', 'date_format:Y-m-d'],
        'serial'            => ['nullable', 'string', 'unique_undeleted:inventories,serial'],
        'purchase_cost'     => ['nullable', 'numeric', 'gte:0', 'max:99999999999999999.99'],
        'supplier_id'       => ['nullable', 'exists:suppliers,id'],
        'asset_eol_date'    => ['nullable', 'date'],
        'eol_explicit'      => ['nullable', 'boolean'],
        'byod'              => ['nullable', 'boolean'],
        'order_number'      => ['nullable', 'string', 'max:191'],
        'notes'             => ['nullable', 'string', 'max:65535'],
        'assigned_to'   => ['nullable', 'integer', 'required_with:assigned_type'],
        'assigned_type' => ['nullable', 'required_with:assigned_to', 'in:'.User::class.",".Location::class.",".self::class],
        'requestable'       => ['nullable', 'boolean'],
        'assigned_user'     => ['integer', 'nullable', 'exists:users,id,deleted_at,NULL'],
        'assigned_location' => ['integer', 'nullable', 'exists:locations,id,deleted_at,NULL', 'fmcs_location'],
        'assigned_inventory'    => ['integer', 'nullable', 'exists:inventories,id,deleted_at,NULL']
    ];

    protected $fillable = [
        'inventory_tag',
        'assigned_to',
        'assigned_type',
        'company_id',
        'donor_id',
        'image',
        'location_id',
        'model_id',
        'name',
        'notes',
        'order_number',
        'purchase_cost',
        'purchase_date',
        'rtd_location_id',
        'serial',
        'status_id',
        'supplier_id',
        'warranty_months',
        'requestable',
        'last_checkout',
        'expected_checkin',
        'byod',
        'asset_eol_date',
        'eol_explicit',
        'last_audit_date',
        'next_audit_date',
        'last_checkin',
        'last_checkout',
    ];

    use Searchable;

    protected $searchableAttributes = [
      'name',
      'inventory_tag',
      'serial',
      'order_number',
      'purchase_cost',
      'notes',
      'created_at',
      'updated_at',
      'purchase_date',
      'expected_checkin',
      'next_audit_date',
      'last_audit_date',
      'last_checkin',
      'last_checkout',
      'asset_eol_date',
    ];

    protected $searchableRelations = [
        'assetstatus'        => ['name'],
        'supplier'           => ['name'],
        'donor'              => ['name'],
        'company'            => ['name'],
        'defaultLoc'         => ['name'],
        'location'           => ['name'],
        'model'              => ['name', 'model_number', 'eol'],
        'model.category'     => ['name'],
        'model.manufacturer' => ['name'],
    ];

    protected static function booted(): void
    {
        static::forceDeleted(function (Inventory $inventory) {
            $inventory->requests()->forceDelete();
        });

        static::softDeleted(function (Inventory $inventory) {
            $inventory->requests()->delete();
        });
    }

    public function setExpectedCheckinAttribute($value)
    {
        if ($value == '') {
            $value = null;
        }
        $this->attributes['expected_checkin'] = $value;
    }

    public function customFieldValidationRules()
    {
        $customFieldValidationRules = [];

        if (($this->model) && ($this->model->fieldset)) {
            foreach ($this->model->fieldset->fields as $field) {
                if ($field->format == 'BOOLEAN' && !$field->field_encrypted) {
                    $this->{$field->db_column} = filter_var($this->{$field->db_column}, FILTER_VALIDATE_BOOLEAN);
                }
            }
            $customFieldValidationRules += $this->model->fieldset->validation_rules();
        }

        return $customFieldValidationRules;
    }

    public function save(array $params = [])
    {
        $this->rules += $this->customFieldValidationRules();
        return parent::save($params);
    }

    public function getDisplayNameAttribute()
    {
        return $this->present()->name();
    }

    protected function warrantyExpires(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => ($attributes['warranty_months'] && $attributes['purchase_date']) ? Carbon::parse($attributes['purchase_date'])->addMonths((int)$attributes['warranty_months']) : null,
        );
    }

    protected function warrantyExpiresFormattedDate(): Attribute
    {
        return Attribute::make(
             get: fn(mixed $value, array $attributes) => Helper::getFormattedDateObject($this->warrantyExpires, 'date', false)
        );
    }

    protected function warrantyExpiresDiff(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->warrantyExpires ? round((Carbon::now()->diffInDays($this->warrantyExpires))) : null,
        );
    }

    protected function warrantyExpiresDiffForHumans(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->warrantyExpires ? Carbon::parse($this->warrantyExpires)->diffForHumans() : null,
        );
    }

    protected function lastAuditFormattedDate(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => Helper::getFormattedDateObject($this->last_audit_date, 'datetime', false)
        );
    }

    protected function lastAuditDiff(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->warrantyExpires ? round((Carbon::now()->diffInDays($this->warrantyExpires))) : null,
        );
    }

    protected function lastAuditDiffForHumans(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) =>  $attributes['last_audit_date'] ? Carbon::parse($attributes['last_audit_date'])->diffForHumans() : null,
        );
    }

    protected function nextAuditFormattedDate(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => Helper::getFormattedDateObject($this->next_audit_date, 'date', false)
        );
    }

    protected function nextAuditDiffInDays(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $attributes['next_audit_date'] ? Carbon::now()->diffInDays($attributes['next_audit_date']) : null,
        );
    }

    protected function nextAuditDiffForHumans(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $attributes['next_audit_date'] ? Carbon::parse($attributes['next_audit_date'])->diffForHumans() : null,
        );
    }

    protected function eolDate(): Attribute
    {
        return Attribute::make(
            get: function(mixed $value, array $attributes) {
                if ($attributes['asset_eol_date'] && $attributes['eol_explicit'] == '1') {
                    return Carbon::parse($attributes['asset_eol_date']);
                } elseif ($attributes['purchase_date'] && $this->model && ((int) $this->model->eol > 0)) {
                    return Carbon::parse($attributes['purchase_date'])->addMonths((int) $this->model->eol);
                } else {
                    return null;
                }
            }
        );
    }

    protected function eolFormattedDate(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->eolDate ? Helper::getFormattedDateObject($this->eolDate, 'date', false) : null,
        );
    }

    protected function eolDiffInDays(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->eolDate ? round((Carbon::now()->diffInDays(Carbon::parse($this->eolDate), false,  1))) : null,
        );
    }

    protected function eolDiffForHumans(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->eolDate  ? Carbon::parse($this->eolDate)->diffForHumans() : null,
        );
    }

    protected function expectedCheckinFormattedDate(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => array_key_exists('expected_checkin', $attributes) ? Helper::getFormattedDateObject($attributes['expected_checkin'], 'date', false) : null,
        );
    }

    protected function expectedCheckinDiffForHumans(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => array_key_exists('expected_checkin', $attributes)  ? Carbon::parse($this->expected_checkin)->diffForHumans() : null,
        );
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    public function availableForCheckout()
    {
        if ((! $this->assigned_to) && (! $this->deleted_at)) {
            if (($this->assetstatus) && ($this->assetstatus->archived == '0')
                && ($this->assetstatus->deployable == '1')
            ) {
                return true;
            }
        }
        return false;
    }

    public function checkOut($target, $admin = null, $checkout_at = null, $expected_checkin = null, $note = null, $name = null, $location = null)
    {
        if (! $target) {
            return false;
        }
        if ($this->is($target)) {
            throw new CheckoutNotAllowed('You cannot check an inventory out to itself.');
        }

        if ($expected_checkin) {
            $this->expected_checkin = $expected_checkin;
        }

        $this->last_checkout = $checkout_at;
        $this->name = $name;

        $this->assignedTo()->associate($target);

        if ($location != null) {
            $this->location_id = $location;
        } else {
            if (isset($target->location)) {
                $this->location_id = $target->location->id;
            }
            if ($target instanceof Location) {
                $this->location_id = $target->id;
            }
        }

        $originalValues = $this->getRawOriginal();

        if ($checkout_at && strpos($checkout_at, date('Y-m-d')) === false) {
            $originalValues['action_date'] = date('Y-m-d H:i:s');
        }

        if ($this->save()) {
            if (is_int($admin)) {
                $checkedOutBy = User::findOrFail($admin);
            } elseif ($admin && get_class($admin) === \App\Models\User::class) {
                $checkedOutBy = $admin;
            } else {
                $checkedOutBy = auth()->user();
            }
            event(new CheckoutableCheckedOut($this, $target, $checkedOutBy, $note, $originalValues));

            $this->increment('checkout_counter', 1);

            return true;
        }

        return false;
    }

    public function getDetailedNameAttribute()
    {
        if ($this->assignedto) {
            $user_name = $this->assignedto->present()->name();
        } else {
            $user_name = 'Unassigned';
        }

        return $this->inventory_tag.' - '.$this->name.' ('.$user_name.') '.($this->model) ? $this->model->name : '';
    }

    public function validationRules()
    {
        return $this->rules;
    }

    public function customFieldsForCheckinCheckout($checkin_checkout)
    {
        if (($this->model) && ($this->model->fieldset) && ($this->model->fieldset->fields)) {
            foreach ($this->model->fieldset->fields as $field) {
                if (($field->{$checkin_checkout} == 1) && (request()->has($field->db_column))) {
                    if ($field->field_encrypted == '1') {
                        if (Gate::allows('inventories.view.encrypted_custom_fields')) {
                            if (is_array(request()->input($field->db_column))) {
                                $this->{$field->db_column} = Crypt::encrypt(implode(', ', request()->input($field->db_column)));
                            } else {
                                $this->{$field->db_column} = Crypt::encrypt(request()->input($field->db_column));
                            }
                        }
                    } else {
                        if (is_array(request()->input($field->db_column))) {
                            $this->{$field->db_column} = implode(', ', request()->input($field->db_column));
                        } else {
                            $this->{$field->db_column} = request()->input($field->db_column);
                        }
                    }
                }
            }
        }
    }

    public function depreciation()
    {
        return $this->hasOneThrough(\App\Models\Depreciation::class, \App\Models\AssetModel::class, 'id', 'id', 'model_id', 'depreciation_id');
    }

    public function components()
    {
        return $this->belongsToMany('\App\Models\Component', 'components_inventories', 'inventory_id', 'component_id')->withPivot('id', 'assigned_qty', 'created_at');
    }

    public function get_depreciation()
    {
        if (($this->model) && ($this->model->depreciation)) {
            return $this->model->depreciation;
        }
    }

    public function checkedOutToUser(): bool
    {
        return $this->assignedType() === self::USER;
    }

    public function checkedOutToLocation(): bool
    {
        return $this->assignedType() === self::LOCATION;
    }

    public function checkedOutToInventory(): bool
    {
        return $this->assignedType() === self::INVENTORY;
    }

    public function assignedTo()
    {
        return $this->morphTo('assigned', 'assigned_type', 'assigned_to')->withTrashed();
    }

    public function assignedInventories()
    {
        return $this->morphMany(self::class, 'assigned', 'assigned_type', 'assigned_to')->withTrashed();
    }

    public function assignedAssets()
    {
        return $this->morphMany(\App\Models\Asset::class, 'assigned', 'assigned_type', 'assigned_to')->withTrashed();
    }

    public function assignedAccessories()
    {
        return $this->morphMany(\App\Models\AccessoryCheckout::class, 'assigned', 'assigned_type', 'assigned_to');
    }

    public function assetLoc($iterations = 1, $first_inventory = null)
    {
        if (! empty($this->assignedType())) {
            if ($this->assignedType() == self::INVENTORY) {
                if (! $first_inventory) {
                    $first_inventory = $this;
                }
                if ($iterations > 10) {
                    throw new \Exception('Inventory assignment Loop for Inventory ID: '.$first_inventory->id);
                }
                $assigned_to = self::find($this->assigned_to);
                if ($assigned_to) {
                    return $assigned_to->assetLoc($iterations + 1, $first_inventory);
                }
            }
            if ($this->assignedType() == self::LOCATION) {
                if ($this->assignedTo) {
                    return $this->assignedTo;
                }
            }
            if ($this->assignedType() == self::USER) {
                if (($this->assignedTo) && $this->assignedTo->userLoc) {
                    return $this->assignedTo->userLoc;
                }
                return $this->defaultLoc;
            }
        }
        return $this->defaultLoc;
    }

    public function assignedType()
    {
        return $this->assigned_type ? strtolower(class_basename($this->assigned_type)) : null;
    }

    public function targetShowRoute()
    {
        $route = str_plural($this->assignedType());
        if ($route=='inventories') {
            return 'inventories';
        }
        return $route;
    }

    public function defaultLoc()
    {
        return $this->belongsTo(\App\Models\Location::class, 'rtd_location_id');
    }

    public function getImageUrl()
    {
        if ($this->image && ! empty($this->image)) {
            return Storage::disk('public')->url(app('assets_upload_path').e($this->image));
        } elseif ($this->model && ! empty($this->model->image)) {
            return Storage::disk('public')->url(app('models_upload_path').e($this->model->image));
        } elseif ($this->model?->category && ! empty($this->model->category->image)) {
            return Storage::disk('public')->url(app('categories_upload_path').e($this->model->category->image));
        }
        return false;
    }

    public function assetlog()
    {
        return $this->hasMany(\App\Models\Actionlog::class, 'item_id')
            ->where('item_type', '=', self::class)
            ->orderBy('created_at', 'desc')
            ->withTrashed();
    }

    public function checkouts()
    {
        return $this->assetlog()->where('action_type', '=', 'checkout')
            ->orderBy('created_at', 'desc')
            ->withTrashed();
    }

    public function audits()
    {
        return $this->assetlog()->where('action_type', '=', 'audit')
            ->orderBy('created_at', 'desc')
            ->withTrashed();
    }

    public function checkins()
    {
        return $this->assetlog()
            ->where('action_type', '=', 'checkin from')
            ->orderBy('created_at', 'desc')
            ->withTrashed();
    }

    public function userRequests()
    {
        return $this->assetlog()
            ->where('action_type', '=', 'requested')
            ->orderBy('created_at', 'desc')
            ->withTrashed();
    }

    public function maintenances()
    {
        return $this->hasMany(\App\Models\Maintenance::class, 'inventory_id')
            ->orderBy('created_at', 'desc');
    }

    public function adminuser()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')->withTrashed();
    }

    public function assetstatus()
    {
        return $this->belongsTo(\App\Models\Statuslabel::class, 'status_id');
    }

    public function model()
    {
        return $this->belongsTo(\App\Models\AssetModel::class, 'model_id')->withTrashed();
    }

    public static function getExpiringWarrantyOrEol($days = 30)
    {
        $now = now();
        $end = now()->addDays($days);

        $expired_inventories = self::query()
            ->where('archived', '=', '0')
            ->NotArchived()
            ->whereNull('deleted_at')
            ->whereNotNull('asset_eol_date')
            ->whereBetween('asset_eol_date', [$now, $end])
            ->get();

        $inventories_with_warranties = self::query()
            ->where('archived', '=', '0')
            ->NotArchived()
            ->whereNull('deleted_at')
            ->whereNotNull('purchase_date')
            ->whereNotNull('warranty_months')
            ->get();

        $expired_warranties = $inventories_with_warranties->filter(function ($inventory) use ($now, $end) {
            $expiration_window = Carbon::parse($inventory->purchase_date)->addMonths((int) $inventory->warranty_months);
            return $expiration_window->betweenIncluded($now, $end);
        });
        return $expired_inventories->concat($expired_warranties)
            ->unique('id')
            ->sortBy([
                ['asset_eol_date', 'ASC'],
                ['purchase_date', 'ASC']
            ])
            ->values();
    }

    public function licenses()
    {
        return $this->belongsToMany(\App\Models\License::class, 'license_seats', 'inventory_id', 'license_id');
    }

    public function licenseseats()
    {
        return $this->hasMany(\App\Models\LicenseSeat::class, 'inventory_id');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier::class, 'supplier_id');
    }

    public function donor()
    {
        return $this->belongsTo(\App\Models\Donor::class, 'donor_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id');
    } 

    public static function autoincrement_inventory(int $additional_increment = 0)
    {
        $settings = \App\Models\Setting::getSettings();

        if ($settings->auto_increment_assets == '1') {
            if ($settings->zerofill_count > 0) {
                return $settings->auto_increment_prefix.self::zerofill($settings->next_auto_tag_base + $additional_increment, $settings->zerofill_count);
            }
            return $settings->auto_increment_prefix.($settings->next_auto_tag_base + $additional_increment);
        } else {
            return false;
        }
    }

    public static function nextAutoIncrement($inventories)
    {
        $max = 1;
        foreach ($inventories as $inventory) {
            $results = preg_match("/\d+$/", $inventory['inventory_tag'], $matches);
            if ($results) {
                $number = $matches[0];
                if ($number > $max) {
                    $max = $number;
                }
            }
        }
    }

    public static function zerofill($num, $zerofill = 3)
    {
        return str_pad($num, $zerofill, '0', STR_PAD_LEFT);
    }

    public function checkin_email()
    {
        if (($this->model) && ($this->model->category)) {
            return $this->model->category->checkin_email;
        }
    }

    public function requireAcceptance()
    {
        if (($this->model) && ($this->model->category)) {
            return $this->model->category->require_acceptance;
        }
        return false;
    }

    public function checkInvalidNextAuditDate()
    {
        if ($this->last_audit_date) {
            $last = Carbon::parse($this->last_audit_date)->format('Y-m-d');
        }
        if ($this->next_audit_date) {
            $next = Carbon::parse($this->next_audit_date)->format('Y-m-d');
        }
        if ((isset($last) && (isset($next))) && ($last > $next)) {
            return true;
        }
        return false;
    }

    public function getComponentCost()
    {
        $cost = 0;
        foreach($this->components as $component) {
            $cost += $component->pivot->assigned_qty*$component->purchase_cost;
        }
        return $cost;
    }

    protected function nextAuditDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function lastAuditDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
            set: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }

    protected function lastCheckout(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
            set: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }

    protected function lastCheckin(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
            set: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }

    protected function assetEolDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function requestable(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) filter_var($value, FILTER_VALIDATE_BOOLEAN),
            set: fn ($value) => (int) filter_var($value, FILTER_VALIDATE_BOOLEAN),
        );
    }

    public function scopeHardware($query)
    {
        return $query->where('physical', '=', '1');
    }

    public function scopePending($query)
    {
        return $query->whereHas(
            'assetstatus', function ($query) {
                $query->where('deployable', '=', 0)
                    ->where('pending', '=', 1)
                    ->where('archived', '=', 0);
            }
        );
    }

    public function scopeAssetsByLocation($query, $location)
    {
        return $query->where(
            function ($query) use ($location) {
                $query->whereHas(
                    'assignedTo', function ($query) use ($location) {
                        $query->where(
                            [
                            ['users.location_id', '=', $location->id],
                            ['inventories.assigned_type', '=', User::class],
                            ]
                        )->orWhere(
                            [
                            ['locations.id', '=', $location->id],
                            ['inventories.assigned_type', '=', Location::class],
                            ]
                        )->orWhere(
                            [
                            ['inventories.rtd_location_id', '=', $location->id],
                            ['inventories.assigned_type', '=', self::class],
                            ]
                        );
                    }
                )->orWhere(
                    function ($query) use ($location) {
                        $query->where('inventories.rtd_location_id', '=', $location->id);
                        $query->whereNull('inventories.assigned_to');
                    }
                );
            }
        );
    }

    public function scopeRTD($query)
    {
        return $query->whereNull('inventories.assigned_to')
            ->whereHas(
                'assetstatus', function ($query) {
                        $query->where('deployable', '=', 1)
                            ->where('pending', '=', 0)
                            ->where('archived', '=', 0);
                }
            );
    }

    public function scopeUndeployable($query)
    {
        return $query->whereHas(
            'assetstatus', function ($query) {
                $query->where('deployable', '=', 0)
                    ->where('pending', '=', 0)
                    ->where('archived', '=', 0);
            }
        );
    }

    public function scopeNotArchived($query)
    {
        return $query->whereHas(
            'assetstatus', function ($query) {
                $query->where('archived', '=', 0);
            }
        );
    }

    public function scopeDueForAudit($query, $settings)
    {
        $interval = (int) $settings->audit_warning_days ?? 0;
        $today = Carbon::now();
        $interval_date = $today->copy()->addDays($interval)->format('Y-m-d');

        return $query->whereNotNull('inventories.next_audit_date')
            ->whereBetween('inventories.next_audit_date', [$today->format('Y-m-d'), $interval_date])
            ->where('inventories.archived', '=', 0)
            ->NotArchived();
    }

    public function scopeOverdueForAudit($query)
    {
        return $query->whereNotNull('inventories.next_audit_date')
            ->where('inventories.next_audit_date', '<', Carbon::now()->format('Y-m-d'))
            ->where('inventories.archived', '=', 0)
            ->NotArchived();
    }

    public function scopeDueOrOverdueForAudit($query, $settings)
    {
        return $query->where(
            function ($query) {
                $query->OverdueForAudit();
            }
        )->orWhere(
            function ($query) use ($settings) {
                $query->DueForAudit($settings);
            }
        );
    }

    public function scopeDueForCheckin($query, $settings)
    {
        $interval = (int) $settings->due_checkin_days ?? 0;
        $today = Carbon::now();
        $interval_date = $today->copy()->addDays($interval)->format('Y-m-d');

        return $query->whereNotNull('inventories.expected_checkin')
            ->whereBetween('inventories.expected_checkin', [$today->format('Y-m-d'), $interval_date])
            ->where('inventories.archived', '=', 0)
            ->whereNotNull('inventories.assigned_to')
            ->NotArchived();
    }

    public function scopeOverdueForCheckin($query)
    {
        return $query->whereNotNull('inventories.expected_checkin')
            ->where('inventories.expected_checkin', '<', Carbon::now()->format('Y-m-d'))
            ->where('inventories.archived', '=', 0)
            ->whereNotNull('inventories.assigned_to')
            ->NotArchived();
    }

    public function scopeDueOrOverdueForCheckin($query, $settings)
    {
        return $query->where(
            function ($query) {
                $query->OverdueForCheckin();
            }
        )->orWhere(
            function ($query) use ($settings) {
                $query->DueForCheckin($settings);
            }
        );
    }

    public function scopeAssetsForShow($query)
    {
        if (Setting::getSettings()->show_archived_in_list!=1) {
            return $query->whereHas(
                'assetstatus', function ($query) {
                    $query->where('archived', '=', 0);
                }
            );
        } else {
            return $query;
        }
    }

    public function scopeArchived($query)
    {
        return $query->whereHas(
            'assetstatus', function ($query) {
                $query->where('deployable', '=', 0)
                    ->where('pending', '=', 0)
                    ->where('archived', '=', 1);
            }
        );
    }

    public function scopeDeployed($query)
    {
        return $query->where('assigned_to', '>', '0');
    }

    public function scopeRequestableAssets($query): Builder
    {
        $table = $query->getModel()->getTable();
        return Company::scopeCompanyables($query->where($table.'.requestable', '=', 1))
        ->whereHas(
            'assetstatus', function ($query) {
                $query->where(
                    function ($query) {
                        $query->where('deployable', '=', 1)
                            ->where('archived', '=', 0);
                    }
                )->orWhere('pending', '=', 1);
            }
        );
    }

    public function scopeInModelList($query, array $modelIdListing)
    {
        return $query->whereIn('inventories.model_id', $modelIdListing);
    }

    public function scopeNotYetAccepted($query)
    {
        return $query->where('accepted', '=', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('accepted', '=', 'rejected');
    }

    public function scopeAccepted($query)
    {
        return $query->where('accepted', '=', 'accepted');
    }

    public function scopeAssignedSearch($query, $search)
    {
        $search = explode(' OR ', $search);

        return $query->leftJoin(
            'users as inventories_users', function ($leftJoin) {
                $leftJoin->on('inventories_users.id', '=', 'inventories.assigned_to')
                    ->where('inventories.assigned_type', '=', User::class);
            }
        )->leftJoin(
            'locations as inventories_locations', function ($leftJoin) {
                    $leftJoin->on('inventories_locations.id', '=', 'inventories.assigned_to')
                        ->where('inventories.assigned_type', '=', Location::class);
            }
        )->leftJoin(
            'inventories as assigned_inventories', function ($leftJoin) {
                    $leftJoin->on('assigned_inventories.id', '=', 'inventories.assigned_to')
                        ->where('inventories.assigned_type', '=', self::class);
            }
        )->where(
            function ($query) use ($search) {
                foreach ($search as $search) {
                    $query->whereHas(
                        'model', function ($query) use ($search) {
                            $query->whereHas(
                                'category', function ($query) use ($search) {
                                    $query->where(
                                        function ($query) use ($search) {
                                            $query->where('categories.name', 'LIKE', '%'.$search.'%')
                                                ->orWhere('models.name', 'LIKE', '%'.$search.'%')
                                                ->orWhere('models.model_number', 'LIKE', '%'.$search.'%');
                                        }
                                    );
                                }
                            );
                        }
                    )->orWhereHas(
                        'model', function ($query) use ($search) {
                            $query->whereHas(
                                'manufacturer', function ($query) use ($search) {
                                    $query->where(
                                        function ($query) use ($search) {
                                            $query->where('manufacturers.name', 'LIKE', '%'.$search.'%');
                                        }
                                    );
                                }
                            );
                        }
                    )->orWhere(
                        function ($query) use ($search) {
                            $query->where('inventories_users.first_name', 'LIKE', '%'.$search.'%')
                                ->orWhere('inventories_users.last_name', 'LIKE', '%'.$search.'%')
                                ->orWhere('inventories_users.username', 'LIKE', '%'.$search.'%')
                                ->orWhere('inventories_users.jobtitle', 'LIKE', '%'.$search.'%')
                                ->orWhereMultipleColumns(
                                    [
                                    'inventories_users.first_name',
                                    'inventories_users.last_name',
                                    'inventories_users.jobtitle',
                                    ], $search
                                )
                                ->orWhere('inventories_locations.name', 'LIKE', '%'.$search.'%')
                                ->orWhere('assigned_inventories.name', 'LIKE', '%'.$search.'%');
                        }
                    )->orWhere('inventories.name', 'LIKE', '%'.$search.'%')
                        ->orWhere('inventories.inventory_tag', 'LIKE', '%'.$search.'%')
                        ->orWhere('inventories.serial', 'LIKE', '%'.$search.'%')
                        ->orWhere('inventories.order_number', 'LIKE', '%'.$search.'%')
                        ->orWhere('inventories.notes', 'LIKE', '%'.$search.'%');
                }
            }
        )->withTrashed()->whereNull('inventories.deleted_at');
    }

    public function scopeCheckedOutToTargetInDepartment($query, $search)
    {
        return $query->leftJoin(
            'users as inventories_dept_users', function ($leftJoin) {
                $leftJoin->on('inventories_dept_users.id', '=', 'inventories.assigned_to')
                    ->where('inventories.assigned_type', '=', User::class);
            }
        )->where(
            function ($query) use ($search) {
                    $query->whereIn('inventories_dept_users.department_id', $search);
            }
        )->withTrashed()->whereNull('inventories.deleted_at');
    }

    public function scopeByFilter($query, $filter)
    {
        return $query->where(
            function ($query) use ($filter) {
                foreach ($filter as $key => $search_val) {
                    $fieldname = str_replace('custom_fields.', '', $key);

                    if ($fieldname == 'inventory_tag') {
                        $query->where('inventories.inventory_tag', 'LIKE', '%'.$search_val.'%');
                    }

                    if ($fieldname == 'name') {
                        $query->where('inventories.name', 'LIKE', '%'.$search_val.'%');
                    }

                    if ($fieldname =='serial') {
                        $query->where('inventories.serial', 'LIKE', '%'.$search_val.'%');
                    }

                    if ($fieldname == 'purchase_date') {
                        $query->where('inventories.purchase_date', 'LIKE', '%'.$search_val.'%');
                    }

                    if ($fieldname == 'purchase_cost') {
                        $query->where('inventories.purchase_cost', 'LIKE', '%'.$search_val.'%');
                    }

                    if ($fieldname == 'notes') {
                        $query->where('inventories.notes', 'LIKE', '%'.$search_val.'%');
                    }

                    if ($fieldname == 'order_number') {
                        $query->where('inventories.order_number', 'LIKE', '%'.$search_val.'%');
                    }

                    if ($fieldname == 'status_label') {
                        $query->whereHas(
                            'assetstatus', function ($query) use ($search_val) {
                                $query->where('status_labels.name', 'LIKE', '%'.$search_val.'%');
                            }
                        );
                    }

                    if ($fieldname == 'location') {
                        $query->whereHas(
                            'location', function ($query) use ($search_val) {
                                $query->where('locations.name', 'LIKE', '%'.$search_val.'%');
                            }
                        );
                    }

                    if ($fieldname == 'rtd_location') {
                        $query->whereHas(
                            'defaultLoc', function ($query) use ($search_val) {
                                $query->where('locations.name', 'LIKE', '%'.$search_val.'%');
                            }
                        );
                    }

                    if ($fieldname == 'assigned_to') {
                        $query->whereHasMorph(
                            'assignedTo', [User::class], function ($query) use ($search_val) {
                                $query->where(
                                    function ($query) use ($search_val) {
                                        $query->where('users.first_name', 'LIKE', '%'.$search_val.'%')
                                            ->orWhere('users.last_name', 'LIKE', '%'.$search_val.'%')
                                            ->orWhere('users.username', 'LIKE', '%'.$search_val.'%');
                                    }
                                );
                            }
                        )->orWhereHasMorph(
                            'assignedTo', [Location::class], function ($query) use ($search_val) {
                                $query->where('locations.name', 'LIKE', '%'.$search_val.'%');
                            }
                        )->orWhereHasMorph(
                            'assignedTo', [self::class], function ($query) use ($search_val) {
                            $query->where(
                                function ($query) use ($search_val) {
                                    $query->where('name', 'LIKE', '%'.$search_val.'%')
                                        ->orWhere('inventory_tag', 'LIKE', '%'.$search_val.'%');
                                }
                            );
                        }
                        );
                    }

                    if ($fieldname == 'manufacturer') {
                        $query->whereHas(
                            'model', function ($query) use ($search_val) {
                                $query->whereHas(
                                    'manufacturer', function ($query) use ($search_val) {
                                        $query->where(
                                            function ($query) use ($search_val) {
                                                $query->where('manufacturers.name', 'LIKE', '%'.$search_val.'%');
                                            }
                                        );
                                    }
                                );
                            }
                        );
                    }

                    if ($fieldname == 'category') {
                        $query->whereHas(
                            'model', function ($query) use ($search_val) {
                                $query->whereHas(
                                    'category', function ($query) use ($search_val) {
                                        $query->where(
                                            function ($query) use ($search_val) {
                                                $query->where('categories.name', 'LIKE', '%'.$search_val.'%')
                                                    ->orWhere('models.name', 'LIKE', '%'.$search_val.'%')
                                                    ->orWhere('models.model_number', 'LIKE', '%'.$search_val.'%');
                                            }
                                        );
                                    }
                                );
                            }
                        );
                    }

                    if ($fieldname == 'model') {
                        $query->whereHas(
                            'model', function ($query) use ($search_val) {
                            $query->where('models.name', 'LIKE', '%'.$search_val.'%');
                        }
                        );
                    }

                    if ($fieldname == 'model_number') {
                        $query->whereHas(
                            'model', function ($query) use ($search_val) {
                            $query->where('models.model_number', 'LIKE', '%'.$search_val.'%');
                        }
                        );
                    }

                    if ($fieldname == 'company') {
                        $query->whereHas(
                            'company', function ($query) use ($search_val) {
                            $query->where('companies.name', 'LIKE', '%'.$search_val.'%');
                        }
                        );
                    }

                    if ($fieldname == 'supplier') {
                        $query->whereHas(
                            'supplier', function ($query) use ($search_val) {
                            $query->where('suppliers.name', 'LIKE', '%'.$search_val.'%');
                        }
                        );
                    }

                    if ($fieldname == 'donor') {
                        $query->whereHas(
                            'donor', function ($query) use ($search_val) {
                            $query->where('donors.name', 'LIKE', '%'.$search_val.'%');
                        }
                        );
                    }

                    if ($fieldname == 'status_label') {
                        $query->whereHas(
                            'assetstatus', function ($query) use ($search_val) {
                            $query->where('status_labels.name', 'LIKE', '%'.$search_val.'%');
                        }
                        );
                    }

                    if ($fieldname == 'jobtitle') {
                        $query->where(function ($query) use ($search_val) {
                            if (is_array($search_val)) {
                                $query->whereHasMorph(
                                    'assignedTo',
                                    [User::class],
                                    function ($query) use ($search_val) {
                                        $query->whereIn('users.jobtitle', $search_val);
                                    }
                                );
                            } else {
                                $query->whereHasMorph(
                                    'assignedTo',
                                    [User::class],
                                    function ($query) use ($search_val) {
                                        $query->where(function ($query) use ($search_val) {
                                            $query->where('users.jobtitle', 'LIKE', '%' . $search_val . '%');
                                        });
                                    }
                                );
                            }
                        });
                    }

                    if (($fieldname!='category') && ($fieldname!='model_number') && ($fieldname!='rtd_location') && ($fieldname!='location') && ($fieldname!='supplier')
                        && ($fieldname!='status_label') && ($fieldname!='assigned_to') && ($fieldname!='model')  && ($fieldname!='jobtitle') && ($fieldname!='company') && ($fieldname!='manufacturer')
                    ) {
                        $query->where('inventories.'.$fieldname, 'LIKE', '%' . $search_val . '%');
                    }
                }
            }
        );
    }

    public function scopeOrderModels($query, $order)
    {
        return $query->join('models as inventory_models', 'inventories.model_id', '=', 'inventory_models.id')->orderBy('inventory_models.name', $order);
    }

    public function scopeOrderModelNumber($query, $order)
    {
        return $query->leftJoin('models as model_number_sort', 'inventories.model_id', '=', 'model_number_sort.id')->orderBy('model_number_sort.model_number', $order);
    }

    public function scopeOrderByCreatedByName($query, $order)
    {
        return $query->leftJoin('users as admin_sort', 'inventories.created_by', '=', 'admin_sort.id')->select('inventories.*')->orderBy('admin_sort.first_name', $order)->orderBy('admin_sort.last_name', $order);
    }

    public function scopeOrderAssigned($query, $order)
    {
        return $query->leftJoin('users as users_sort', 'inventories.assigned_to', '=', 'users_sort.id')->select('inventories.*')->orderBy('users_sort.first_name', $order)->orderBy('users_sort.last_name', $order);
    }

    public function scopeOrderStatus($query, $order)
    {
        return $query->join('status_labels as status_sort', 'inventories.status_id', '=', 'status_sort.id')->orderBy('status_sort.name', $order);
    }

    public function scopeOrderCompany($query, $order)
    {
        return $query->leftJoin('companies as company_sort', 'inventories.company_id', '=', 'company_sort.id')->orderBy('company_sort.name', $order);
    }

    public function scopeInCategory($query, $category_id)
    {
        return $query->join('models as category_models', 'inventories.model_id', '=', 'category_models.id')
            ->join('categories', 'category_models.category_id', '=', 'categories.id')
            ->whereIn('category_models.category_id', (!is_array($category_id) ? explode(',', $category_id): $category_id));
    }

    public function scopeByManufacturer($query, $manufacturer_id)
    {
        return $query->join('models', 'inventories.model_id', '=', 'models.id')
            ->join('manufacturers', 'models.manufacturer_id', '=', 'manufacturers.id')->whereIn('models.manufacturer_id', (!is_array($manufacturer_id) ? explode(',', $manufacturer_id): $manufacturer_id));
    }

    public function scopeOrderCategory($query, $order)
    {
        return $query->join('models as order_model_category', 'inventories.model_id', '=', 'order_model_category.id')
            ->join('categories as category_order', 'order_model_category.category_id', '=', 'category_order.id')
            ->orderBy('category_order.name', $order);
    }

    public function scopeOrderManufacturer($query, $order)
    {
        return $query->join('models as order_inventory_model', 'inventories.model_id', '=', 'order_inventory_model.id')
            ->leftjoin('manufacturers as manufacturer_order', 'order_inventory_model.manufacturer_id', '=', 'manufacturer_order.id')
            ->orderBy('manufacturer_order.name', $order);
    }

    public function scopeOrderLocation($query, $order)
    {
        return $query->leftJoin('locations as inventory_locations', 'inventory_locations.id', '=', 'inventories.location_id')->orderBy('inventory_locations.name', $order);
    }

    public function scopeOrderRtdLocation($query, $order)
    {
        return $query->leftJoin('locations as rtd_inventory_locations', 'rtd_inventory_locations.id', '=', 'inventories.rtd_location_id')->orderBy('rtd_inventory_locations.name', $order);
    }

    public function scopeOrderSupplier($query, $order)
    {
        return $query->leftJoin('suppliers as suppliers_inventories', 'inventories.supplier_id', '=', 'suppliers_inventories.id')->orderBy('suppliers_inventories.name', $order);
    }

    public function scopeOrderByJobTitle($query, $order)
    {
        return $query->leftJoin('users as users_sort', 'inventories.assigned_to', '=', 'users_sort.id')->select('inventories.*')->orderBy('users_sort.jobtitle', $order);
    }

    public function scopeByLocationId($query, $search)
    {
        return $query->where(
            function ($query) use ($search) {
                $query->whereHas(
                    'location', function ($query) use ($search) {
                        $query->where('locations.id', '=', $search);
                    }
                );
            }
        );
    }

    public function scopeByDepreciationId($query, $search)
    {
        return $query->join('models', 'inventories.model_id', '=', 'models.id')
            ->join('depreciations', 'models.depreciation_id', '=', 'depreciations.id')->where('models.depreciation_id', '=', $search);
    }

    public function scopeAdvancedTextSearch(Builder $query, array $terms)
    {
        $query = $query->leftJoin(
            'users as inventories_users', function ($leftJoin) {
                $leftJoin->on('inventories_users.id', '=', 'inventories.assigned_to')
                    ->where('inventories.assigned_type', '=', User::class);
            }
        );

        foreach ($terms as $term) {
            $query = $query
                ->orWhere('inventories_users.first_name', 'LIKE', '%'.$term.'%')
                ->orWhere('inventories_users.last_name', 'LIKE', '%'.$term.'%')
                ->orWhere('inventories_users.display_name', 'LIKE', '%'.$term.'%')
                ->orWhere('inventories_users.jobtitle', 'LIKE', '%'.$term.'%')
                ->orWhere('inventories_users.username', 'LIKE', '%'.$term.'%')
                ->orWhere('inventories_users.employee_num', 'LIKE', '%'.$term.'%')
                ->orWhereMultipleColumns(
                    [
                    'inventories_users.first_name',
                    'inventories_users.last_name',
                    ], $term
                );
        }

        $query = $query->leftJoin(
            'locations as inventories_locations', function ($leftJoin) {
                $leftJoin->on('inventories_locations.id', '=', 'inventories.assigned_to')
                    ->where('inventories.assigned_type', '=', Location::class);
            }
        );

        foreach ($terms as $term) {
            $query = $query->orWhere('inventories_locations.name', 'LIKE', '%'.$term.'%');
        }

        $query = $query->leftJoin(
            'inventories as assigned_inventories', function ($leftJoin) {
                $leftJoin->on('assigned_inventories.id', '=', 'inventories.assigned_to')
                    ->where('inventories.assigned_type', '=', self::class);
            }
        );

        foreach ($terms as $term) {
            $query = $query->orWhere('assigned_inventories.name', 'LIKE', '%'.$term.'%');
        }

        return $query;
    }
}
