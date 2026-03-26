<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Models\Donor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use \Illuminate\Contracts\View\View;

final class DonorsController extends Controller
{
    public function index() : View
    {
        $this->authorize('view', Donor::class);

        return view('donors/index');
    }

    public function create() : View
    {
        $this->authorize('create', Donor::class);

        return view('donors/edit')->with('item', new Donor);
    }

    public function store(ImageUploadRequest $request) : RedirectResponse
    {
        $this->authorize('create', Donor::class);

        $donor = new Donor;
        $donor->name = $request->input('name');
        $donor->phone = $request->input('phone');
        $donor->fax = $request->input('fax');
        $donor->email = $request->input('email');
        $donor->tag_color = $request->input('tag_color');
        $donor->notes = $request->input('notes');
        $donor->created_by = auth()->id();

        $donor = $request->handleImages($donor);

        if ($donor->save()) {
            return redirect()->route('donors.index')
                ->with('success', trans('admin/donors/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($donor->getErrors());
    }

    public function edit(Donor $donor) : View | RedirectResponse
    {
        $this->authorize('update', $donor);
        return view('donors/edit')->with('item', $donor);
    }

    public function update(ImageUploadRequest $request, Donor $donor) : RedirectResponse
    {
        $this->authorize('update', $donor);
        $donor->name = $request->input('name');
        $donor->phone = $request->input('phone');
        $donor->fax = $request->input('fax');
        $donor->email = $request->input('email');
        $donor->tag_color = $request->input('tag_color');
        $donor->notes = $request->input('notes');

        $donor = $request->handleImages($donor);

        if ($donor->save()) {
            return redirect()->route('donors.index')
                ->with('success', trans('admin/donors/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($donor->getErrors());
    }

    public function destroy($donorId) : RedirectResponse
    {
        if (is_null($donor = Donor::find($donorId))) {
            return redirect()->route('donors.index')
                ->with('error', trans('admin/donors/message.not_found'));
        }

        $this->authorize('delete', $donor);
        if (! $donor->isDeletable()) {
            return redirect()->route('donors.index')
                    ->with('error', trans('admin/donors/message.assoc_assets'));
        }

        if ($donor->image) {
            try {
                Storage::disk('public')->delete('donors'.'/'.$donor->image);
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }

        $donor->delete();

        return redirect()->route('donors.index')
            ->with('success', trans('admin/donors/message.delete.success'));
    }

    public function show(Donor $donor) : View | RedirectResponse
    {
        $this->authorize('view', Donor::class);
        return view('donors/view')->with('donor', $donor);
    }
}