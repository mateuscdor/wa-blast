@extends('layouts.app')

@section('title')
    Templates
@endsection

@push('head')

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
                    <link href="{{asset('plugins/select2/css/select2.css')}}" rel="stylesheet">
                    <div class="card">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-header">
                                    <h3 class="card-title">Blast</h3>
                                </div>
                            </div>
                        </div>
                        <form id="form" method="POST">
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

                                            {{--                                            <div class="ajaxplace mt-5"></div>--}}
                                            <div>
                                                <label for="template_name" class="form-label">Template Name</label>
                                                <input name="name" id="template_name" class="form-control" tabindex="-1" required>
                                            </div>
                                            @isset($template)
                                                @include('components.creators.reply-creator', ['initial' => $template])
                                            @else
                                                @include('components.creators.reply-creator')
                                            @endisset
                                            {{-- button start --}}
                                            <div class="row">
                                                <div class="col-md-12 mt-5">
                                                    <button id="startBlast" type="submit" class="btn btn-success">
                                                        @isset($template)
                                                            Update
                                                        @else
                                                            Create
                                                        @endisset
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                @endif

                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')

    <script src="{{asset('js/autoreply.js?t=' . \Illuminate\Support\Str::random())}}"></script>
    <script src="{{asset('plugins/select2/js/select2.full.min.js')}}"></script>



    {{--    <script src="{{asset('js/pages/select2.js')}}"></script>--}}
    <script>


        // oncange, if tipe schedule datetime show
        $('#tipe').on('change', function() {
            if (this.value == 'schedule') {
                $('#datetime').removeClass('d-none');
            } else {
                $('#datetime').addClass('d-none');
            }
        });

        @isset($template)
            $('#template_name').val('{{$template->label}}')
        @endisset

        $('#form').on('submit', function(e){
            e.preventDefault();

            const data = getAllValues();

            data.label = $('#template_name').val();
            const url = '{{isset($template)? route('template.update', $template->id): route('template.store')}}';

            $.ajax({
                method : 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url : url,
                data : data,
                dataType : 'json',
                success : (result) => {
                    window.location.href = '{{route('template.lists')}}'
                },
                error : (err) => {
                    //console.log(err);
                    window.location = '';
                }
            })
        })
    </script>
@endpush