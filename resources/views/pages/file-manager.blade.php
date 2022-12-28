@extends('layouts.app')

@section('title') File Manager @endsection

@section('content')
    <div class="content-wrapper">

        <div class="container-fluid">
            <div class="row">
                @if (session()->has('alert'))
                    <x-alert>
                        @slot('type',session('alert')['type'])
                        @slot('msg',session('alert')['msg'])
                    </x-alert>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <iframe src="{{url('/laravel-filemanager')}}" style="width: 100%; height: calc(100vh - 160px); overflow: hidden; border: none;"></iframe>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $('#server').on('change',function(){
            let type = $('#server :selected').val();
            console.log(type);
            if(type === 'other'){
                $('.formUrlNode').removeClass('d-none')
            } else {
                $('.formUrlNode').addClass('d-none')

            }
        })
    </script>
@endsection