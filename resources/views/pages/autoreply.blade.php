@extends('layouts.app')

@section('title')
    Auto Reply
@endsection

@push('head')
    <link href="{{asset('css/custom.css')}}" rel="stylesheet">
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
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
                        <div class="d-flex gap-2">
                            <div class="dropdown d-none" id="dropdown_actions">
                                <a class="btn btn-warning btn-sm dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </a>

                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                    <li>
                                        <a class="dropdown-item text-danger bg-outline-danger" href="#" id="selection_delete_toggle" data-bs-toggle="modal" data-bs-target="#modal-delete-confirm">
                                            Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            @if(Session::has('selectedDevice'))
                                <form action="{{route('deleteAllAutoreply')}}" method="POST">
                                    @method('delete')
                                    @csrf
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete All</button>
                                </form>
                                <button type="button" class="btn btn-primary btn-sm" id="addModalBtn" data-bs-toggle="modal" data-bs-target="#addAutoRespond">Add</button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body rounded-lg">
                        <table id="datatable1" class="display" style="width:100%">
                            {{-- if exist autoreplies variable foreach, else please select device --}}

                            <thead>
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

                                    <tr data-id="{{$autoreply->id}}">


                                        <td>{{implode(', ', explode('[|]', $autoreply['keyword'])) ?: '(No keywords)'}} </td>
                                        <td>Will respond if Keyword <span class="badge badge-success">{{$autoreply['type_keyword']}}</span> &  when the sender is <span class="badge badge-warning">{{$autoreply['reply_when']}}</span> </td>
                                        <td>{{$autoreply['type']}}</td>
                                        <td>
                                            <button data-stop-propagation class="btn btn-primary btn-sm" onclick="viewReply({{$autoreply->id}})">
                                                Preview
                                            </button>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button data-stop-propagation class="btn btn-warning btn-sm" data-bs-target="#addAutoRespond" data-bs-toggle="modal" data-edit-id="{{$autoreply['id']}}" data-autoreply="{{ json_encode($autoreply) }}">
                                                    Edit
                                                </button>
                                                <form action={{route('autoreply.delete')}} method="POST">
                                                    @method('delete')
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{$autoreply->id}}">
                                                    <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
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
                    <h5 class="modal-title" id="autoreply-modal-title">Add Auto Reply</h5>
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

                        <div class="form-group">
                            <label class="form-label">Type Keyword</label><br>
                            <div class="form-check">
                                <input type="radio" id="keyword_type_contain" class="form-check-input" value="Contain" checked name="type_keyword"><label for="keyword_type_contain" class="form-check-label">Contain</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="keyword_type_equal" class="form-check-input" value="Equal" name="type_keyword"><label for="keyword_type_equal" class="form-check-label">Equal</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="keyword_type_none" class="form-check-input" value="None" name="type_keyword"><label for="keyword_type_none" class="form-check-label">None</label>
                            </div>
                        </div>

                        <div class="row mt-2" id="keyword__container">
                            <div class="col">
                                <label for="keyword" class="form-label">Keywords</label>
                                <input type="text" class="form-control" placeholder="Enter to add a keyword" name="keyword_input" id="keyword">
                                <input type="hidden" name="keyword" id="keywords">
                                <div id="keyword_container">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col">
                                <div class="form-switch">
                                    <input class="form-check-input" type="checkbox" id="switch_time">
                                    <label class="form-check-label" for="switch_time">Time Management</label>
                                </div>
                            </div>
                        </div>

                        <div id="time_management">
                            <div class="row mt-2">
                                <div class="col-sm-6">
                                    <label for="start_time" class="form-label">Active Start Time</label>
                                    <input type="time" name="start_time" class="form-control" value="00:00" id="start_time">
                                </div>
                                <div class="col-sm-6">
                                    <label for="end_time" class="form-label">Active End Time</label>
                                    <input type="time" name="end_time" class="form-control" value="23:59" id="end_time">
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-sm-6">
                                    <label for="active_days" class="form-label">Active Days</label>
                                    <div class="dropdown" id="dd_days">
                                        <a class="btn btn-outline-success btn-sm dropdown-toggle w-100" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Select days
                                        </a>

                                        <ul class="dropdown-menu">
                                            <li>
                                                <label for="active_day_1" data-stop-propagation class="form-check flex dropdown-item">
                                                    <input class="form-check-input" type="checkbox" data-value="sat" id="active_day_1" name="active_days[0]">
                                                    <span class="form-check-label flex-grow-1 d-block">Sabtu</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label for="active_day_2" data-stop-propagation class="form-check flex dropdown-item">
                                                    <input class="form-check-input" type="checkbox" data-value="sun" id="active_day_2" name="active_days[1]">
                                                    <span class="form-check-label flex-grow-1 d-block">Minggu</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label for="active_day_3" data-stop-propagation class="form-check flex dropdown-item">
                                                    <input class="form-check-input" type="checkbox" data-value="mon" id="active_day_3" name="active_days[2]">
                                                    <span class="form-check-label flex-grow-1 d-block">Senin</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label for="active_day_4" data-stop-propagation class="form-check flex dropdown-item">
                                                    <input class="form-check-input" type="checkbox" data-value="tue" id="active_day_4" name="active_days[3]">
                                                    <span class="form-check-label flex-grow-1 d-block">Selasa</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label for="active_day_5" data-stop-propagation class="form-check flex dropdown-item">
                                                    <input class="form-check-input" type="checkbox" data-value="wed" id="active_day_5" name="active_days[4]">
                                                    <span class="form-check-label flex-grow-1 d-block">Rabu</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label for="active_day_6" data-stop-propagation class="form-check flex dropdown-item">
                                                    <input class="form-check-input" type="checkbox" data-value="thu" id="active_day_6" name="active_days[5]">
                                                    <span class="form-check-label flex-grow-1 d-block">Kamis</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label for="active_day_7" data-stop-propagation class="form-check flex dropdown-item">
                                                    <input class="form-check-input" type="checkbox" data-value="fri" id="active_day_7" name="active_days[6]">
                                                    <span class="form-check-label flex-grow-1 d-block">Jumat</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
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
                        <button type="submit" name="submit" class="btn btn-primary" id="submitBtn">Submit</button>
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
    <div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Delete Auto Reply</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('autoreply.delete.selected')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>
                            Are you sure want to delete <span id="selection_count">0</span> autoreplies?
                        </p>
                        <div id="selection_ids"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!--  -->
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script src="{{asset('js/autoreply.js?t='.getLastJSTime())}}"></script>
    <script>

        let isUpdating = false;
        let isUsingCreatedTemplate = false;
        $('#message_templates').hide();
        $('#datatable1').DataTable();
        let multiSelector = new MultiInputCreator({
            inputSelector: '#keyword',
            hiddenSelector: '#keywords',
            createdSelector: '#keyword_container',
            hiddenCreator: function(p, c, i){
                if(!p){
                    return c;
                }
                return p + '[|]' + c;
            },
        });

        multiSelector.init();

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

        $('[data-autoreply]').click(function(){

            let autoreply = $(this).data('autoreply');

            let keywords = autoreply.keyword.split('[|]');

            $('#keyword').val('');
            multiSelector.fill(keywords);
            if(autoreply.settings?.startTime === '00:00' && autoreply.settings?.endTime === '23:59' && autoreply.settings?.activeDays?.length === 7){
                $('#time_management').hide();
                $('#switch_time').prop('checked', false);
            } else {
                $('#time_management').show();
                $('#switch_time').prop('checked', true);
            }

            $('[name="start_time"]').val(autoreply.settings?.startTime);
            $('[name="end_time"]').val(autoreply.settings?.endTime);
            $(`[name="type_keyword"][value="${autoreply.type_keyword}"]`).prop('checked', true);
            $('[name="device"]').val(autoreply.device);
            $(`[name="reply_when"][value="${autoreply.reply_when}"]`).prop('checked', true);
            $('[id^=active_day_]').each(function(){
                let el = $(this);
                let val = el.data('value');
                if((autoreply.settings?.activeDays ?? []).includes(val)){
                    el.prop('checked', true);
                } else {
                    el.prop('checked', false);
                }
            });


            let currentData = JSON.parse(autoreply.reply);
            isUpdating = autoreply.id;
            $('#autoreply-modal-title').text('Edit Auto Reply');
            $('#message_type').val(autoreply.type)
            $('#message_type').trigger('change')
            let buttons = currentData.buttons || currentData.templateButtons || [];
            let list = currentData.list ?? {};
            let footer = currentData.footer ?? "";
            let body = currentData.text ?? currentData.caption ?? currentData.message ?? '';
            let image = currentData.image?.url ?? "";

            buttonCreator.fill(buttons?.map(b => {
                let id = b.index;
                let type = 'callButton' in b? 'phone': 'quickReplyButton' in b? 'text': 'url';
                let currentType = 'callButton' in b? 'callButton': 'quickReplyButton' in b? 'quickReplyButton': 'urlButton';
                let label = (b[currentType])?.displayText ?? '';
                let text = b[currentType].url ?? b[currentType].phoneNumber ?? '';
                return {
                    id,
                    type,
                    label,
                    text
                }
            }));
            listCreator.fill(currentData);
            footerCreator.fill(footer);[{"index": 0, "callButton": {"displayText": "Button 1", "phoneNumber": "6285157830644"}}]
            bodyCreator.fill(body);
            mediaCreator.fill(image);
        });

        const selected = {};
        const selectedGroup = '';
        $('table tbody').on('click', 'tr[data-id]', function () {
            const id = $(this).data('id');
            const groupId = $(this).data('groupId') ?? '';
            if(!selected[groupId]){
                selected[groupId] = []
            }

            const index = $.inArray(id, selected[groupId]);

            if ( index === -1 ) {
                selected[groupId].push( id );
            } else {
                selected[groupId].splice( index, 1 );
            }

            if(selected[groupId]?.length){
                $('#dropdown_actions').removeClass('d-none');
            } else {
                $('#dropdown_actions').addClass('d-none');
            }

            $(this).toggleClass('selected');
        });
        $('#selection_delete_toggle').click(function(){
            $('#selection_ids').html('')
            for(let index in selected[selectedGroup]){
                let id = selected[selectedGroup][index];
                $('#selection_ids').append($(`<input type="hidden" name="id[${index}]" value="${id}"/>`))
            }
            $('#selection_count').text(selected[selectedGroup].length);
        });
        $('[name="type_keyword"]').change(function(e){
            if($('[name="type_keyword"]:checked').val() === 'None'){
                $('#keyword__container').hide();
            } else {
                $('#keyword__container').show();
            }
        });
        $('#switch_time').change(function(e){
           let checked = $(this).prop('checked');
           if(checked){
               $('#time_management').show();
           } else {
               $('#time_management').hide();
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
            let url = '{{isset($template)? route('autoreply', $template->id): route('autoreply')}}';

            if(isUpdating){
                data.id = isUpdating;
            }

            data.label = $('#template_name').val();
            data.type_keyword = $('[name="type_keyword"]:checked').val();

            data.keyword = $('#keyword').val();

            if(data.type_keyword !== 'None'){
                data.keyword = $('#keyword').val();
                data.keywords = $('#keywords').val();
            } else {
                data.keyword = '';
            }

            if($('#switch_time').prop('checked')){
                data.active_days = Array.from($('[id^=active_day_]:checked').map(function(){
                    let el = $(this);
                    return el.data('value');
                }));
                data.start_time = $('[name="start_time"]').val();
                data.end_time = $('[name="end_time"]').val();
                data.active_days = JSON.stringify(data.active_days);
            } else {
                data.start_time = '00:00';
                data.end_time = '23:59';
                data.active_days = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
            }

            data.device = $('[name="device"]').val();
            data.reply_when = $('[name="reply_when"]:checked').val();

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
        $('#addModalBtn').click(function(){
            isUpdating = false;
            $('#form').trigger('reset');

            $('#keyword').val('');
            multiSelector.fill([]);

            $('[name="start_time"]').val('');
            $('[name="end_time"]').val('');
            $('[name="reply_when"][value="All"]').prop('checked', true);
            $('[name="type_keyword"][value="Contain"]').prop('checked', true);
            $('[name="type_keyword"]').trigger('change');
            $('[id^=active_day_]').each(function(){
                $(this).prop('checked', false);
            });
            $('#switch_time').prop('checked', false);
            $('#switch_time').trigger('change');

            $('#autoreply-modal-title').text('Add Auto Reply');
            $('#message_type').val('')
            $('#message_type').trigger('change')
            let buttons = [];
            let list = {};
            let footer = "";
            let body = "";
            let image = "";
            buttonCreator.fill(buttons);
            listCreator.fill(list);
            footerCreator.fill(footer);
            bodyCreator.fill(body);
            mediaCreator.fill(image);
        });

        $('#time_management').hide();

    </script>
@endpush
