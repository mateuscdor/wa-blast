<?php

namespace App\Http\Controllers;

use App\Models\Number;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScanController extends Controller
{
    public function index($body)
    {
        $number = Number::where('body', $body)->firstOrFail();
        if(!$number->is_usable){
            throw new NotFoundHttpException;
        }

        return view('scan',[
            'number' => $number
        ]);
    }
}
