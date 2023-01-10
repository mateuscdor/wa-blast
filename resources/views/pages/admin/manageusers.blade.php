@extends('layouts.app')

@section('title')
    User Manager
@endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
    {{--    <link href="{{asset('css/custom.css')}}" rel="stylesheet">--}}
    <style>
        i.info {
            background-color: var(--bs-primary);
            border-radius: 100px;
            text-align: center;
            width: 17px;
            margin-bottom: 2px;
            color: white;
            display: inline-flex;
            line-height: 0;
            justify-content: center;
            align-items: center;
            font-style: normal;
            font-size: 10px;
            height: 16px;
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
        @if(!Auth::user()->can_create_user)
            <x-alert>
                @slot('type', 'danger')
                @slot('msg', 'Your subscription expired, please renew to create a user')
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
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                            @foreach($levels as $index => $level)
                                <li class="nav-item{{!$index? " active": ''}}" role="presentation">
                                    <button class="nav-link{{!$index? " active": ''}}" id="nav_level_{{$level->id}}" data-bs-toggle="tab" data-bs-target="#tab_{{$level->id}}" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">
                                        {{$level->name}}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            @foreach($levels as $index => $level)
                                @php
                                    $users = $groups[$level->id] ?? [];
                                    $levelId = $level->id;
                                @endphp
                                <div class="tab-pane fade{{!$index? " show active": ''}}" id="tab_{{$levelId}}" role="tabpanel" aria-labelledby="nav_level_{{$level->id}}">
                                    @if(Auth::user()->can_create_user)
                                        <div class="d-flex justify-content-end mb-3">
                                            @if(!($levelId === \App\Models\Level::LEVEL_ADMIN && Auth::user()->level_id === \App\Models\Level::LEVEL_RESELLER && !Auth::user()->can_create_admin_account))
                                                <button onclick="addUser({{$level->id}}, '{{$level->name}}')" class="btn btn-primary">
                                                    Add {{$level->name}}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="row">
                                        <div class="col">
                                            <table class="display" id="datatable_{{$levelId}}"  width="100%">
                                                <thead>
                                                <tr>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Devices</th>
                                                    <th>Limit Devices</th>
                                                    @if($levelId === \App\Models\Level::LEVEL_RESELLER)
                                                        <th>Limit Akun Admin</th>
                                                    @endif
                                                    <th>Subscription</th>
                                                    <th>Expired at</th>
                                                    <th>Action</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($users as $user)

                                                    <tr>
                                                        <td>{{$user->username}}</td>
                                                        <td>{{$user->email}}</td>
                                                        <td align="middle">
                                                            <p class="mb-0">
                                                                <span class="badge badge-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Connected Devices">{{$user->total_device['connected']}}</span>
                                                                <span class="badge badge-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Disconnected Devices">{{$user->total_device['disconnected']}}</span>
                                                            </p>
                                                        </td>
                                                        <td>
                                                            {{$user->total_device['max']}}
                                                        </td>
                                                        @if($levelId === \App\Models\Level::LEVEL_RESELLER)
                                                            <td>{{Auth::user()->createdUsers()->where('level_id', \App\Models\Level::LEVEL_ADMIN)->count()}}/{{Auth::user()->limit_admin_account}}</td>
                                                        @endif
                                                        <td>
                                                            @if($user->is_expired_subscription)
                                                                <span class="badge badge-danger">Inactive</span>
                                                            @else
                                                                <span class="badge badge-success">{{$user->active_subscription}}</span>
                                                            @endif
                                                        </td>

                                                        <td>
                                                            @php
                                                                if($user->is_expired_subscription)
                                                                {
                                                                    echo '<span class="badge badge-danger">-</span>';
                                                                }
                                                                else
                                                                {
                                                                    if($user->active_subscription == 'active')
                                                                    {
                                                                        echo \Carbon\Carbon::createFromDate($user->subscription_expired)->format('Y-m-d');
                                                                    }
                                                                    else
                                                                    {
                                                                        echo '<span class="badge badge-danger">-</span>';
                                                                    }
                                                                }
                                                            @endphp
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                @if(Auth::user()->can_create_user)
                                                                    <button type="button" class="btn btn-primary btn-sm" onclick="editUser({{$user->id}}, {{$level->id}}, '{{$level->name}}')">
                                                                        Edit
                                                                    </button>
                                                                    <form action="{{route('user.delete',$user->id)}}" method="POST" onsubmit="return confirm('Are you sure will delete this user ? all data user also will deleted')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="id" value="{{$user->id}}">
                                                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                                    </form>
                                                                @else
                                                                    <button type="button" disabled="disabled" class="btn btn-primary btn-sm">Edit</button>
                                                                    <button type="button" disabled="disabled" class="btn btn-danger btn-sm">Delete</button>
                                                                @endif
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
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- Modal -->

    </div>
    @if(\Illuminate\Support\Facades\Auth::user()->level_id === \App\Models\Level::LEVEL_SUPER_ADMIN)
        @include('.components.modals.user-modal', ['id' => 'modal_user_' . \App\Models\Level::LEVEL_SUPER_ADMIN, 'levelId' => \App\Models\Level::LEVEL_SUPER_ADMIN])
        @include('.components.modals.user-modal', ['id' => 'modal_user_' . \App\Models\Level::LEVEL_RESELLER, 'limit' => true, 'max_admin_account' => true, 'levelId' => \App\Models\Level::LEVEL_RESELLER])
    @endif

    @if(\Illuminate\Support\Facades\Auth::user()->level_id <= \App\Models\Level::LEVEL_RESELLER)
        @include('.components.modals.user-modal', ['id' => 'modal_user_' . \App\Models\Level::LEVEL_ADMIN, 'modalPackages' => $adminPackages, 'levelId' => \App\Models\Level::LEVEL_ADMIN])
    @endif

    @if(\Illuminate\Support\Facades\Auth::user()->level_id <= \App\Models\Level::LEVEL_ADMIN)
        @include('.components.modals.user-modal', ['id' => 'modal_user_' . \App\Models\Level::LEVEL_CUSTOMER_SERVICE, 'levelId' => \App\Models\Level::LEVEL_CUSTOMER_SERVICE, 'subscription' => false])
    @endif
@endsection

@push('scripts')

    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script src="{{asset('js/pages/datatables.js?t=4'.\Illuminate\Support\Str::random())}}"></script>


    <script>
        function addUser(id, label){
            let elementId = 'modal_user_' + id;
            $(`#${elementId}_title`).html('Add ' + label);
            $(`#${elementId}_button`).html('Submit');
            $(`#${elementId}_label_password`).html('Password');
            $(`#form_${elementId}`).attr('action', '{{route('user.store')}}');

            $(`#${elementId}_user_username`).val('');
            $(`#${elementId}_user_email`).val('');
            $(`#${elementId}_user_display_name`).val('');
            $(`#${elementId}_user_password`).val('');
            $(`#${elementId}_user_phone_number`).val('');
            $(`#${elementId}_user_active_subscription`).val('inactive');
            $(`#${elementId}_user_subscription_expired`).val('');
            $(`#${elementId}_user_limit_device`).val('');
            $(`#${elementId}_user_max_admin_account`).val('');
            $(`#${elementId}_user_id`).val('');
            $(`#${elementId}_user_package_id`).val('');

            $(`#${elementId}`).modal('show');
        }

        function editUser(id, levelId, levelLabel){

            let elementId = 'modal_user_' + levelId;

            // return;
            $(`#${elementId}_title`).html('Edit ' + levelLabel);
            $(`#form_${elementId}`).attr('action', '{{route('user.update')}}');
            $(`#${elementId}`).modal('show');
            $.ajax({
                url: "{{route('user.edit')}}",
                type: "GET",
                data: {id:id},
                dataType: "JSON",
                success: function(data) {
                    $(`#${elementId}_label_password`).html('Password *(leave blank if not change)');
                    $(`#${elementId}_user_username`).val(data.username);
                    $(`#${elementId}_user_email`).val(data.email);
                    $(`#${elementId}_user_display_name`).val(data.display_name);
                    $(`#${elementId}_user_password`).val('');
                    $(`#${elementId}_user_phone_number`).val(data.phone_number);
                    $(`#${elementId}_user_active_subscription`).val(data.active_subscription);
                    $(`#${elementId}_user_subscription_expired`).val(data.subscription_expired? data.subscription_expired.substring(0, 10): '');
                    $(`#${elementId}_user_id`).val(data.id);
                    $(`#${elementId}_user_max_admin_account`).val(data.limit_admin_account);
                    $(`#${elementId}_user_package_id`).val(data.package_id);
                    $(`#${elementId}_user_limit_device`).val(data.limit_device);
                }
            });
        }
    </script>
@endpush



