<div data-from-time="{{$conversation->oldest_time}}"
     data-to-time="{{$conversation->latest_time}}">
            <span data-from-id="{{$conversation->id}}" class="badge badge-warning">
                {{$conversation->oldest_time}}
            </span>
    <span data-to-id="{{$conversation->id}}" class="badge badge-primary">
        {{$conversation->latest_time}}
    </span>
</div>