@extends('layouts.app')

@section('title')
    Create Campaign
@endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">

    <script src="{{asset('js/pages/datatables.js')}}"></script>
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
@endpush

@section('content')
    @if (session()->has('alert'))
        <x-alert>
            @slot('type',session('alert')['type'])
            @slot('msg',session('alert')['msg'])
        </x-alert>
    @endif
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="card">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-header">
                                    <h3 class="card-title">Blast</h3>
                                </div>
                            </div>
                        </div>
                        <form  id="form" method="POST">
                            @csrf
                            <div class="card-body">
                                @if(!Session::has('selectedDevice'))
                                    {{-- please select deviec --}}
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-danger" role="alert">
                                                <strong>Please select device first</strong>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- title, form campaign  --}}
                                    {{-- make form sender,tag  --}}

                                    {{-- make 2 form flex --}}
                                    <div class="row">
                                        <div class="col d-flex gap-2 flex-column">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="name" class="form-label">Campaign Name</label>
                                                        <input type="text" required class="form-control" id="name" name="name" placeholder="Campaign 1">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="tag" class="form-label">Sender</label>
                                                        <input type="text" class="form-control" value="{{Session::get('selectedDevice')}}" id="sender" name="sender" placeholder="Sender" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tagsOption">
                                                <label for="inputEmail4" class="form-label">Phone Book</label>
                                                <select name="tag" required id="tag" class="form-control" style="width: 100%;">
                                                    <option value="">Pilih Phone Book</option>
                                                    @foreach ($tags as $tag)
                                                        <option value="{{$tag->id}}">{{$tag->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            {{-- time form, now or schedule --}}
                                            <div class="d-flex justify-content-rounded gap-2">

                                                <div class="col ">
                                                    <label for="tipe" class="form-label">Type</label>
                                                    <select name="tipe" id="tipe" class="form-control" style="width: 100%;">
                                                        <option value="immediately">Immediately</option>
                                                        <option value="schedule">Schedule</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row d-none" id="datetime">
                                                <div class="col-sm-6">
                                                    <label for="datetime2" class="form-label">Date</label>
                                                    <input type="date" id="datetime2" name="date" class="form-control">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="datetime3" class="form-label">Time</label>
                                                    <input type="time" id="datetime3" name="time" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <label for="delay" class="form-label">Delay (seconds)</label>
                                                    <input type="number" required value="3" id="delay" min="1" max="60" name="delay" class="form-control"  required>
                                                </div>
                                            </div>

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
                                                <div class="col">
                                                    @include('components.creators.reply-creator')
                                                </div>
                                            </div>
                                            <div id="message_templates">
                                                <div>
                                                    <label for="msg_template" class="form-label">Message Template</label>
                                                    <select name="message_template" id="msg_template" class="form-control" style="width: 100%;">
                                                        <option value="">Choose a template...</option>
                                                        @foreach($templates as $template)
                                                            <option value="{{$template->id}}">{{$template->label}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- button start --}}
                                            <div class="row">
                                                <div class="col-md-12 mt-5">
                                                    <button id="startBlast" type="submit" class="btn btn-success">Start</button>
                                                </div>
                                            </div>
                                        </div>

                                        @endif

                                    </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script src="{{asset('js/autoreply.js')}}"></script>

    <script>

        let isUsingCreatedTemplate = false;
        $('#message_templates').hide();

        // oncange, if tipe schedule datetime show
        $('#tipe').on('change', function() {
            if (this.value == 'schedule') {
                $('#datetime').removeClass('d-none');
            } else {
                $('#datetime').addClass('d-none');
            }
        });

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

            $('#startBlast').attr('disabled',true);
            $('#startBlast').html('Sending...');

            let data;
            if(isUsingCreatedTemplate){
                data = {
                    template_id: $('#msg_template').val(),
                }
            } else {
                data = getAllValues();
            }

            data.tag = $('#tag').val();
            data.sender = $('#sender').val();
            data.name = $('#name').val();
            data.start_date = $('#datetime2').val();
            data.start_time = $('#datetime3').val();
            data.delay = $('#delay').val();

            const url = '{{route('blast')}}';

            $.ajax({
                method : 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url : url,
                data : data,
                dataType : 'json',
                success : (result) => {
                    // window.location = '';
                    window.location.href = '{{route('campaign.lists')}}'
                    $('#startBlast').attr('disabled',false);
                    $('#startBlast').html('Start');
                },
                error : (err) => {
                    //console.log(err);
                    window.location = '';
                    // console.log(err);
                    $('#startBlast').attr('disabled',false);
                    $('#startBlast').html('Start');
                }
            })
        })
    </script>
@endpush