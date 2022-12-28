@extends('layouts.app')

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
    <script>
        let packages = [
            ...{!!json_encode($adminPackages)!!},
            ...{!!json_encode($resellerPackages)!!}
        ]
    </script>
    <style>
        .ml-auto {
            margin-left: auto;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">

                    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="nav_level_reseller" data-bs-toggle="tab" data-bs-target="#reseller_packages" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">
                                Paket Reseller
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="nav_level_admin" data-bs-toggle="tab" data-bs-target="#admin_packages" type="button" role="tab" aria-controls="hoaccountme" aria-selected="true">
                                Paket Admin
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="reseller_packages" role="tabpanel" aria-labelledby="nav_level_reseller">
                            <div class="d-flex justify-content-end mb-3">
                                <button onclick="addPackage({{\App\Models\Level::LEVEL_RESELLER}})" class="btn btn-primary">
                                    Tambah Paket
                                </button>
                            </div>
                            @include('components.tables.package-table', ['packages' => $resellerPackages, 'id' => 'reseller'])
                        </div>
                        <div class="tab-pane fade" id="admin_packages" role="tabpanel" aria-labelledby="nav_level_admin">
                            <div class="d-flex justify-content-end mb-3">
                                <button onclick="addPackage({{\App\Models\Level::LEVEL_ADMIN}})" class="btn btn-primary">
                                    Tambah Paket
                                </button>
                            </div>
                            @include('components.tables.package-table', ['packages' => $adminPackages, 'id' => 'admin'])
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @include('components.modals.package-modal', ['id' => 'package_manager', 'action' => route('package.update')])

@endsection

@push('scripts')
    <script>
        function editPackage(id){
            let packageItem = packages.find(p => `${p.id}` === id);
            if(packageItem){
                $('#package_manager_title').text('Ubah paket');
                $('#package_id').val(packageItem.id);
                $('#package_name').val(packageItem.name);
                $('#package_users').val(packageItem.users);
                $('#package_user_device').val(packageItem.user_device);
                $('#package_admin_device').val(packageItem.admin_device);
                $('#package_level_' + packageItem.level_id).prop('checked', true);
                $('#package_live_chat').prop('checked', packageItem.live_chat);
                $('#form_package_manager').attr('action', "{{route('package.update')}}")
                $('#package_manager').modal('show');
            }
        }
        function addPackage(levelId){
            $('#package_manager_title').text('Tambah paket');
            $('#package_id').val('');
            $('#package_name').val('');
            $('#package_users').val('');
            $('#package_user_device').val('');
            $('#package_admin_device').val('');
            $('#package_level_' + levelId).prop('checked', true);
            $('#package_live_chat').prop('checked', false);
            $('#form_package_manager').attr('action', "{{route('package.store')}}")
            $('#package_manager').modal('show');
        }
    </script>

    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script src="{{asset('js/pages/datatables.js?t='.\Illuminate\Support\Str::random())}}"></script>
@endpush