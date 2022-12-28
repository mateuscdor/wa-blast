@extends('layouts.app')

@section('title')
    Templates
@endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
    <link href="{{asset('plugins/select2/css/select2.css')}}" rel="stylesheet">
    <link href="{{asset('css/custom.css')}}" rel="stylesheet">
@endpush

@section('content')
    <div class="row mt-4">
        <div class="col">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title">Templates</h5>

                    <div class="d-flex">
                        <a href="{{route('template.create')}}" class="btn btn-primary">
                            Add Template
                        </a>
                    </div>

                </div>
                <div class="card-body">
                    <table id="datatable1" class="display" style="width:100%">
                        <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Related Campaigns</th>
                             <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($templates as $template)
                            <tr>
                                <td>{{$template->label}}</td>
                                <td>{{$template->related_campaigns_count}}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{route('template.edit', $template->id)}}" class="btn btn-primary btn-sm">
                                            Edit
                                        </a>
                                        <form action="{{route('template.delete', $template->id)}}" method="POST" onsubmit="return confirm('Are you sure will delete this template?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="id" value="{{$template->id}}">
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
    <script src="{{asset('js/pages/datatables.js')}}"></script>
@endpush