@extends('layouts.app')

@section('title')
    Auto Reply
@endsection

@push('head')
    <link href="{{asset('css/custom.css')}}" rel="stylesheet">
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
                        @isset($template)
                            @include('components.creators.reply-creator', ['initial' => $template])
                        @else
                            @include('components.creators.reply-creator')
                        @endisset
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
                <div class="modal-body showReply">
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
    <script src="{{asset('js/autoreply.js')}}"></script>
    <script>
        $('#form').on('submit', function(e){
            e.preventDefault();

            const data = getAllValues();

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
    </script>
@endpush