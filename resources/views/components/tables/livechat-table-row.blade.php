<tr data-id="{{$conversation->id}}" data-group-id="{{$conversation->group_id ?: ''}}">
    <td>@include('components.tables.live-chat.defined_label')</td>
    <td>@include('components.tables.live-chat.target_name')</td>
    <td>@include('components.tables.live-chat.target_number')</td>
    <td>@include('components.tables.live-chat.unreads')</td>
    <td>@include('components.tables.live-chat.time_range')</td>
    <td>@include('components.tables.live-chat.actions')</td>
</tr>
