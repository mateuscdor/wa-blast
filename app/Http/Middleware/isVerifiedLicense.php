<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class isVerifiedLicense
{

    private  $verified;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */

   
    public function handle(Request $request, Closure $next)
    {
        $key = env('LICENSE_KEY');
        if(!Session::has('verifiedLicense')){
            
            $cek = Http::withOptions(['verify' => false])->get('https://license-management.m-pedia.my.id/api/license/check?licensekey='.$key.'&host='.$_SERVER['HTTP_HOST'])->object();
            if($cek->status === 200){
                Session::put('verifiedLicense',true);
                return $next($request);
              }else {
                return Redirect::intended('https://license.m-pedia.my.id/invalid.html');
              }
        } else if(Session::get('verifiedLicense')) {
            return $next($request);
        }

       
    }
}
