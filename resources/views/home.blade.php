@extends('layouts.app')

@section('title')
    Home
@endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
    <style>
        td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="content-wrapper">
        <div>

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
            <x-alert>
                @if(\Illuminate\Support\Facades\Auth::user()->level_id === \App\Models\Level::LEVEL_SUPER_ADMIN)
                    @slot('type', 'success')
                    @slot('msg', 'You are super admin! You have no expiry date.')
                @else
                    @slot('type', Auth::user()->is_expired_subscription ? 'danger' : 'success')
                    @slot('msg', "Subscription : " . Auth::user()->expired_subscription)
                @endif
            </x-alert>
            <div class="row">
                {{-- text danger subscription --}}



                <div class="col-xl-6">
                    <div class="card widget widget-stats">
                        <div class="card-body">
                            <div class="widget-stats-container d-flex">
                                <div class="widget-stats-icon widget-stats-icon-primary">
                                    <i class="material-icons-outlined">contacts</i>
                                </div>
                                <div class="widget-stats-content flex-fill">
                                    <span class="widget-stats-title">All Contacts</span>
                                    <span class="widget-stats-amount">{{ Auth::user()->contacts()->count()}}</span>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card widget widget-stats">
                        <div class="card-body">
                            <div class="widget-stats-container d-flex">
                                <div class="widget-stats-icon widget-stats-icon-warning">
                                    <i class="material-icons-outlined">message</i>
                                </div>
                                <div class="widget-stats-content flex-fill">
                                    <span class="widget-stats-title">Blast Message</span>

                                    <span class="widget-stats-info">{{Auth::user()->blasts()->where(['status' => 'success'])->count()}} Success</span>
                                    <span class="widget-stats-info">{{Auth::user()->blasts()->where(['status' => 'failed'])->count()}} Failed</span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="col-xl-4">
            <div class="card widget widget-stats">
                <div class="card-body">
                    <div class="widget-stats-container d-flex">
                        <div class="widget-stats-icon widget-stats-icon-danger">
                            <i class="material-icons-outlined">schedule</i>
                        </div>
                        <div class="widget-stats-content flex-fill">
                            <span class="widget-stats-title">Pesan jadwal</span>

                            <span class="widget-stats-info">0 Sukses</span>
                            <span class="widget-stats-info">0 Gagal</span>
                            <span class="widget-stats-info">0 Pending</span>
                        </div>

                    </div>
                </div>
            </div>
        </div> --}}
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-4 mb-3 justify-content-between">
                        <div class="d-flex gap-2">
                            <h5 class="">Whatsapp Devices</h5>
                            <small class="text-warning">*You have {{$limit_device}} limit whatsapp device</small>
                        </div>
                        @if($limit_device === count($numbers))
                            <button type="button" class="btn btn-primary" disabled="disabled">Create Device</button>
                        @else
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDevice">Create Device</button>
                        @endif
                    </div>
                    <table class="display mt-2" style="width: 100%">
                        <thead>
                        <tr>
                            <th>Number</th>
                            <th>Webhook URL</th>
                            <th>Messages Sent</th>
                            <th>Status</th>
                            <th>API Key</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($numbers as $number)
                            <tr>
                                <td>{{$number['body']}}</td>
                                <td>
                                    <form action="" method="post">
                                        @csrf
                                        <input type="text" class="form-control form-control-solid-bordered" data-id="{{$number['body']}}" name="" value="{{$number['webhook']}}">
                                    </form>
                                </td>
                                <td>{{$number['messages_sent']}}</td>
                                <td><span class="badge badge-{{ $number['status'] == 'Connected' ? 'success' : 'danger'}}">{{$number['status']}}</span></td>
                                <td>{{$number['api_key']}}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{route('scan',$number->body)}}" class="btn btn-warning btn-sm">Scan<i style="margin-right: 0; margin-left: 2px;" class="material-icons pl-2 mr-0">qr_code</i></a>
                                        <form action="{{route('deleteDevice')}}" method="POST">
                                            @method('delete')
                                            @csrf
                                            <input name="deviceId" type="hidden" value="{{$number['id']}}">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete<i style="margin-right: 0; margin-left: 0;" class="material-icons">delete_outline</i></button>
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
    <div class="modal fade" id="addDevice" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route('addDevice')}}" method="POST">
                    <div class="modal-body">
                        @csrf
                        <label for="sender" class="form-label">Device Number</label>
                        <input type="number" id="sender" name="sender" class="form-control" required>
                        <small class="text-small text-danger">*Use Country Code ( without + )</small><br>
                        <label for="urlwebhook" class="form-label mt-1">Link webhook</label>
                        <input type="text" name="urlwebhook" class="form-control" id="urlwebhook">
                        <small class="text-danger">*Optional</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit"  name="submit" class="btn btn-primary">Save</button>
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
        var typingTimer;                //timer identifier
        var doneTypingInterval = 1000;
        $('#webhook').keydown(function(){
            clearTimeout(typingTimer);

            typingTimer = setTimeout(function(){
                $.ajax({
                    method : 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url : '{{route('setHook')}}',
                    data : {
                        number : $('#webhook').data('id'),
                        webhook : $('#webhook').val()
                    },
                    dataType : 'json',
                    success : (result) => {

                    },
                    error : (err) => {
                        console.log(err);
                    }
                })
            }, doneTypingInterval);
        })
    </script>

@endpush