<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    //
    public function index(){
        return view('pages.admin.packages', [
            'resellerPackages' => Package::where('level_id', Level::LEVEL_RESELLER)->get(),
            'adminPackages' => Package::where('level_id', Level::LEVEL_ADMIN)->get(),
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'level_id' => 'required|in:' . Level::LEVEL_RESELLER . ',' . Level::LEVEL_ADMIN,
            'name' => 'required|string',
            'users' => 'required|integer',
            'admin_device' => 'required|integer',
            'user_device' => 'required|integer',
            'live_chat' => 'nullable|in:on',
        ]);

        $inserts = collect($request->only([
            'level_id',
            'name',
            'users',
            'admin_device',
            'user_device',
        ]));
        $inserts['live_chat'] = $request->post('live_chat') === 'on';

        Package::insert($inserts->toArray());
        return redirect(route('admin.managePackages'));
    }

    public function update(Request $request){
        $request->validate([
            'id' => 'required|exists:packages',
            'level_id' => 'required|in:' . Level::LEVEL_RESELLER . ',' . Level::LEVEL_ADMIN,
            'name' => 'required|string',
            'users' => 'required|integer',
            'admin_device' => 'required|integer',
            'user_device' => 'required|integer',
            'live_chat' => 'nullable|in:on',
        ]);

        $updates = collect($request->only([
            'level_id',
            'name',
            'users',
            'admin_device',
            'user_device',
        ]));
        $updates['live_chat'] = $request->post('live_chat') === 'on';

        Package::find($request->post('id'))->update($updates->toArray());
        return redirect(route('admin.managePackages'));
    }

    public function remove($id){
        $package = Package::findOrFail($id);
        $package->delete();
        return redirect(route('admin.managePackages'));
    }

}
