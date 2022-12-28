@extends('layouts.app')

@section('title')
    Settings
@endsection

@push('head')
    <style>
        .image-input {
            flex-grow: 1;
            background-color: transparent;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
        }
    </style>
@endpush

@section('content')
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
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="page-description page-description-tabbed">
                    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">System</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="server-tab" data-bs-toggle="tab" data-bs-target="#server" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">Server</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="system" role="tabpanel" aria-labelledby="system-tab">
                                <form method="post" action="{{route('system.update')}}">
                                    @csrf
                                    <div class="row m-t-lg">
                                        <div class="col-sm-6">
                                            <label for="site_title" class="form-label">Site Title</label>
                                            <input type="text" name="site-title" class="form-control" id="site_title" value="{{getSystemSettings('site-title')}}" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="logo_title" class="form-label">Logo Title</label>
                                            <input type="text" name="logo-title" class="form-control" id="logo_title" value="{{getSystemSettings('logo-title')}}" required>
                                        </div>
                                        <div class="col-sm-12 m-t-lg">
                                            <label for="site_description" class="form-label">Site Description</label>
                                            <textarea name="site-description" rows="5" class="form-control" id="site_description" required>{{getSystemSettings('site-description')}}</textarea>
                                        </div>
                                        <div class="col-sm-6 m-t-lg">
                                            <div>
                                                <label class="form-label">Logo</label>
                                                <div class="input-group bg-light rounded d-flex align-items-center p-2 w-100">
                                                    <div class="input-group-btn">
                                                        <a id="template_image_path" data-input="thumbnail" data-preview="holder" class="btn btn-primary text-white">
                                                            <i class="material-icons">image</i> Choose
                                                        </a>
                                                    </div>
                                                    <input id="thumbnail" class="image-input disabled" type="text" value="{{getSystemSettings('logo')}}" name="logo">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 m-t-lg">
                                            <div>
                                                <label class="form-label">Logo Icon</label>
                                                <div class="input-group bg-light rounded d-flex align-items-center p-2 w-100">
                                                    <div class="input-group-btn">
                                                        <a id="template_image_path2" data-input="thumbnail2" data-preview="holder" class="btn btn-primary text-white">
                                                            <i class="material-icons">image</i> Choose
                                                        </a>
                                                    </div>
                                                    <input id="thumbnail2" class="image-input disabled" type="text" value="{{getSystemSettings('logo-icon')}}" name="logo-icon">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col m-t-lg">
                                            <button type="submit" class="btn btn-primary m-t-sm">Update</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="server" role="tabpanel" aria-labelledby="server-tab">
                                <div class="row">
                                    <form action="{{route('setServer')}}" method="POST">
                                        <div class="row m-t-lg">
                                            @csrf
                                            <div class="col-md-6">
                                                <label for="typeServer" class="form-label">Server Type</label>
                                                <select name="typeServer" class="form-control" id="typeServer" required>
                                                    @if (env('TYPE_SERVER') === 'localhost')
                                                        <option value="localhost" selected>Localhost</option>
                                                        <option value="hosting">Hosting Shared</option>
                                                        <option value="other">Other</option>
                                                    @elseif(env('TYPE_SERVER') === 'hosting')
                                                        <option value="localhost">Localhost</option>
                                                        <option value="hosting" selected>Hosting Shared</option>
                                                        <option value="other">Other</option>
                                                    @else
                                                        <option value="other" required>Other</option>
                                                        <option value="localhost">Localhost</option>
                                                        <option value="hosting">Hosting Shared</option>
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="Port" class="form-label">Port Node JS</label>
                                                <input type="number" name="portnode" class="form-control" id="Port" value="{{env('PORT_NODE')}}" required>
                                            </div>
                                        </div>
                                        <div class="row m-t-lg {{env('TYPE_SERVER') === 'other' ? 'd-block' : 'd-none'}} formUrlNode">
                                            <div class="col-md-6">
                                                <label for="settingsInputUserName " class="form-label">URL Node</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"  id="settingsInputUserName-add">URL</span>
                                                    <input type="text" class="form-control" value="{{env('WA_URL_SERVER')}}" name="urlnode" id="settingsInputUserName" aria-describedby="settingsInputUserName-add">
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row m-t-lg">
                                            <div class="col">

                                                <button type="submit" class="btn btn-primary m-t-sm">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{url('/vendor/laravel-filemanager/js/stand-alone-button.js')}}"></script>
    <script>
        $('#template_image_path').filemanager('file')
        $('#template_image_path2').filemanager('file')
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
@endpush