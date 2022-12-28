<div class="chat_item chat_{{strtolower($chat->number_type)}}" data-chat-id="{{$chat->message_id}}">
    @isset($chat->message['image'])
        <a target="_blank" rel="noopener noreferrer" href="{{$chat->message['image']}}">
            <img src="{{$chat->message['image']}}" class="chat_image">
        </a>
    @endisset
    @isset($chat->message['text'])
        <div class="chat_content">
            {{$chat->message['text']}}
        </div>
    @endisset
    @if($chat->is_autoreply)
        <div class="chat_autoreply">
            <small>This is an auto reply generated message</small>
        </div>
    @endif
    <div class="d-flex gap-2">
        @php
        $sentAt = $chat->sent_at ?: $chat->updated_at;
        @endphp
        <small class="chat_time" data-time="{{$sentAt}}">
            {{\Carbon\Carbon::make($sentAt)->format('H:i')}}
        </small>
        <small>
            @if($chat->number_type === 'SENDER')
                ✔
                @if($chat->user)
                    @if($chat->user_id === \Illuminate\Support\Facades\Auth::user()->id)
                        Sent by You
                    @else
                        Sent by {{$chat->user? ($chat->user->display_name?:$chat->user->username): ''}}
                    @endif
                @endif
            @else
                @if(isset($conversation) && !$conversation->can_send_message)
                    @if($chat->read_status === 'READ')
                        Read
                    @else
                        Unread
                    @endif
                @endif
            @endif
        </small>
    </div>
</div>