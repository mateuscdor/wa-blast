<table>
    <thead>
        <tr>
            <th><b>No.</b></th>
            <th><b>Sender</b></th>
            <th><b>Message</b></th>
            <th><b>Status</b></th>
            <th><b>User</b></th>
            <th><b>Sent At</b></th>
        </tr>
    </thead>
    <tbody>
        @php $index = 1; @endphp
        @foreach($chats as $chat)
            <tr>
                <td>{{$index++}}</td>
                <td>{{$chat->number_type === 'RECEIVER'? $conversation->target_number: $conversation->device_number}}</td>
                <td>
                    @foreach($chat->message as $key => $message)
                        {{$key}}:
                        {{gettype($message) === 'string'? $message: json_encode($message)}}
                        <br/>
                    @endforeach
                </td>
                <td>{{$chat->read_status}}</td>
                <td>{{($chat->user)? '@' . $chat->user->username: '-'}}</td>
                <td>{{$chat->sent_at}}</td>
            </tr>
        @endforeach
    </tbody>
</table>