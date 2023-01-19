<div class="d-flex gap-2">
    <button data-stop-propagation class="btn btn-primary btn-sm" onclick="viewReply({{$message['autoreply']->id}}, {{$message['id']}})">
        Preview
    </button>
    <a data-resend-id="{{$message['id']}}" href="{{route('autoreply-history.resend', $message['id'])}}" data-stop-propagation class="btn btn-warning btn-sm {{$message['status'] === 'failed'? '': 'd-none'}}">
        Resend
    </a>
</div>