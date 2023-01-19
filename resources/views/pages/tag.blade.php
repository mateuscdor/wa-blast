@extends('layouts.app')

@section('title')
    Phone Book
@endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
    <style>
        .group__item {
            background-color: #0AAEB3;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            cursor: pointer;
            align-items: center;
        }
        .group__item:hover {
            background-color: #0C9A9A;
        }
        .group__item .subject__group {
            display: flex;
            flex-grow: 1;
            flex-direction: column;
            gap: 2px;
            -webkit-column-gap: 2px;
            -moz-column-gap: 2px;
        }
        .group__item .subject {
            color: #ffffff;
            font-size: 16px;
            margin-bottom: 0;
        }
        .group__item .participants {
            color: #d8dfec;
            font-size: 14px;
            margin-bottom: 0;
        }
        .group__item .participant_count {
            font-size: 14px;
            color: #ffffff;
            font-weight: 400;
            margin-bottom: 0;
        }
        .form-check-input[type=checkbox] {
            margin-top: 0;
        }
        .group__item.selected {
            background-color: #077275;
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
        <div class="alert alert-outline-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="card-header d-flex justify-content-between">

        <button id="btn-fetch-groups" type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#selectNomor"><i class="material-icons-outlined">contacts</i>Fetch From Groups WA</button>
        <div class="d-flex justify-content-right">

            <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#addTag"><i class="material-icons-outlined">add</i>Add</button>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title">Tags</h5>
                    <div class="dropdown d-none" id="dropdown_actions">
                        <a class="btn btn-warning btn-sm dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </a>

                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <span id="tag_delete_modal_button" class="text-danger bg-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#modal-delete-confirm">
                                        Delete Tags
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- <button type="button" class="btn btn-danger " data-bs-toggle="modal" data-bs-target="#selectNomor"><i class="material-icons-outlined">contacts</i>Hapus semua</button>
                    <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#selectNomor"><i class="material-icons-outlined">contacts</i>Generate Kontak</button>
                    <div class="d-flex justify-content-right">
                        <form action="" method="POST">
                            <button type="submit" name="export" class="btn btn-warning "><i class="material-icons">download</i>Export (xlsx)</button>
                        </form>
                        <button type="button" class="btn btn-primary mx-2" data-bs-toggle="modal" data-bs-target="#importExcel"><i class="material-icons-outlined">upload</i>Import (xlsx)</button>
                        <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#addNumber"><i class="material-icons-outlined">add</i>Tambah</button>
                    </div> -->
                </div>
                <div class="card-body">
                    <table id="datatable1" class="display" style="width:100%">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th class="d-flex justify-content-center">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($tags as $tag)

                            <tr data-id="{{$tag->id}}">
                                <td>{{$tag->name}}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a data-stop-propagation class="btn btn-success btn-sm" href="/contact/{{$tag->id}}">View Contacts</a>
                                        <button class="btn btn-warning btn-sm" data-edit-button data-tag-id="{{$tag->id}}" data-tag-label="{{$tag->name}}" data-stop-propagation>
                                            Edit
                                        </button>
                                        <form action="{{route('tag.delete')}}" method="POST" onsubmit="return confirm('do you sure want to delete this tag? ( All contacts in this tag also will delete! )')">
                                            @method('delete')
                                            @csrf
                                            <input type="hidden" name="id" value="{{$tag->id}}">
                                            <button type="submit" data-stop-propagation name="delete" class="btn btn-danger btn-sm">Delete</button>
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


    <div class="modal fade" id="addTag" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('tag.store')}}" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" id="name" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Delete Tags</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('tags.delete.selected')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>
                            Are you sure want to delete <span id="tag_count">0</span> tags?
                        </p>
                        <div id="selected_tags"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal edit --}}
    <div class="modal fade" id="edit_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('tag.store')}}" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="id" id="edit_tag_id">
                        <label for="name" class="form-label">New Name</label>
                        <input type="text" name="name" class="form-control" id="edit_tag_label" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal select sender --}}
    <div class="modal fade" id="selectNomor" tabindex="-1" aria-labelledby="SelectNomorModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Select Groups</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('fetch.groups')}}" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <label for="" class="form-label">Sender</label>
                        @if(Session::has('selectedDevice'))
                            <input type="text" name="sender" class="form-control" id="sender" value="{{Session::get('selectedDevice')}}" readonly>
                        @else
                            <input type="text" name="senderrr" value="Please Select Sender First" class="form-control" id="sender" required>
                        @endif
                        <div class="d-flex flex-column mt-2 rounded" style="max-height: 300px; overflow: auto" id="group__container">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{asset('js/pages/datatables.js')}}"></script>
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script>
        let selectedGroup = '';
        let selected = {};
        let isFetched = false;

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
        $('#tag_delete_modal_button').click(function(){
            $('#selected_tags').html('')
            for(let index in selected[selectedGroup]){
                let id = selected[selectedGroup][index];
                $('#selected_tags').append($(`<input type="hidden" name="id[${index}]" value="${id}"/>`))
           }
           $('#tag_count').text(selected[selectedGroup].length);
        });
        $('[data-stop-propagation]').click(function(e){
           e.stopPropagation();
        });
        $('[data-edit-button]').click(function(){
           let btn = $(this);
           let tagId = btn.data('tagId');
           let tagLabel = btn.data('tagLabel');
           $('#edit_tag_id').val(tagId);
           $('#edit_tag_label').val(tagLabel);
           $('#edit_modal').modal('show');
        });
        $('#btn-fetch-groups').click(function(){
            if(!isFetched){
                $.ajax({
                    url: '{{route('tag.groups.fetch')}}',
                    method: 'POST',
                    data: {
                        _token: '{{csrf_token()}}',
                        sender: '{{session()->get('selectedDevice')}}'
                    },
                    success(r){
                        if(isFetched){
                            return;
                        }
                        isFetched = true;
                        for(let {id, subject, participantLength} of r.data){
                            $('#group__container').append(`<label data-group-id="${id}" class="group__item">
                                <div class="d-flex align-items-center gap-2 pl-3">
                                    <input id="group_id_${id}" value="${id}" name="group_ids[]" class="form-check-input" type="checkbox">
                                    <div class="subject__group">
                                        <p class="subject">
                                            ${subject}
                                        </p>
                                    </div>
                                </div>
                                <p class="participant_count">
                                    ${participantLength} participants
                                </p>
                            </label>`);
                            $('[type="checkbox"][id^="group_id"]').click(function(e){
                                let groupId = $(this).attr('id').replace('group_id_', '');
                                if($(this).prop('checked')){
                                    $(`[data-group-id="${groupId}"]`).addClass('selected');
                                } else {
                                    $(`[data-group-id="${groupId}"]`).removeClass('selected');
                                }
                            });
                        }
                        console.log(r.data);
                    },
                    error(e){

                    }
                })
            }
        });
    </script>
@endpush