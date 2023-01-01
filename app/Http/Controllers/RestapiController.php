<?php

namespace App\Http\Controllers;

use App\Models\Number;
use Illuminate\Http\Request;

class RestapiController extends Controller
{
    public function __invoke(Request $request)
    {
        $apiKey = '<YOUR-DEVICE-API-KEY-HERE>';
        if(session()->has('selectedDevice')){
            $selectedDevice = session()->get('selectedDevice');
            $device = Number::where('body', $selectedDevice)->first();
            if($device){
                $apiKey = $device->api_key;
            }
        }
        return view('pages.rest-api', [
            'apiKey' => $apiKey
        ]);
    }
}
