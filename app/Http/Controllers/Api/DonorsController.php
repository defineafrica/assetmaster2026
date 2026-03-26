<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\DonorsTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Donor;
use Illuminate\Http\Request;
use App\Http\Requests\ImageUploadRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class DonorsController extends Controller
{
    public function index(Request $request) : JsonResponse | array
    {
        $this->authorize('view', Donor::class);

        $allowed_columns = [
            'id',
            'name',
            'phone',
            'fax',
            'email',
            'created_at',
            'updated_at',
            'assets_count',
            'licenses_count',
            'accessories_count',
            'consumables_count',
            'components_count',
            'tag_color',
            'notes',
        ];

        $donors = Donor::withCount(['assets as assets_count'  => function ($query) {
            $query->AssetsForShow();
        }])
            ->with('adminuser')
            ->withCount('licenses as licenses_count', 'accessories as accessories_count', 'consumables as consumables_count', 'components as components_count');


        if ($request->filled('search')) {
            $donors->TextSearch($request->input('search'));
        }

        if ($request->filled('name')) {
            $donors->where('name', '=', $request->input('name'));
        }

		if ($request->filled('email')) {
            $donors->where('email', '=', $request->input('email'));
        }

        if ($request->filled('created_by')) {
            $donors->where('created_by', '=', $request->input('created_by'));
        }

        if ($request->filled('tag_color')) {
            $donors->where('tag_color', '=', $request->input('tag_color'));
        }

        $offset = ($request->input('offset') > $donors->count()) ? $donors->count() : app('api_offset_value');
        $limit = app('api_limit_value');
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort_override =  $request->input('sort');
        $column_sort = in_array($sort_override, $allowed_columns) ? $sort_override : 'created_at';

        switch ($sort_override) {
            case 'created_by':
                $donors = $donors->OrderByCreatedBy($order);
                break;
            default:
                $donors = $donors->orderBy($column_sort, $order);
                break;
        }

        $total = $donors->count();

        $donors = $donors->skip($offset)->take($limit)->get();
        return (new DonorsTransformer)->transformDonors($donors, $total);

    }

    public function store(ImageUploadRequest $request) : JsonResponse
    {
        $this->authorize('create', Donor::class);
        $donor = new Donor;
        $donor->fill($request->all());
        $donor = $request->handleImages($donor);
        
        if ($donor->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new DonorsTransformer)->transformDonor($donor), trans('admin/donors/message.create.success')));
        }

        return response()
            ->json(Helper::formatStandardApiResponse('error', null, $donor->getErrors()));
    }

    public function show($id) : array
    {
        $this->authorize('view', Donor::class);
        $donor = Donor::findOrFail($id);
        $this->authorize('view', $donor);
        return (new DonorsTransformer)->transformDonor($donor);

    }

    public function update(ImageUploadRequest $request, $id) : JsonResponse
    {
        $this->authorize('update', Donor::class);
        $donor = Donor::findOrFail($id);
        $this->authorize('update', $donor);
        $donor->fill($request->all());
        $donor = $request->handleImages($donor);

        if ($donor->save()) {
            return response()
                ->json(Helper::formatStandardApiResponse('success', (new DonorsTransformer)->transformDonor($donor), trans('admin/donors/message.update.success')));
        }

        return response()
            ->json(Helper::formatStandardApiResponse('error', null, $donor->getErrors()));
    }

    public function destroy($id) : JsonResponse
    {
        $this->authorize('delete', Donor::class);
        $donor = Donor::findOrFail($id);
        $this->authorize('delete', $donor);

        if (! $donor->isDeletable()) {
            return response()
                    ->json(Helper::formatStandardApiResponse('error', null, trans('admin/donors/message.assoc_assets')));
        }
        $donor->delete();

        return response()
            ->json(Helper::formatStandardApiResponse('success', null, trans('admin/donors/message.delete.success')));
    }

    public function selectlist(Request $request) : array
    {
        $this->authorize('view.selectlists');
        $donors = Donor::select([
            'donors.id',
            'donors.name',
            'donors.email',
            'donors.tag_color',
        ]);


        if ($request->filled('search')) {
            $donors = $donors->where('donors.name', 'LIKE', '%'.$request->input('search').'%');
        }

        $donors = $donors->orderBy('name', 'ASC')->paginate(50);

        foreach ($donors as $donor) {
            $donor->use_text = $donor->name;
        }

        return (new SelectlistTransformer)->transformSelectlist($donors);
    }
}