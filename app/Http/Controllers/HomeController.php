<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Number;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    

    public function index(Request $request){
       
        $numbers = Number::whereStatus('Connected')->get();
        return view('home',[
            'numbers' => $request->user()->numbers()->where('live_chat', '!=', 1)->latest()->paginate(15),
            'limit_device' => $request->user()->limit_device,
        ]);
    }

  
    public function store(Request $request){
        $limit_device = Auth::user()->limit_device;
        $deviceadded = $request->user()->numbers()->count();
        if($limit_device <= $deviceadded && !in_array(Auth::user()->level_id, [Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER])){
            return redirect()->back()->with('alert',['type' => 'danger','msg' => 'You have reached your limit of devices']);
        }
        $request->validate([
            'sender' => ['required','min:10','unique:numbers,body']
        ]);
        Number::create([
            'user_id' => Auth::user()->id,
            'body' => $request->sender,
            'webhook' => $request->urlwebhook,
            'status' => Number::STATUS_DISCONNECTED,
            'api_key' => Str::random(30),
            'messages_sent' => 0
        ]);

        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Devices Added!'
        ]);
    }
    public function destroy(Request $request){
        Number::find($request->deviceId)->delete();

        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Devices Deleted!'
        ]);
    }


    public function setHook(Request $request){
        $n = Number::whereBody($request->number)->first();
        $n->webhook = $request->webhook;
        $n->save();
        return true;
    }

    public function setSelectedDeviceSession(Request $request){
        session()->put('selectedDevice', $request->device);
        return true;
    }


    


    

}
