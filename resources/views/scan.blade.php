@extends('layouts.app')

@section('title')
    Scan {{$number->body}}
@endsection

@section('content')
    <div class="content-wrapper">
        <h4 class="my-5">Whatsapp Account {{$number->body}}</h4>

        <div class="alert alert-secondary">Don't leave your phone before connected</div>
        <div class="row">
            <div class="col">
                @include('components.device.scan-card', [
                    'hasAccess' => !\Illuminate\Support\Facades\Auth::user()->is_expired_subscription,
                    'deviceNumber' => $number->body,
                ])
            </div>
        </div>
    </div>
@endsection

