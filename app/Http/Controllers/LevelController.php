<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LevelController extends Controller
{
    //
    public function index()
    {
        return view('pages.admin.managelevels', [
            'levels' => Level::with(['levelModules', 'users'])->get(),
            'modules' => Module::all(),
        ]);
    }
}
