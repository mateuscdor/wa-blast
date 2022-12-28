<?php

namespace App\Http\Controllers;

use App\Models\Number;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class NumberController extends Controller
{

    public function addLivechat(Request $request){

        $user = Auth::user();
        if(!$user->can_add_live_chat){
            throw new BadRequestHttpException;
        }

        $request->validate([
            'number' => 'required|unique:numbers,body',
        ]);

        $newNumber = $request->post('number');
        if(Str::startsWith($newNumber, '0')){
            $newNumber = '62' . Str::substr($newNumber, 1);
        }

        $number = new Number([
            'user_id' => $user->id,
            'body' => $newNumber,
            'status' => Number::STATUS_DISCONNECTED,
            'api_key' => Str::random(30),
            'live_chat' => 1,
        ]);
        $number->save();

        return redirect()->to(route('scan', $number->body))->with('alert', [
            'type' => 'success',
            'msg' => 'Number has been added'
        ]);
    }
    //
    public function update(Request $request, $body){
        $number = Number::where('body', $body)->firstOrFail();

        if(!$number->is_updatable){
            throw new BadRequestHttpException;
        }

        $request->validate([
            'number' => 'required|unique:numbers,body',
        ]);

        $newNumber = $request->post('number');
        if(Str::startsWith($newNumber, '0')){
            $newNumber = '62' . Str::substr($newNumber, 1);
        }

        Http::withoutVerifying()->post(env('WA_URL_SERVER') . '/backend-logout', [
            'token' => $body
        ]);

        $number->body = $newNumber;
        $number->status = Number::STATUS_DISCONNECTED;
        $number->save();

        return redirect()->to(route('scan', $number->body))->with('alert', [
            'type' => 'success',
            'msg' => 'Number has been changed'
        ]);
    }

    public function disconnect($body){
        $number = Number::where('body', $body)->firstOrFail();
        if(!$number->is_updatable){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'You are forbidden to disconnect this device'
            ]);
        }

        Http::withoutVerifying()->post(env('WA_URL_SERVER') . '/backend-logout', [
            'token' => $body
        ]);
        $number->status = Number::STATUS_DISCONNECTED;
        $number->save();
        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => 'The requested number has been disconnected'
        ]);
    }
}
