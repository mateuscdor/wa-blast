@php
    $badgeColor = [
        'success' => 'success',
        'failed' => 'danger',
        'pending' => 'warning',
        'processing' => 'info'
    ][$message['status']] ?? 'secondary';
@endphp
<div data-message-id="{{$message['id']}}" class="badge badge-{{$badgeColor}}">
    {{$message['status']}}
</div>