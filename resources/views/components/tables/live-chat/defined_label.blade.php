<div class="d-flex gap-2 align-items-center">
    {{$conversation->defined_name ?: "-"}}
    <button data-before="{{$conversation->defined_name}}" data-defined-label="" data-edit-id="{{$conversation->id}}" data-toggle="edit" class="btn btn-warning btn-sm">
        Edit
    </button>
</div>