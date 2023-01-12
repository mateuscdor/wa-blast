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

                        <h5 class="card-title">Autoreply History {{Session::get('selectedDevice')}} </h5>
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
                            <a href="{{route('autoreply-history.resend-all')}}" data-stop-propagation class="btn btn-warning btn-sm">
                                Resend Fails
                            </a>
                            @if(Session::has('selectedDevice'))
                                <form action="{{route('autoreply-history.delete.all')}}" method="POST">
                                    @method('delete')
                                    @csrf
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete All</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="card-body rounded-lg">
                        <div class="overflow-auto">
                            <table id="datatable" class="table table-hover" style="width:100%">
                                {{-- if exist autoreplies variable foreach, else please select device --}}

                                <thead>
                                <tr>
                                    <th>Target Name</th>
                                    <th>Target Number</th>
                                    <th>Incoming Message</th>
                                    <th>Status</th>
                                    <th>Received At</th>
                                    <th>Sent At</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>

                                @if(Session::has('selectedDevice'))
                                    @foreach ($autoreplyMessages as $message)
                                        @component('components.tables.autoreply-history-table-row', ['message' => $message])
                                        @endcomponent
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4">Please select device</td>
                                    </tr>
                                @endif
                                </tbody>



                            </table>

                        </div>
                        {{-- pagination custom --}}

                        <div class="d-flex w-100 justify-content-center">
                            {{$autoreplyMessages->links()}}
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>
    <!-- Modal -->
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
                    <h5 class="modal-title" id="exampleModalLabel">Delete History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('autoreply-history.delete.selected')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>
                            Are you sure want to delete <span id="selection_count">0</span> messages?
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
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script src="{{asset('js/autoreply.js?t='.getLastJSTime())}}"></script>
    <script>

        let isUpdating = false;
        let isUsingCreatedTemplate = false;
        let ids = {{$autoreplyMessages->map(function($m){return $m['id'];})}}
        $('#message_templates').hide();
        $('#datatable1').DataTable({
            paging: false,
        });
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

            $('[name="keyword"]').val('');
            multiSelector.fill(keywords);

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
            let buttons = currentData.buttons ?? [];
            let list = currentData.list ?? {};
            let footer = currentData.footer ?? "";
            let body = currentData.text ?? currentData.caption ?? currentData.message ?? '';
            let image = currentData.image?.url ?? "";
            buttonCreator.fill(buttons);
            listCreator.fill(list);
            footerCreator.fill(footer);
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

        setInterval(()=>{
            $.ajax({
               url: '{{route('autoreply-history.refresh')}}',
               method: 'GET',
                success(r){
                   for(let data of r){
                       let status = {
                           success :'success',
                           failed :'danger',
                           pending :'warning',
                           processing :'info'
                       }[data.status] ?? 'secondary';
                       if(!document.querySelector(`[data-message-id="${data.id}"]`)){
                           let el = $(`${data.view}`);
                           $('#datatable tbody').append(el);
                       }
                       $(`[data-message-id="${data.id}"]`).text(data.status).attr('class', `badge badge-${status}`);
                       if(data.status === 'failed'){
                          $(`[data-resend-id="${data.id}"]`).removeClass('d-none');
                       } else {
                           $(`[data-resend-id="${data.id}"]`).addClass('d-none');
                       }
                       $('[data-stop-propagation]').click(function(e){
                           e.stopPropagation();
                       });
                   }
                },
                error(e){
                   console.log(e);
                }
            });
        }, 2000);
    </script>
@endpush