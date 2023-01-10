<div class="chat_item chat_{{strtolower($chat->number_type)}}" data-chat-id="{{$chat->message_id}}">
    @if($chat->number_type === 'RECEIVER' && $conversation->defined_name)
        <p class="mb-0">
            {{$conversation->defined_name}}
        </p>
        <div class="divider my-1">
        </div>
    @endif
    @isset($chat->message['image'])
        @php
            $image = $chat->message['image'];
            $image = $image['url'] ?? $image;
        @endphp
        <a class="chat_image_holder" target="_blank" rel="noopener noreferrer" href="{{$image}}">
            <img src="{{$image}}" class="chat_image {{$chat->number_type === 'SENDER'? 'autoreply_image': ''}}">
        </a>
    @endisset
    @isset($chat->message['text'])
        <div class="chat_content">
            {{$chat->message['text']}}
        </div>
    @endisset
    @if($chat->is_autoreply)
        <div class="chat_autoreply">
            <small>This is an auto reply generated message{{isset($chat->message['sections']) ? ' (Type: List)': ''}}</small>
        </div>
    @endif
    <div class="d-flex gap-2 justify-content-between">
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