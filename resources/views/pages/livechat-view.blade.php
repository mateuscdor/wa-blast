@extends('layouts.app')

@section('title') Live Chat @endsection

@push('head')
    <link href="{{asset('plugins/datatables/datatables.min.css')}}" rel="stylesheet">
    <style>
        .chat_item {
            margin-top: 0.25rem;
            max-width: 500px;
            padding: 8px 16px;
            border-radius: 8px;
            width: fit-content;
            box-shadow: 0 0px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .chat_sender, .chat_auto_reply {
            background-color: #5be5e5;
            margin-left: auto;
            border-top-right-radius: 0;
        }
        .chat_receiver .chat_time {
            margin-left: auto;
        }
        .chat_receiver {
            background-color: white;
            border-top-left-radius: 0;
        }
        .chat_image {
            width: 100%;
            border-radius: 4px;
            margin-top: 8px;
            margin-bottom: 4px;
        }
        .chat_autoreply {
            border-top: 1px solid rgba(0, 0, 0, 0.2);
            margin-top: 4px;
        }
        .chat_disabled {
            background-color: #eeeeee !important;
            margin-right: 0.5rem !important;
        }
    </style>
@endpush

@section('content')
    <div>
        @if (session()->has('alert'))
            <x-alert>
                @slot('type',session('alert')['type'])
                @slot('msg',session('alert')['msg'])
            </x-alert>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-row align-items-center justify-content-between border border-light border-4 p-2 rounded-2">
                            <div class="d-flex flex-column gap-1">
                                <h5>
                                    {{$conversation->target_name ?: "Unknown"}}
                                </h5>
                                <h6 class="mb-0">
                                    +{{$conversation->target_number}}
                                </h6>
                            </div>
                            @if($conversation->can_send_message && $conversation->last_user_id === Auth::id() && $conversation->group_users->where('id', '!=', Auth::id())->count())
                                <button id="switch" data-bs-toggle="modal" data-bs-target="#switch-modal" class="btn btn-primary">
                                    Switch with Other...
                                </button>
                            @else
                                <button id="switch" disabled="disabled" data-bs-toggle="modal" data-bs-target="#switch-modal" class="btn btn-primary">
                                    Switch with Other...
                                </button>
                            @endif
                        </div>
                        <div id="chats" class="d-flex flex-column p-4" style="min-height: 400px; height: 400px; max-height: 400px; overflow-y: auto; background-color: #e7e7e7">
                            @include('components.chat.chat-list', ['chats' => $conversation->chats])
                        </div>
                        @if($device->status === \App\Models\Number::STATUS_DISCONNECTED)
                            <input value="Please connect your device" disabled="disabled" class="form-control chat_disabled">
                        @elseif($conversation->can_send_message)
                            <form method="POST" action="" id="message_form" class="d-flex align-items-center pr-2">
                                <input autocomplete="off" placeholder="Type a message" autofocus="autofocus" name="chat_message" id="chat_input" class="flex-grow-1 form-control">
                                <button class="btn btn-success">
                                    Send
                                </button>
                            </form>
                        @else
                            <input value="Another user is taking a part..." disabled="disabled" class="form-control chat_disabled">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="test">
        @csrf
    </div>
    <div class="modal fade" id="switch-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{route('livechat.switch', $conversation->id)}}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal_title">
                            Switch with other user
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Switch this conversation with other customer service
                        </p>
                        @csrf
                        <label for="username" class="form-label">User</label>
                        <select required class="form-select" name="target_username" id="username">
                            <option value="">Select User</option>
                            @foreach($conversation->group_users as $user)
                                @if($user->id !== Auth::id())
                                    <option value="{{$user->username}}">{{$user->username}}</option>
                                @endif
                            @endforeach
                        </select>
                        <label for="message" class="form-label mt-2">Message</label>
                        <textarea id="message" name="message" class="form-control" rows="10" required placeholder="Leave a message..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="button_button" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{asset('plugins/datatables/datatables.min.js')}}"></script>
    <script src="{{asset('plugins/moment/moment.js')}}"></script>
    <script src="{{asset('js/pages/datatables.js?t=' . getLastJSTime())}}"></script>

    <script>
        const getCharString = function(char){
            let len = char.length;
            return `(?<!\\w)([${char}]{${len}})(.+?)\\1(?!\\w)`;
        }
        const creators = {
            [getCharString('*')]: function(){
                return '<strong>$2</strong>'
            },
            [getCharString('~')]: function(){
                return '<del>$2</del>'
            },
            [getCharString('_')]: function(){
                return '<i>$2</i>'
            },
            [getCharString('```')]: function(){
                return '<pre>$2</pre>'
            },
        }
    </script>
    <script>
        $(document).ready(function(){
            const updateTime = function(){
                $('.chat_time:not([data-updated])').each(function(){
                    let el = $(this);
                    if(!el.data('updated')){
                        let time = el.data('time');
                        moment(time);
                        let newDate = moment(time).utc(-(new Date()).getTimezoneOffset())._d;
                        el.text(moment(newDate).format('HH:mm'));
                        el.data('updated', true);
                    }
                });
            }
            $('.chat_content').each(function(){
                let content = $(this).text();
                for(let i in creators){
                    content = content.replace(new RegExp(i, 'gi'), creators[i](content));
                }
                $(this).html(content);
            });
            $('#message_form').submit(function(e){
                e.preventDefault();
                let val = $('#chat_input').val();
                $('#chat_input').val('');
                $.ajax({
                    method : 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        'message': val,
                    },
                    dataType: 'json',
                    url : '{{route('livechat.sendMessage', $conversation->id)}}',
                    success : (result) => {
                        // window.location = '';
                        $('#switch').attr('disabled', false);
                    },
                    done: ()=>{
                        $('#switch').attr('disabled', false);
                    }
                })
            });
            setInterval(()=>{
                $.ajax({
                    method : 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url : '{{route('livechat.refresh', $conversation->id)}}',
                    data: {
                        lastMessageId: $('.chat_item').last().data('chatId')
                    },
                    dataType: "json",
                    success : (result) => {
                        // window.location = '';
                        let resultView = $(result.view);
                        resultView.each(function(){
                            let el = $(this);
                            if(document.querySelector(`[data-chat-id="${el.data('chatId')}"]`)){
                                return '';
                            }
                            $('#chats').append(el);
                        })
                        if(result.length){
                            document.getElementById('chats').scrollTo({
                                top: document.getElementById('chats').scrollHeight,
                            })
                        }
                        updateTime();
                    },
                })
            }, 3000);
            updateTime();
            document.getElementById('chats').scrollTo({
                top: document.getElementById('chats').scrollHeight,
            });
        });
    </script>
@endpush