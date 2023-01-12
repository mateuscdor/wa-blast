@extends('layouts.app')

@section('title')
    Contacts
@endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
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
        <form action="{{route('deleteAll')}}" method="POST">
            @method('delete')
            @csrf
            <input type="hidden" name="tag" value="{{$tag->id}}">
            <button type="submit" name="deleteAll" class="btn btn-danger "><i class="material-icons-outlined">contacts</i>Delete All</button>
        </form>
        {{--   <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#selectNomor"><i class="material-icons-outlined">contacts</i>Generate Kontak</button> --}}
        <div class="d-flex justify-content-right gap-2">
            <form action="{{route('exportContact')}}" method="POST">
                @csrf
                <input type="hidden" name="tag" value="{{$tag->id}}">
                <button type="submit" name="" class="btn btn-warning "><i class="material-icons">download</i>Export (xlsx)</button>
            </form>
            <div class="dropdown">
                <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Import
                </a>

                <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importContacts">
                            Import Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{route('contact.import.contacts', $tag->id)}}">
                            Import From Contacts
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addNumber">
                            Add Manually
                        </a>
                    </li>
                    <li>

                    </li>
                </ul>
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-3">Contact lists from <span class="badge badge-primary">{{$tag->name}}</span></h5>
                    <!-- <button type="button" class="btn btn-danger " data-bs-toggle="modal" data-bs-target="#selectNomor"><i class="material-icons-outlined">contacts</i>Hapus semua</button>
                    <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#selectNomor"><i class="material-icons-outlined">contacts</i>Generate Kontak</button>
                    <div class="d-flex justify-content-right">
                        <form action="" method="POST">
                            <button type="submit" name="export" class="btn btn-warning "><i class="material-icons">download</i>Export (xlsx)</button>
                        </form>
                        <button type="button" class="btn btn-primary mx-2" data-bs-toggle="modal" data-bs-target="#importExcel"><i class="material-icons-outlined">upload</i>Import (xlsx)</button>
                        <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#addNumber"><i class="material-icons-outlined">add</i>Tambah</button>
                    </div> -->
                    <div class="dropdown d-none" id="dropdown_actions">
                        <a class="btn btn-warning btn-sm dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </a>

                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                            <li>
                                <a class="dropdown-item text-danger bg-outline-danger" href="#" id="contact_delete_modal_button" data-bs-toggle="modal" data-bs-target="#modal-delete-confirm">
                                    Delete
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <table id="datatable1" class="display" style="width:100%">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Number</th>
                            {{-- <th>Tag</th> --}}
                            <th class="d-flex justify-content-center">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($contacts as $contact)

                            <tr data-id="{{$contact->id}}">
                                <td>{{$contact->name}}</td>
                                <td>{{$contact->number}}</td>
                                {{-- <td><span class="badge badge-primary">{{$contact->tag->name}}</span></td> --}}
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                         <button data-edit-id="{{$contact->id}}" data-edit-number="{{$contact->number}}" data-edit-name="{{$contact->name}}" data-bs-target="#modal-edit" data-stop-propagation data-bs-toggle="modal" class="btn btn-warning btn-sm">
                                             Edit
                                         </button>
                                        <form action="{{route('contactDeleteOne',$contact->id)}}" method="POST">
                                            @method('delete')
                                            @csrf
                                            <input type="hidden" name="id" value="{{$contact->id}}">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm"><i class="material-icons">delete_outline</i>Delete</button>
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
    <div class="modal fade" id="addNumber" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('contact.store')}}" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" id="name" required>
                        <label for="number" class="form-label">Number</label>
                        <input type="number" name="number" class="form-control" id="number" required>
                        <input type="hidden" name="tag" value="{{$tag->id}}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-edit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('contact.update')}}" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="id" value="" id="contact_edit_id"/>
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" id="contact_edit_name" required>
                        <label for="number" class="form-label">Number</label>
                        <input type="number" name="number" class="form-control" id="contact_edit_number" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importContacts" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('importContacts')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                <div class="modal-body">
                    <label for="fileContacts" class="form-label">Excel File</label>
                    <input type="file" name="fileContacts" class="form-control" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" id="fileContacts" required>

                    <input type="hidden" name="tag" value="{{$tag->id}}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit" class="btn btn-primary">Tambah</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Delete Contacts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('contacts.delete.selected')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>
                            Are you sure want to delete <span id="selection_count">0</span> contacts?
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
    <script src="{{asset('js/pages/datatables.js?t=' . getLastJSTime())}}"></script>
    <script>
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
        $('#contact_delete_modal_button').click(function(){
            $('#selection_ids').html('')
            for(let index in selected[selectedGroup]){
                let id = selected[selectedGroup][index];
                $('#selection_ids').append($(`<input type="hidden" name="id[${index}]" value="${id}"/>`))
            }
            $('#selection_count').text(selected[selectedGroup].length);
        });
        $('[data-stop-propagation]').click(function(e){
            e.stopPropagation();
        });
        $('button[data-edit-id]').click(function(){
           let el = $(this);
           let id = el.data('editId');
           let name = el.data('editName');
           let number = el.data('editNumber');
           $('#contact_edit_id').val(id);
           $('#contact_edit_name').val(name);
           $('#contact_edit_number').val(number);
        });
    </script>
@endpush