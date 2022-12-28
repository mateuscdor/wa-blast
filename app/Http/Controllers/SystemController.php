<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemController extends Controller
{
    //
    public function update(Request $request){

        if(Auth::user()->level_id !== Level::LEVEL_SUPER_ADMIN){
            return redirect()->back()->with('alert', ['type' => 'danger', 'msg' => 'You are not permitted to do this action']);
        }

        $request->validate([
           'logo' => 'required|string',
           'logo-icon' => 'required|string',
           'logo-title' => 'required|string',
           'site-title' => 'required|string',
           'site-description' => 'required|string',
        ]);
        
        $items = collect($request->only([
            'logo',
            'logo-icon',
            'logo-title',
            'site-title',
            'site-description',
        ]))->toArray();
        
        foreach ($items as $name => $item){
            $system = System::where('name', $name)->first();
            if(!$system){
                $system = new System([
                   'name' => $name,
                   'value' => $item
                ]);
            } else {
                $system->value = $item;
            }
            $system->save();
        }

        return redirect()->back()->with('alert', [
           'type' => 'success',
           'msg' => 'System settings has been saved'
        ]);
        
    }
}
