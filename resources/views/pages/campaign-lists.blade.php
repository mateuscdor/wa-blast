@extends('layouts.app')

@push('title')
    Campaigns
@endpush

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
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

                    <div class="d-flex">

                        <form action="{{route('campaigns.delete.all')}}" method="POST">
                            @method('delete')
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Delete All</button>
                        </form>
                    </div>

                </div>
                <div class="card-body">
                    <table id="datatable1" class="display" style="width:100%">
                        <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Receiver</th>
                            <th>Message</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            {{-- <th class="d-flex justify-content-center">Action</th> --}}
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($campaigns as $campaign)

                            <tr>
                                <td>{{$campaign->sender}}</td>
                                <td>{{$campaign->name}}</td>
                                <td><span class="badge badge-secondary badge-sm text-warning">{{$campaign->type}}</span></td>
                                <td>
                                    <span id="blasts_count_{{$campaign->id}}">{{$campaign->blasts_count}}</span><span class="badge badge-primary">total</span>
                                    <br>
                                    <span id="blasts_success_{{$campaign->id}}">{{$campaign->blasts_success}}</span><span class="badge badge-success">Success</span>
                                    <br>
                                    <span id="blasts_failed_{{$campaign->id}}">{{$campaign->blasts_failed}}</span><span class="badge badge-danger">Failed</span>
                                    <br>
                                    <span id="blasts_pending_{{$campaign->id}}">{{$campaign->blasts_pending}}</span><span class="badge badge-warning">Waiting</span>
                                    {{-- button view blasts list --}}
                                    <br>
                                    <a href="{{route('blastHistories',$campaign->id)}}" class="btn btn-primary btn-sm mt-1">View All</a>
                                </td>
                                <td><button class="btn btn-primary" onclick="viewCampaignMessage({{$campaign->id}})">View</button></td>

                                <td>{{$campaign->schedule ?? '-'}}</td>
                                <td >
                                    {{-- if status success badge success, if waiting badge warning if failed badge danger --}}
                                    <span id="blasts_status_{{$campaign->id}}" class="badge badge-{{$campaign->status === 'finish' ? 'success' : ($campaign->status === 'waiting' ? 'warning' : ($campaign->status === 'failed' ? 'danger' : 'primary'))}}">{{$campaign->status}}</span>
                                    {{--  icon pause --}}
                                    <div id="blasts_resume_{{$campaign->id}}" class="{{!($campaign->status === 'processing' || $campaign->status === 'waiting') ? 'd-none': ''}}">
                                        <button onclick="pauseCampaign({{$campaign->id}})" class="btn btn-warning btn-sm"><i class="material-icons">pause</i></button>
                                    </div>
                                    {{-- icon play --}}
                                    <div id="blasts_pause_{{$campaign->id}}" class="{{!($campaign->status === 'paused') ? 'd-none': ''}}">
                                        <button onclick="resumeCampaign({{$campaign->id}})" class="btn btn-success btn-sm"><i class="material-icons">play_arrow</i></button>
                                    </div>
                                    {{-- icon delete --}}

                                </td>

                                {{-- <td class="d-flex justify-content-center">
                                    <a href="{{route('editBlast',$campaign->id)}}" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="{{route('deleteBlast',$campaign->id)}}" method="POST">
                                        @method('delete')
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td> --}}
                            </tr>
                        @endforeach


                        </tbody>
                        <tfoot></tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modalView" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Message Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body previewCampaignMessage">
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
{{--    <script src="{{asset('js/pages/datatables.js')}}"></script>--}}
    <script src="{{asset('js/autoreply.js')}}"></script>
    <script>

        const table = $('#datatable1').DataTable();

        function viewCampaignMessage(id) {
            $.ajax({
                url: `/campaign/show/${id}`,
                type: 'GET',
                dataType: 'html',
                success: (result) => {

                    $('.previewCampaignMessage').html(result);
                    $('#modalView').modal('show')
                },
                error: (error) => {
                    console.log(error);
                }
            })
        }

        function pauseCampaign(id) {
            $.ajax({
                url: `/campaign/pause/${id}`,
                type: 'POST',
                dataType: 'json',
                success: (result) => {
                    location.reload();
                },
                error: (error) => {
                    console.log(error);
                }
            })
        }

        function resumeCampaign(id) {
            $.ajax({
                url: `/campaign/resume/${id}`,
                type: 'POST',
                dataType: 'json',
                success: (result) => {
                    location.reload();
                },
                error: (error) => {
                    console.log(error);
                }
            })
        }

        const refreshCampaigns = function(){
            $.ajax({
                url: `/campaign/datatable`,
                type: 'GET',
                dataType: 'json',
                success: (result) => {
                    for(let campaign of result.data){
                        $('#blasts_count_' + campaign.id).text(campaign.blasts_count);
                        $('#blasts_pending_' + campaign.id).text(campaign.blasts_pending);
                        $('#blasts_success_' + campaign.id).text(campaign.blasts_success);
                        $('#blasts_failed_' + campaign.id).text(campaign.blasts_failed);
                        if(['finish', 'failed'].includes(campaign.status)){
                            $('#blasts_resume_' + campaign.id).addClass('d-none');
                            $('#blasts_pause_' + campaign.id).addClass('d-none');

                            if(campaign.status === 'failed'){
                                $('#blasts_status_' + campaign.id).attr('class', 'badge badge-danger');
                            } else {
                                $('#blasts_status_' + campaign.id).attr('class', 'badge badge-success');
                            }

                        } else if(['processing', 'waiting'].includes(campaign.status)){
                            $('#blasts_resume_' + campaign.id).removeClass('d-none');
                            $('#blasts_pause_' + campaign.id).addClass('d-none');
                            $('#blasts_status_' + campaign.id).attr('class', 'badge badge-warning');
                        } else {
                            $('#blasts_resume_' + campaign.id).addClass('d-none');
                            $('#blasts_pause_' + campaign.id).removeClass('d-none');
                            $('#blasts_status_' + campaign.id).attr('class', 'badge badge-warning');
                        }
                        $('#blasts_status_' + campaign.id).text(campaign.status);
                    }
                },
                error: (error) => {
                    console.log(error);
                }
            })
        }

        setInterval(()=>{
            refreshCampaigns();
        }, 1000);

    </script>
@endpush





