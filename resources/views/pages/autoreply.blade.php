@extends('layouts.app')

@section('title')
    Auto Reply
@endsection

@push('head')
    <link href="{{asset('css/custom.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css" integrity="sha512-rRQtF4V2wtAvXsou4iUAs2kXHi3Lj9NE7xJR77DE7GHsxgY9RTWy93dzMXgDIG8ToiRTD45VsDNdTiUagOFeZA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .modal { overflow: auto !important; }
        .showReply {
            padding-left: 0!important;
            padding-right: 0!important;
        }
        .showReply .conversation-compose {
            height: 56px;
            padding-bottom: 8px;
        }
        .showReply .page {
            width: 100% !important;
            align-items: normal;
        }
        .btn_type {
            padding: 5px 10px;
            border: 0;
            color: #5454ff;
            font-weight: 600;
            font-size: 11px !important;
            background-color: #e5ffdc;
            cursor: pointer;
        }
        .btn_type:hover {
            background-color: #0AAEB3;
        }
    </style>
@endpush
{{-- <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet"> --}}
{{-- <link href="{{asset('plugins/select2/css/select2.css')}}" rel="stylesheet"> --}}
@section('content')
    <div class="content-wrapper">
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


        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">

                        <h5 class="card-title">Lists auto respond {{Session::get('selectedDevice')}} </h5>
                        <div class="d-flex ">

                            @if(Session::has('selectedDevice'))

                                <form action="{{route('deleteAllAutoreply')}}" method="POST">
                                    @method('delete')
                                    @csrf
                                    <button type="submit" name="delete" class="btn btn-danger btn-xs"><i class="material-icons">delete_outline</i>Delete All</button>
                                </form>
                                <button type="button" class="btn btn-primary btn-xs mx-4" data-bs-toggle="modal" data-bs-target="#addAutoRespond"><i class="material-icons-outlined">add</i>Add</button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body rounded-lg">
                        <table id="datatable1" class="display table table-striped table-bordered" style="width:100%">
                            {{-- if exist autoreplies variable foreach, else please select device --}}

                            <thead class="">
                            <tr>

                                <th>Keyword</th>
                                <th>Details</th>
                                <th>Type</th>

                                <th>Respond</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>



                            @if(Session::has('selectedDevice'))
                                @foreach ($autoreplies as $autoreply)

                                    <tr>


                                        <td>{{$autoreply['keyword']}} </td>
                                        <td>Will respond if Keyword <span class="badge badge-success">{{$autoreply['type_keyword']}}</span> &  when the sender is <span class="badge badge-warning">{{$autoreply['reply_when']}}</span> </td>
                                        <td>{{$autoreply['type']}}</td>
                                        <td><button class="btn btn-primary" onclick="viewReply({{$autoreply->id}})">View</button></td>
                                        <td>
                                            <form action={{route('autoreply.delete')}} method="POST">
                                                @method('delete')
                                                @csrf
                                                <input type="hidden" name="id" value="{{$autoreply->id}}">
                                                <button type="submit" name="delete" class="btn btn-danger btn-sm"><i class="material-icons">delete_outline</i></button>
                                            </form>

                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4">Please select device</td>
                                </tr>
                            @endif
                            </tbody>



                        </table>
                        {{-- pagination custom --}}

                        <div class="d-flex">
                            {{$autoreplies->links()}}
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>
    <!-- Modal -->
    <div class="modal fade" id="addAutoRespond" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Auto Reply</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" id="form">
                    <div class="modal-body">
                        @csrf
                        <label for="device" class="form-label">Whatsapp Account</label>
                        @if(Session::has('selectedDevice'))
                            <input type="text" name="device" id="device" class="form-control" value="{{Session::get('selectedDevice')}}" readonly>
                        @else
                            <input type="text" name="device" id="device" class="form-control" value="Please select device" readonly>
                        @endif

                        <div class="form-group">
                            <label class="form-label">Type Keyword</label><br>
                            <div class="form-check">
                                <input type="radio" id="keyword_type_contain" class="form-check-input" value="Contain" checked name="type_keyword"><label for="keyword_type_contain" class="form-check-label">Contain</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="keyword_type_equal" class="form-check-input" value="Equal" name="type_keyword"><label for="keyword_type_equal" class="form-check-label">Equal</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Only reply when sender is </label><br>
                            <div class="form-check">
                                <input type="radio" id="sender_type_group" class="form-check-input" value="Group" name="reply_when"><label for="sender_type_group" class="form-check-label">Group</label>
                            </div>
                            <div class="form-check">
                            <input type="radio" id="sender_type_personal" class="form-check-input" value="Personal" name="reply_when"><label for="sender_type_personal" class="form-check-label">Personal</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="sender_type_all" class="form-check-input" value="All" checked name="reply_when"><label for="sender_type_all" class="form-check-label">All</label>
                            </div>
                        </div>
                        <label for="keyword" class="form-label">Keyword</label>
                        <input type="text" name="keyword" class="form-control" id="keyword" required>

                        <div class="row mt-2">
                            <div class="col">
                                <div class="form-switch">
                                    <input class="form-check-input" type="checkbox" id="created_template">
                                    <label class="form-check-label" for="created_template">Use Created Template</label>
                                </div>
                            </div>
                        </div>
                        <div class="divider mt-2 mb-2"></div>

                        <div id="reply_creator" class="row">
                            @isset($template)
                                @include('components.creators.reply-creator', ['initial' => $template])
                            @else
                                @include('components.creators.reply-creator')
                            @endisset
                        </div>

                        <div id="message_templates" class="row">
                            <div class="col">
                                <label for="msg_template" class="form-label">Message Template</label>
                                <select name="message_template" id="msg_template" class="form-control" style="width: 100%;">
                                    <option value="">Choose a template...</option>
                                    @foreach($templates as $template)
                                        <option value="{{$template->id}}">{{$template->label}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalView" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Auto Reply Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body showReply" id="showReply">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!--  -->
    {{-- <script src="{{asset('js/pages/datatables.js')}}"></script> --}}
    {{-- <script src="{{asset('js/pages/select2.js')}}"></script> --}}
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    {{-- <script src="{{asset('plugins/select2/js/select2.full.min.js')}}"></script> --}}
    <script src="{{asset('js/autoreply.js?t='.getLastJSTime())}}"></script>
    <script>

        let isUsingCreatedTemplate = false;
        $('#message_templates').hide();

        $('#created_template').on('change', function(){
            let checked = $(this).prop('checked');
            if(checked){
                isUsingCreatedTemplate = true;
                $('#reply_creator').hide();
                $('#message_templates').show();
            } else {
                isUsingCreatedTemplate = false;
                $('#reply_creator').show();
                $('#message_templates').hide();
            }
        });

        $('#form').on('submit', function(e){
            e.preventDefault();

            let data;
            if(isUsingCreatedTemplate){
                data = {
                    template_id: $('#msg_template').val(),
                }
            } else {
                data = getAllValues();
            }

            data.label = $('#template_name').val();
            data.keyword = $('[name="keyword"]').val();
            data.type_keyword = $('[name="type_keyword"][checked]').val();
            data.device = $('[name="device"]').val();
            data.reply_when = $('[name="reply_when"][checked]').val();
            const url = '{{isset($template)? route('autoreply', $template->id): route('autoreply')}}';

            $.ajax({
                method : 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url : url,
                data : data,
                dataType : 'json',
                success : (result) => {
                    window.location = ''
                },
                error : (err) => {
                    //console.log(err);
                    window.location = '';
                }
            })
        })
        let isModalOpen = false;
        $('#addAutoRespond').on('show.bs.modal', function(){
           isModalOpen = true;
        });
        $('#modal-spintax').on('show.bs.modal', function(){
            $('#addAutoRespond').modal('hide');
        });
        $('#modal-spintax').on('hide.bs.modal', function(){
            $('#addAutoRespond').modal('show');
        });
    </script>
@endpush
