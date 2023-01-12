<tr data-id="{{$message->id}}">
    <td>
        <p style="white-space: nowrap">
            {{$message['repliedMessage']['conversation']['target_name']}}
            @if($message['repliedMessage']['conversation']['defined_name'])
                <span class="badge badge-info">
                                                    {{$message['repliedMessage']['conversation']['defined_name']}}
                                                </span>
            @endif
        </p>
    </td>
    <td>
        {{$message['repliedMessage']['conversation']['target_number']}}
    </td>
    <td>
        <p class="text-truncate">{{$message['repliedMessage']['message']['text'] ?? ''}}</p>
    </td>
    <td>
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
    </td>
    <td>
        <p class="badge badge-info">
            {{$message['repliedMessage']['sent_at']}}
        </p>
    </td>
    <td>
        <p class="badge badge-info">
            {{$message['message']['sent_at'] ?? '-'}}
        </p>
    </td>
    <td>
        <div class="d-flex gap-2">
            <button data-stop-propagation class="btn btn-primary btn-sm" onclick="viewReply({{$message['autoreply']->id}}, {{$message['id']}})">
                Preview
            </button>
            <a data-resend-id="{{$message['id']}}" href="{{route('autoreply-history.resend', $message['id'])}}" data-stop-propagation class="btn btn-warning btn-sm {{$message['status'] === 'failed'? '': 'd-none'}}">
                Resend
            </a>
        </div>
    </td>
</tr>