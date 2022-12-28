<table id="datatable_{{$id}}" class="display" style="width: 100%">
    <thead>
    <tr>
        <th>
            Nama Paket
        </th>
        <th>
            Jumlah User
        </th>
        <th>
            Device Admin
        </th>
        <th>
            Device User
        </th>
        <th>
            Live Chat
        </th>
        <th>
            Aksi
        </th>
    </tr>
    </thead>
    <tbody>
    @isset($packages)
        @foreach($packages as $package)
            <tr>
                <td>
                    {{$package->name}}
                </td>
                <td>
                    {{$package->users}}
                </td>
                <td>
                    {{$package->admin_device}}
                </td>
                <td>
                    {{$package->user_device}}
                </td>
                <td>
                    {{$package->live_chat? "Aktif": "Non-aktif"}}
                </td>
                <td align="middle">
                    <div class="d-flex justify-content-center gap-2">
                        <button onclick="editPackage('{{$package->id}}')" class="btn btn-primary btn-sm">
                            Edit
                        </button>
                        <form action="{{route('package.delete', $package->id)}}" method="post" onsubmit="return confirm('Are you sure to delete this package? all users related to this package will be affected.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="id" value="{{$package->id}}">
                            <button type="submit" class="btn btn-danger btn-sm">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    @endisset
    </tbody>
</table>