<div class="d-flex gap-2 align-items-center">
    {{$conversation->target_name ?: "-"}}
    <button data-before="{{$conversation->target_name}}" data-contact-name="" data-edit-id="{{$conversation->id}}" data-toggle="edit" class="btn btn-warning btn-sm">
        Edit
    </button>
</div>