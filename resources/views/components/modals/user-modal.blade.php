<div class="modal fade" id="{{$id ?? 'user_modal'}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{$action ?? ''}}" method="POST" enctype="multipart/form-data" id="form_{{$id ?? 'user'}}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{$id ?? 'user_modal'}}_title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="{{$id ?? 'user_modal'}}_user_id" name="id">
                    <input type="hidden" id="{{$id ?? 'user_modal'}}_level_id" name="level_id" value="{{$levelId ?? 0}}">

                    <label for="{{$id ?? 'user_modal'}}_user_display_name" class="form-label">Nama Lengkap</label>
                    <input type="text" name="display_name" id="{{$id ?? 'user_modal'}}_user_display_name" class="form-control" value=""><br>

                    <label for="{{$id ?? 'user_modal'}}_user_username" class="form-label">Username</label>
                    <input type="text" name="username" id="{{$id ?? 'user_modal'}}_user_username" class="form-control" value=""><br>

                    <label for="{{$id ?? 'user_modal'}}_user_email" class="form-label">Email</label><br>
                    <input id="{{$id ?? 'user_modal'}}_user_email" name="email" class="form-control" type="email"><br/>

                    <label for="{{$id ?? 'user_modal'}}_user_password" id="{{$id ?? 'user_modal'}}_label_password" class="form-label">Limit Device Admin</label><br>
                    <input id="{{$id ?? 'user_modal'}}_user_password" name="password" class="form-control"><br/>

                    <label for="{{$id ?? 'user_modal'}}_user_phone_number" class="form-label">Nomor Telepon</label><br>
                    <input id="{{$id ?? 'user_modal'}}_user_phone_number" name="phone_number" class="form-control"><br/>
                    
                    @isset($modalPackages)
                        <label for="{{$id ?? 'user_modal'}}_user_package_id" class="form-label">Paket</label><br>
                        <select name="package_id" id="{{$id ?? 'user_modal'}}_user_package_id" class="form-control">
                            <option value="active">Pilih Paket</option>
                            @foreach($modalPackages as $package)
                                <option value="{{$package->id}}">{{$package->name}} - (U: {{$package->users}}, UD: {{$package->user_device}}, AD: {{$package->admin_device}})</option>
                            @endforeach
                        </select>
                        <small class="mb-1 d-block">
                            U: Users, UD: User devices, AD: Admin devices
                        </small><br>
                    @endisset

                    @if($limit ?? false)
                        <label for="{{$id ?? 'user_modal'}}_user_limit_device" class="form-label">Limit Device</label><br>
                        <input id="{{$id ?? 'user_modal'}}_user_limit_device" name="limit_device" class="form-control"><br/>
                    @endif

                    @if($subscription ?? true)
                        <label for="{{$id ?? 'user_modal'}}_user_active_subscription" class="form-label">Active Status</label><br>
                        <select name="active_subscription" id="{{$id ?? 'user_modal'}}_user_active_subscription" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="lifetime">Lifetime</option>
                        </select><br>

                        <label for="{{$id ?? 'user_modal'}}_user_subscription_expired" class="form-label">Subscription Expiry Date</label><br>
                        <input id="{{$id ?? 'user_modal'}}_user_subscription_expired" type="date" name="subscription_expired" class="form-control"><br/>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="{{$id ?? 'user_modal'}}_button" name="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
