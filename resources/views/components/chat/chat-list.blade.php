@foreach($chats as $chat)
    @include('components.chat.chat-item', ['chat' => $chat])
@endforeach