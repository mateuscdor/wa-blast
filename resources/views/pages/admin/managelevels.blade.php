@extends('layouts.app')

@section('title')
    Levels
@endsection

@push('head')
    {{--    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />--}}
    {{--    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>--}}
    {{--    <link href="{{asset('css/custom.css')}}" rel="stylesheet">--}}
@endpush

@section('content')
    {{--<x-layout-dashboard title="Auto Replies">--}}

    <div class="app-content">
        <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">

        <div class="content-wrapper">
            <div class="container">
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




                <div class="row mt-4">
                    <div class="col">

                        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                            @foreach($levels as $index => $level)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link{{!$index? ' active': ''}}" id="nav_level_{{$level->id}}" data-bs-toggle="tab" data-bs-target="#{{$level->name}}" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">{{$level->name}}</button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">...</div>
                            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">...</div>
                            <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">...</div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5 class="card-title">Permissions</h5>

                            </div>
                            <div class="card-body">



                                <div class="row m-t-lg">
                                    <form>
                                        <div class="col-md-6">
                                            <label for="maxDevice" class="form-label">Maksimum Device</label>
                                            <input name="max_device" class="form-control" id="maxDevice" required value="{{$levels[0]->max_devices}}">

                                        </div>
                                        <div class="row m-t-lg">
                                            <div class="col">
                                                <button type="button" class="btn btn-primary">
                                                    Simpan
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            {{--                                <table id="datatable1" class="display" style="width:100%">--}}
                            {{--                                    <thead>--}}
                            {{--                                    <tr>--}}
                            {{--                                        <th>Hak Akses</th>--}}
                            {{--                                        <th>Status</th>--}}
                            {{--                                        <th>Action</th>--}}
                            {{--                                        --}}{{-- <th class="d-flex justify-content-center">Action</th> --}}
                            {{--                                    </tr>--}}
                            {{--                                    </thead>--}}
                            {{--                                    <tbody>--}}

                            {{--                                    @foreach($modules as $module)--}}
                            {{--                                        <tr>--}}
                            {{--                                            <td>--}}
                            {{--                                                {{$module->label}}--}}
                            {{--                                            </td>--}}
                            {{--                                            <td>--}}

                            {{--                                            </td>--}}
                            {{--                                        </tr>--}}

                            {{--                                    @endforeach--}}


                            {{--                                    <tfoot></tfoot>--}}
                            {{--                                </table>--}}
                        </div>
                    </div>
                </div>

            </div>


            <!-- Modal -->
            <div class="modal fade" id="modalUser" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="" method="POST" enctype="multipart/form-data" id="formUser">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @csrf
                                <input type="hidden" id="iduser" name="id" >
                                <label for="level_name" class="form-label">Nama Level</label>
                                <input type="text" name="level_name" id="level_name" class="form-control" value=""><br>
                                <label for="modules" class="form-label">Hak Akses</label><br>
                                <select name="modules" multiple="multiple" id="modules" class="form-control">
                                    @foreach($modules as $module)
                                        <option value="{{$module->name}}">{{$module->label}}</option>
                                    @endforeach
                                </select><br>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="modalButton" name="submit" class="btn btn-primary">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{asset('js/pages/datatables.js')}}"></script>

    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>


    <script>
        function addUser(){
            $('#modalLabel').html('Add User');
            $('#modalButton').html('Add');
            $('#formUser').attr('action', '{{route('user.store')}}');
            $('#modalUser').modal('show');
        }

        function editUser(id){

            // return;
            $('#modalLabel').html('Edit User');
            $('#modalButton').html('Edit');
            $('#formUser').attr('action', '{{route('user.update')}}');
            $('#modalUser').modal('show');
            {{--$.ajax({--}}
            {{--    url: "{{route('user.edit')}}",--}}
            {{--    type: "GET",--}}
            {{--    data: {id:id},--}}
            {{--    dataType: "JSON",--}}
            {{--    success: function(data) {--}}
            {{--        --}}
            {{--    }--}}
            {{--});--}}
        }
    </script>
@endpush
