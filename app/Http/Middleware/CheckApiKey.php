<?php

namespace App\Http\Middleware;

use App\Models\Number;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(!isset($request->api_key) || !isset($request->number) || !isset($request->message)){
            return response()->json([
                'status' => false ,
                'msg' => 'Wrong parameters!',
            ],400);
        }
        // check user by sender then check api key
        $data = Number::where('api_key', $request->api_key)->with('user')->first();
        if(!$data){
            return response()->json([
                'status' => false ,
                'msg' => 'Invalid data!',
            ],400);
        }
        if($data->user->is_expired_subscription){
            return response()->json([
                'status' => false ,
                'msg' => 'Your subscription has expired!, contanct admin to renew your subscription',
            ],400);
        }
        if(!$data){
            return response()->json([
                'status' => false ,
                'msg' => 'Invalid data!',
            ],400);
        }
        if($request->api_key !== $data->api_key){
            return response()->json([
                'status' => false ,
                'msg' => 'Wrong API KEY',
            ],400);
        }

        return $next($request);
    }
}
