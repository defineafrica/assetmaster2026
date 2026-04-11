<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\CustomField;
use App\Models\Location;
use App\Models\Statuslabel;
use App\Models\Supplier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition()
    {
        return [
            'name' => null,
            'model_id' => AssetModel::factory(),
            'rtd_location_id' => Location::factory(),
            'serial' => $this->faker->uuid(),
            'status_id' => function () {
                return Statuslabel::where('name', 'Ready to Deploy')->first() ?? Statuslabel::factory()->rtd()->create(['name' => 'Ready to Deploy']);
            },
            'created_by' => User::factory()->superuser(),
            'asset_tag' => $this->faker->unixTime('now'),
            'notes'   => 'Created by DB seeder',
            'purchase_date' => $this->faker->dateTimeBetween('-1 years', 'now', date_default_timezone_get())->format('Y-m-d'),
            'purchase_cost' => $this->faker->randomFloat(2, '299.99', '2999.99'),
            'order_number' => (string) $this->faker->numberBetween(1000000, 50000000),
            'supplier_id' => Supplier::factory(),
            'requestable' => $this->faker->boolean(),
            'assigned_to' => null,
            'assigned_type' => null,
            'next_audit_date' => null,
            'last_checkout' => null,
            'asset_eol_date' => null
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Inventory $inventory) {
            $inventory->asset_eol_date = $this->faker->boolean(5) 
                ? CarbonImmutable::parse($inventory->purchase_date)->addMonths(rand(0, 20))->format('Y-m-d')
                : CarbonImmutable::parse($inventory->purchase_date)->addMonths($inventory->model->eol)->format('Y-m-d');
        });
    }

    public function laptopMbp()
    {
        return $this->state(function () {
            return [
                'model_id' => function () {
                    return AssetModel::where('name', 'Macbook Pro 13"')->first() ?? AssetModel::factory()->mbp13Model();
                },
            ];
        });
    }

    public function assignedToUser(User $user = null)
    {
        return $this->state(function () use ($user) {
            return [
                'assigned_to' => $user->id ?? User::factory(),
                'assigned_type' => User::class,
                'last_checkout' => now()->subDay(),
            ];
        });
    }

    public function assignedToLocation(Location $location = null)
    {
        return $this->state(function () use ($location) {
            return [
                'assigned_to' => $location->id ?? Location::factory(),
                'assigned_type' => Location::class,
            ];
        });
    }

    public function assignedToAsset()
    {
        return $this->state(function () {
            return [
                'model_id' => 1,
                'assigned_to' => Inventory::factory(),
                'assigned_type' => Inventory::class,
            ];
        });
    }

    public function requestable()
    {
        $id = Statuslabel::factory()->create([
            'archived'   => false,
            'deployable' => true,
            'pending'    => true,
        ])->id;
        return $this->state(['status_id' => $id, 'requestable' => true]);
    }

    public function nonrequestable()
    {
        $id = Statuslabel::factory()->create([
            'archived'   => true,
            'deployable' => false,
            'pending'    => false,
        ])->id;
        return $this->state(['status_id' => $id, 'requestable' => false]);
    }

    public function deleted()
    {
        return $this->state(function () {
            return [
                'model_id' => function () {
                    return AssetModel::where('name', 'Macbook Pro 13"')->first() ?? AssetModel::factory()->mbp13Model();
                },
                'deleted_at' => $this->faker->dateTime(),
            ];
        });
    }

    public function noPurchaseOrEolDate()
    {
        return $this->afterCreating(function (Inventory $inventory) {
            $inventory->update([
                'purchase_date' => null,
                'asset_eol_date' => null
            ]);
        });
    }

    public function hasEncryptedCustomField(CustomField $field = null)
    {
        return $this->state(function () use ($field) {
            return [
                'model_id' => AssetModel::factory()->hasEncryptedCustomField($field),
            ];
        });
    }

    public function hasMultipleCustomFields(array $fields = null): self
    {
        return $this->state(function () use ($fields) {
            return [
                'model_id' => AssetModel::factory()->hasMultipleCustomFields($fields),
            ];
        });
    }

    public function canBeInvalidUponCreation()
    {
        return $this->afterMaking(function (Inventory $inventory) {
            $inventory->setValidating(false);
        })->afterCreating(function (Inventory $inventory) {
            $inventory->setValidating(true);
        });
    }
}
