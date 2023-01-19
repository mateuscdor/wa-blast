<div class="d-flex gap-2">
    <a href="{{route('livechat.view', $conversation->id)}}" class="btn btn-primary btn-sm">
        View
    </a>
    <form action="{{route('livechat.delete', $conversation->id)}}" method="POST" onsubmit="return confirm('Are you sure will delete this conversation?')">
        @csrf
        @method('DELETE')
        <input type="hidden" name="id" value="{{$conversation->id}}">
        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
    </form>
</div>