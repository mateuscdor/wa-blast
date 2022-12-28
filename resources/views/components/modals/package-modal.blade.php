<div class="modal fade" id="{{$id ?? 'package_modal'}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{$action ?? ''}}" method="POST" enctype="multipart/form-data" id="form_{{$id ?? 'package'}}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{$id ?? 'package_modal'}}_title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="package_id" name="id" >

                    <label for="package_name" class="form-label">Nama Paket</label>
                    <input type="text" name="name" id="package_name" class="form-control" value=""><br>

                    <label for="package_users" class="form-label">Limit User (CS)</label><br>
                    <input id="package_users" name="users" class="form-control" type="number"><br/>

                    <label for="package_admin_device" class="form-label">Limit Device Admin</label><br>
                    <input id="package_admin_device" name="admin_device" class="form-control" type="number"><br/>

                    <label for="package_user_device" class="form-label">Limit Device User (CS)</label><br>
                    <input id="package_user_device" name="user_device" class="form-control" type="number"><br/>

                    <div>
                        <h6>
                            Tipe Paket
                        </h6>
                        <div class="d-flex flex-column gap-2">
                            <div>
                                <input
                                        class="form-check-input"
                                        value="{{\App\Models\Level::LEVEL_RESELLER}}"
                                        type="radio"
                                        name="level_id"
                                        id="package_level_{{\App\Models\Level::LEVEL_RESELLER}}"
                                >
                                <label class="form-check-label" for="package_level_{{\App\Models\Level::LEVEL_RESELLER}}">
                                    Reseller
                                </label>
                            </div>
                            <div>
                                <input
                                        class="form-check-input"
                                        value="{{\App\Models\Level::LEVEL_ADMIN}}"
                                        type="radio"
                                        name="level_id"
                                        id="package_level_{{\App\Models\Level::LEVEL_ADMIN}}"
                                >
                                <label class="form-check-label" for="package_level_{{\App\Models\Level::LEVEL_ADMIN}}">
                                    Admin
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6>
                            Live Chat
                        </h6>
                        <div class="form-switch">
                            <input class="form-check-input" name="live_chat" type="checkbox" id="package_live_chat">
                            <label class="form-check-label" for="package_live_chat">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="modalButton" name="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
