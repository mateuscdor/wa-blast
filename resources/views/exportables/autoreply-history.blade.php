<table>
    <thead>
        <tr>
            <th><b>No.</b></th>
            <th><b>Target Name</b></th>
            <th><b>Target Number</b></th>
            <th><b>Message</b></th>
            <th><b>Autoreply Keyword</b></th>
            <th><b>Reply Message Type</b></th>
            <th><b>Message Received At</b></th>
            <th><b>Reply Sent At</b></th>
            <th><b>Status</b></th>
            <th><b>Status Updated At</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($messages as $index => $message)
            <tr>
                <td>{{$index + 1}}</td>
                <td>{{$message->target_name}}</td>
                <td>{{$message->target_number}}</td>
                <td>{{$message->extracted_message}}</td>
                <td>{{$message->keyword? implode(',', explode('[|]', $message->keyword)): '(None)'}}</td>
                <td>{{$message->message_type}}</td>
                <td>{{$message->received_at}}</td>
                <td>{{$message->sent_at}}</td>
                <td>{{$message->status}}</td>
                <td>{{$message->updated_at}}</td>
            </tr>
        @endforeach
    </tbody>
</table>