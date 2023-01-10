@extends('layouts.app')

@section('title')
    Auto Replies
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
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title">Histories</h5>

                </div>
                <div class="card-body">
                    <table id="datatable1" class="display" style="width:100%">
                        <thead>
                        <tr>
                            <th>Receiver</th>
                            <th>Status</th>
                            <th>Last Update</th>
                            {{-- <th class="d-flex justify-content-center">Action</th> --}}
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($histories as $history)

                            <tr>
                                <td>{{$history->receiver}}</td>
                                <td id="blast_status_{{$history->id}}">
                                    @if($history->status == 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($history->status == 'success')
                                        <span class="badge badge-success">Success</span>
                                    @elseif($history->status == 'failed')
                                        <span class="badge badge-danger">Failed</span>
                                    @endif
                                </td>
                                <td id="blast_updated_{{$history->id}}">{{$history->updated_at}}</td>

                            </tr>
                        @endforeach


                        </tbody>
                        <tfoot></tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script src="{{asset('js/pages/datatables.js')}}"></script>
    <script src="{{asset('js/autoreply.js')}}"></script>
    <script>
        function convertTZ(date, tzString) {
            return new Date((typeof date === "string" ? new Date(date) : date).toLocaleString("en-US", {timeZone: tzString}));
        }
        function convertDate(date){
            let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            let dates = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            let month = months[date.getMonth()];
            let day = dates[date.getDay()];
            let year = date.getFullYear();
            let hours = `${date.getHours()}`.padStart(2, '0');
            let minutes = `${date.getMinutes()}`.padStart(2, '0');
            let seconds = `${date.getSeconds()}`.padStart(2, '0');

            return [`${date.getDate()}`.padStart(2, '0'), month, year, [hours, minutes, seconds].join(':')].join(' ');
        }
        const refreshData = function(){
            $.ajax({
                url: '{{route('blastDatatable', $campaign_id)}}',
                type: 'GET',
                dataType: 'json',
                success: (result) => {
                    for(let history of result.histories){
                        if(history.status === 'pending'){
                            $('#blast_status_' + history.id).html($('<span class="badge badge-warning">Pending</span>'))
                        } else if(history.status === 'success'){
                            $('#blast_status_' + history.id).html($('<span class="badge badge-success">Success</span>'))
                        } else {
                            $('#blast_status_' + history.id).html($('<span class="badge badge-danger">Failed</span>'))
                        }
                        let timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        let date = convertDate(new Date(history.updated_at));
                        $('#blast_updated_' + history.id).text(date);

                    }
                },
                error: (error) => {
                    console.log(error);
                }
            })
        }
        setInterval(()=>{
            refreshData();
        }, 1000)
    </script>
@endpush

