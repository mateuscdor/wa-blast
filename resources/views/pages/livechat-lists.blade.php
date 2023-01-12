@extends('layouts.app')

@section('title') Live Chat @endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
    <style>
        .ml-auto {
            margin-left: auto;
        }
        .mr-auto {
            margin-right: auto;
        }
        tr.selected {
            background-color: #bec0c2;
        }
        tr[data-id] {
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    <div>
        @if (session()->has('alert'))
            <x-alert>
                @slot('type',session('alert')['type'])
                @slot('msg',session('alert')['msg'])
            </x-alert>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-style-light">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row">
            <div class="col">
                @if($device)
                    <div class="mb-4 d-flex align-items-center alert alert-style-light alert-light">
                        You are using number:&nbsp;<b>+{{$device->body}}</b>.
                        <div class="float-right ml-auto d-flex gap-2">
                            <button data-bs-target="#switch-modal" data-bs-toggle="modal" class="btn btn-primary">
                                Change Number
                            </button>
                            @if($device->status === \App\Models\Number::STATUS_DISCONNECTED)
                                <a href="{{route('scan', $device->body)}}" class="btn btn-warning">
                                    Scan
                                </a>
                            @else
                                <form method="POST" action="{{route('disconnectDevice', $device->body)}}">
                                    @csrf
                                    <button class="btn btn-danger" type="submit">
                                        Disconnect
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h5>
                                    Conversations
                                </h5>
                                <div class="d-flex gap-1">

                                    <div class="dropdown d-none" id="dropdown_actions">
                                        <a class="btn btn-warning btn-sm dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </a>

                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal-contacts" id="add_to_contacts">
                                                    Add to Contacts
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal-group" id="change_group">
                                                    Change Group
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <button id="btn_export" class="btn btn-primary btn-sm h-auto ml-auto" type="button">
                                        Export
                                    </button>
                                    <button id="btn_create_label" class="btn btn-primary btn-sm h-auto ml-auto" type="button">
                                        Create Group
                                    </button>
                                </div>
                            </div>
                            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                                @foreach($groups as $index => $group)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="nav_group_{{$group->id}}" data-bs-toggle="tab" data-bs-target="#tab_{{$group->id}}" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">
                                            {{$group->label}}
                                            <i class="material-icons" data-type="btn_edit_label" data-group-id="{{$group->id}}" data-group-label="{{$group->label}}" style="font-size: 16px">edit</i>
                                        </button>
                                    </li>
                                @endforeach
                                <li class="nav-item active" role="presentation">
                                    <button class="nav-link active" id="nav_group_default" data-bs-toggle="tab" data-bs-target="#tab_default" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">
                                        Unlabeled chats
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                @foreach($groups as $index => $group)
                                    <div class="tab-pane fade" id="tab_{{$group->id}}" role="tabpanel" aria-labelledby="nav_group_{{$group->id}}">
                                        <table class="display" style="width: 100%">
                                            <thead>
                                            <tr>
                                                <th>
                                                    Defined Label
                                                </th>
                                                <th>
                                                    Contact Name
                                                </th>
                                                <th>
                                                    Number
                                                </th>
                                                <th>
                                                    Unread messages
                                                </th>
                                                <th>
                                                    Time Range
                                                </th>
                                                <th>
                                                    Action
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach(($conversations[$group->id] ?? []) as $conversation)
                                                <tr data-id="{{$conversation->id}}" data-group-id="{{$group->id}}">
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            {{$conversation->defined_name ?: "-"}}
                                                            <button data-before="{{$conversation->defined_name}}" data-edit-id="{{$conversation->id}}" data-toggle="edit" class="btn btn-warning btn-sm">
                                                                Edit
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            {{$conversation->target_name ?: "-"}}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        {{$conversation->target_number}}
                                                    </td>
                                                    <td>
                                                        {{$conversation->unread_chats_count}}
                                                    </td>
                                                    <td
                                                            data-from-time="{{$conversation->oldest_time}}"
                                                            data-to-time="{{$conversation->latest_time}}"
                                                    >
                                                    <span data-from-id="{{$conversation->id}}" class="badge badge-warning">
                                                        {{$conversation->oldest_time}}
                                                    </span>
                                                        <span data-to-id="{{$conversation->id}}" class="badge badge-primary">
                                                        {{$conversation->latest_time}}
                                                    </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <a href="{{route('livechat.view', $conversation->id)}}" class="btn btn-primary btn-sm">
                                                                View
                                                            </a>

                                                            <form action="{{route('livechat.delete', $conversation->id)}}" method="POST" onsubmit="return confirm('Are you sure will delete this conversation?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="id" value="{{$conversation->id}}">
                                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot></tfoot>
                                        </table>
                                    </div>
                                @endforeach
                                <div class="tab-pane fade show active" id="tab_default" role="tabpanel" aria-labelledby="nav_group_default">
                                    <table class="display" style="width: 100%">
                                        <thead>
                                        <tr>
                                            <th>
                                                Defined Label
                                            </th>
                                            <th>
                                                Contact Name
                                            </th>
                                            <th>
                                                Number
                                            </th>
                                            <th>
                                                Unread messages
                                            </th>
                                            <th>
                                                Time Range
                                            </th>
                                            <th>
                                                Action
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($conversations[''] ?? [] as $conversation)
                                            <tr data-id="{{$conversation->id}}" data-group-id="">
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        {{$conversation->defined_name ?: "-"}}
                                                        <button data-before="{{$conversation->defined_name}}" data-edit-id="{{$conversation->id}}" data-toggle="edit" class="btn btn-warning btn-sm">
                                                            Edit
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        {{$conversation->target_name ?: "-"}}
                                                    </div>
                                                </td>
                                                <td>
                                                    {{$conversation->target_number}}
                                                </td>
                                                <td>
                                                    {{$conversation->unread_chats_count}}
                                                </td>
                                                <td
                                                        data-from-time="{{$conversation->oldest_time}}"
                                                        data-to-time="{{$conversation->latest_time}}"
                                                >
                                                    <span data-from-id="{{$conversation->id}}" class="badge badge-warning">
                                                        {{$conversation->oldest_time}}
                                                    </span>
                                                    <span data-to-id="{{$conversation->id}}" class="badge badge-primary">
                                                        {{$conversation->latest_time}}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{route('livechat.view', $conversation->id)}}" class="btn btn-primary btn-sm">
                                                            View
                                                        </a>
                                                        <form action="{{route('livechat.delete', $conversation->id)}}" method="POST" onsubmit="return confirm('Are you sure will delete this conversation?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{$conversation->id}}">
                                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot></tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div>
                        <div class="mb-4 d-flex align-items-center alert alert-style-light alert-warning">
                            You have no registered device for this feature. Please add one.
                            <div class="float-right ml-auto d-flex gap-2">
                                <button data-bs-target="#switch-modal" data-bs-toggle="modal" class="btn btn-primary">
                                    Add a Number
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @if($device)
        <div class="modal fade" id="switch-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal_title">
                            Change Number
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{route('changeDevice', $device->body)}}">
                        <div class="modal-body">
                            @csrf
                            <label for="current_number" class="form-label mt-2">Current Number</label>
                            <input id="current_number" type="tel" class="form-control" disabled="disabled" value="{{$device->body}}">
                            <label for="phone_number" class="form-label mt-2">New Number</label>
                            <input id="phone_number" type="tel" name="number" class="form-control" required placeholder="628xxxxxxxx">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" id="button_button" name="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="modal fade" id="switch-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal_title">
                            Add Number
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{route('addLiveChatDevice')}}">
                        <div class="modal-body">
                            @csrf
                            <label for="phone_number" class="form-label mt-2">New Number</label>
                            <input id="phone_number" type="tel" name="number" class="form-control" required placeholder="628xxxxxxxx">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" id="button_button" name="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="label-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="group_modal_title">
                        Add Group
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="label_form" action="{{route('addLiveChatLabel')}}">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="group_id" name="id" value="">
                        <label for="group_label" class="form-label mt-2">Label</label>
                        <input id="group_label" name="label" class="form-control" required placeholder="Initiate a label">
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex gap-2 w-100">
                            <div class="mr-auto">
                                <a id="deletion_group_id" type="submit" class="btn btn-danger">
                                    Delete Group
                                </a>
                            </div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" id="button_button" name="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-group" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="group_modal_title">
                        Change Group
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="label_form" action="{{route('changeGroupLabels')}}">
                    <div class="modal-body">
                        @csrf
                        Change the related group for conversations
                        <div id="selected_conversations">
                        </div>
                        <label for="group" class="form-label mt-2">Groups</label>
                        <select id="group" name="group_id" class="form-control" required>
                            <option value="">Select a Group</option>
                            @foreach($groups as $group)
                                <option value="{{$group->id}}">{{$group->label}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="button_button" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-contacts" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="group_modal_title">
                        Add to Contacts (Phone Book)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="label_form" action="{{route('livechatToBook')}}">
                    <div class="modal-body">
                        @csrf
                        <div id="selected_conversations_2">
                        </div>
                        <label for="books" class="form-label mt-2">Phone Book</label>
                        <select id="books" name="book_id" class="form-control" required>
                            <option value="">Select a Phone Book</option>
                            @foreach($tags as $book)
                                <option value="{{$book->id}}">{{$book->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="button_submit_contacts" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-label" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="group_modal_title">
                        Change Defined Label
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="label_form" action="{{route('livechat.change-label')}}">
                    <div class="modal-body">
                        @csrf
                        <div id="selected_conversations">
                        </div>
                        <label for="defined_name" class="form-label mt-2">New Label</label>
                        <input class="form-control" id="defined_name" name="defined_name" value="">
                        <input name="id" id="defined_id" type="hidden">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="button_button" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('[data-toggle="edit"]').click(function(){
            let id = $(this).data('editId');
            let labelBefore = $(this).data('before');
            $('#defined_name').val(labelBefore);
            $("#defined_id").val(id);
            $('#modal-label').modal('show');
        });
    </script>
    <script>
        $('#btn_create_label').click(function(){
            $('#group_id').val('');
            $('#group_label').val('');
            $('#group_modal_title').text('Add Group');
            $('#label_form').attr('action', '{{route('addLiveChatLabel')}}')
            $('#deletion_group_id').addClass('d-none');
            $('#label-modal').modal('show');
        })
        $('[data-type="btn_edit_label"]').click(function(){
            let el = $(this);
            let groupId = el.data('groupId');
            let groupName = el.data('groupLabel');
            $('#group_modal_title').text('Edit Group');
            $('#group_id').val(groupId);
            $('#group_label').val(groupName);
            $('#deletion_group_id').removeClass('d-none');
            $('#label_form').attr('action', '{{route('editLiveChatLabel')}}')
            $('#deletion_group_id').attr('href', '{{url('/conversation/groups/delete/')}}/' + groupId);
            $('#label-modal').modal('show');
        })
    </script>
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script>
        let selected = {};
        let selectedGroup = '{{(isset($groups[0])? $groups[0]->id: '')}}';
        $(document).ready(function(){
            let table = $('table.display').DataTable();
        });
        $('.nav-link[id^="nav_group"]').click(function(){
            let id = $(this).attr('id').replace('nav_group_', '');
            if(id === 'default'){
                selectedGroup = '';
            } else {
                selectedGroup = parseInt(id);
            }

            if(!selected[selectedGroup]?.length){
                $('#dropdown_actions').addClass('d-none');
            } else {
                $('#dropdown_actions').removeClass('d-none');
            }
        });
        $('table tbody').on('click', 'tr[data-id]', function () {
            const id = $(this).data('id');
            const groupId = $(this).data('groupId');
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
        $('#change_group').click(function(){
            let selections = selected[selectedGroup];
            if(selections?.length){
                $('#selected_conversations').html('');
                for(let index in selections){
                    let id = selections[index];
                    $('#selected_conversations').append($(`<input name="id[${index}]" value="${id}" type="hidden">'}`))
                }
            }
        });
        $('#add_to_contacts').click(function(){
            let selections = selected[selectedGroup];
            if(selections?.length){
                $('#selected_conversations_2').html('');
                for(let index in selections){
                    let id = selections[index];
                    $('#selected_conversations_2').append($(`<input name="id[${index}]" value="${id}" type="hidden">'}`))
                }
            }
        });
    </script>
    <script>
        $(document).ready(function(){
           $('[data-from-time][data-to-time]').each(function(){
               let fromId = $(this).find('[data-from-id]');
               let toId = $(this).find('[data-to-id]');
               let fromTime = $(this).data('fromTime');
               let toTime = $(this).data('toTime');
               let dater = function(date){
                   date = new Date(date);
                   if(isNaN(date)){
                       return '-';
                   }
                   let timestamp = date.getTime();
                   timestamp -= date.getTimezoneOffset() * 60000;
                   date = new Date(timestamp);
                   let hours = date.getHours().toString().padStart(2, '0');
                   let minutes = date.getMinutes().toString().padStart(2, '0');
                   let seconds = date.getSeconds().toString().padStart(2, '0');
                   let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
                   let month = months[date.getMonth();
                   let year = date.getFullYear().toString();
                   let day = date.getDate().toString().padStart(2, '0');
                   return [day, month, year, [hours, minutes, seconds].join(':')].join(' ');
               }
               fromId.text(dater(fromTime));
               toId.text(dater(toTime));
           });
        });
    </script>
    {{--    <script src="{{asset('js/pages/datatables.js?t=' . getLastJSTime())}}"></script>--}}
@endpush