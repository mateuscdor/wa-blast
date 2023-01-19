<?php

namespace App\Http\Controllers;

use App\Exports\LiveChatExport;
use App\Models\Chat;
use App\Models\Conversation;
use App\Models\ConversationGroup;
use App\Models\ConversationTakeOver;
use App\Models\Level;
use App\Models\Number;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LiveChatController extends Controller
{
    //
    public function index(){
        $package = Auth::user()->package;

        if(!hasLiveChatAccess()){
            throw new NotFoundHttpException();
        }

        $device = Number::with(['user'])->whereHas('user', function($q){
            $q->where('id', Auth::user()->id)->orWhereHas('createdUsers', function($q){
                $q->where('id', Auth::user()->id)->where('level_id', Level::LEVEL_CUSTOMER_SERVICE);
            });
        })->where('live_chat', 1)->first();

        $groups = ConversationGroup::whereHas('creator', function($q){
            $q->where('id', Auth::user()->id)->orWhereHas('createdUsers', function($q){
                $q->where('id', Auth::user()->id)->where('level_id', Level::LEVEL_CUSTOMER_SERVICE);
            });
        })->get();

        $tags = Auth::user()->tags;

        return view('pages.livechat-lists',[
            'conversations' => Conversation::withCount('unreadChats')->whereHas('number', function($q){
                $q->whereHas('user', function($q){
                    return $q->where('id', Auth::user()->id)->orWhereHas('createdUsers', function($q){
                        $q->where('id', Auth::user()->id);
                    });
                })->where('is_group_chat', false);
                $q->whereLiveChat(1);
            })->get()->sortByDesc('latest_time')->groupBy('group_id'),
            'groups' => $groups,
            'device' => $device,
            'tags' => $tags,
        ]);
    }

    public function changeLabel(Request $request){
        $conversationId = $request->post('id');
        $conv = Conversation::find($conversationId);
        if(!$conv->has_access){
            throw new NotFoundHttpException();
        }

        $conv->defined_name = $request->post('defined_name');
        $conv->save();

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => 'Conversation Label has been changed'
        ]);
    }

    public function changeName(Request $request){
        $conversationId = $request->post('id');
        $conv = Conversation::find($conversationId);
        if(!$conv->has_access){
            throw new NotFoundHttpException();
        }

        $conv->target_name = $request->post('target_name');
        $conv->save();

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => 'Conversation Name has been changed'
        ]);
    }

    public function getLiveChatQuery($groupId){
        return Conversation::with('unreadChats')->withCount('unreadChats')->whereHas('number', function($q){
            $q->whereHas('user', function($q){
                return $q->where('id', Auth::user()->id)->orWhereHas('createdUsers', function($q){
                    $q->where('id', Auth::user()->id);
                });
            });
            $q->whereLiveChat(1);
        })
            ->where('group_id', $groupId)
            ->orderBy('updated_at', 'desc')
            ->where('is_group_chat', false);
    }

    public function ajaxTable(Request $request){
        $columns = [
            0 =>'defined_name',
            1 =>'target_name',
            2=> 'target_number',
            3=> 'unreads',
            4=> 'time_range',
            5=> 'action',
        ];
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $group = $request->input('groupId');

        $totalData = $this->getLiveChatQuery($group)->count();
        $totalFiltered = $totalData;

        if(empty($request->input('search.value')))
        {
            $conversations = $this->getLiveChatQuery($group)
                ->offset($start)
                ->limit($limit);
        } else {
            $search = $request->input('search.value');
            $conversations = $this->getLiveChatQuery($group)
                ->offset($start)
                ->limit($limit);

            $totalFiltered = $conversations->count();
        }

        $conversations = $conversations->get();
        $data = array();
        if(!empty($conversations))
        {
            foreach ($conversations as $conversation)
            {
                $nestedData = [
                    'defined_name' => view('components.tables.live-chat.defined_label', ['conversation' => $conversation])->render(),
                    'target_name' => view('components.tables.live-chat.target_name', ['conversation' => $conversation])->render(),
                    'target_number' => view('components.tables.live-chat.target_number', ['conversation' => $conversation])->render(),
                    'unreads' => view('components.tables.live-chat.unreads', ['conversation' => $conversation])->render(),
                    'time_range' => view('components.tables.live-chat.time_range', ['conversation' => $conversation])->render(),
                    'action' => view('components.tables.live-chat.actions', ['conversation' => $conversation])->render(),
                    'id' => $conversation->id,
                    'group_id' => $conversation->group_id,
                ];
                $data[] = $nestedData;
            }
        }

        return response()->json(array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ));

    }

    public function show($id){
        $user = Auth::user();
        $conversation = Conversation::with(['chats' => function($q){
            $q->whereIn('read_status', ['READ', 'UNREAD', 'DELIVERED']);
        }])->where('is_group_chat', false)->find($id);
        if(!$conversation || !$conversation->has_access){
            throw new NotFoundHttpException();
        }

        if($conversation->can_send_message){
            $conversation->chats()->where([
                'read_status' => 'UNREAD'
            ])->update([
                'read_status' => 'READ'
            ]);
        }

        $chats = $conversation->chats()->get()->groupBy('message_id')->map(function($items){
            return collect($items)->filter(function($item){
                if(isset($item->message['text'])){
                    if(count(array_values($item->message)) === 1 && $item->message['text'] === ''){
                        return false;
                    }
                }
                return true;
            })->first();
        })->filter(function($item){return !!$item;})->values();


        return view('pages.livechat-view', [
            'conversation' => $conversation,
            'chats' => $chats,
            'device' => $conversation->number()->first(),
        ]);

    }

    public function refresh(Request $request, $id){
        if($request->ajax()){
            $user = Auth::user();
            $conversation = Conversation::with(['chats'])->find($id);
            if(!$conversation->has_access){
                return '';
            }

            $lastMessageChat = Chat::where('message_id', $request->post('lastMessageId', NULL))->first();

            if($lastMessageChat && $request->post('lastMessageId')){
                $chats = $conversation->chats()->where('sent_at', '!=', null)
                    ->where('sent_at', '>=', $lastMessageChat->sent_at)
                    ->where('id', '!=', $lastMessageChat->id)
                    ->whereIn('read_status', ['UNREAD', 'DELIVERED'])
                    ->get()
                    ->groupBy('message_id')->map(function($items){
                        return collect($items)->filter(function($item){
                            if(isset($item->message['text'])){
                                if(count(array_values($item->message)) === 1 && $item->message['text'] === ''){
                                    return false;
                                }
                            }
                            return true;
                        })->first();
                    })->filter(function($item){return !!$item;})->values();
            } else {
                $chats = $conversation->chats()->where('read_status', 'UNREAD')->get()->groupBy('message_id')->map(function($items){
                    return collect($items)->filter(function($item){
                        if(isset($item->message['text'])){
                            if(count(array_values($item->message)) === 1 && $item->message['text'] === ''){
                                return false;
                            }
                        }
                        return true;
                    })->first();
                })->filter(function($item){return !!$item;})->values();
            }

            if($conversation->can_send_message){
                $conversation->chats()->where([
                    'read_status' => 'UNREAD'
                ])->update([
                    'read_status' => 'READ'
                ]);
            }

            return response()->json([
                'view' => (String) view('components.chat.chat-list', [
                    'chats' => $chats->map(function($chat){
                        $chat->is_autoreply = $chat->is_autoreply;
                        return $chat;
                    }),
                    'conversation' => $conversation,
                ])
            ]);
        }
        return response()->json([
            'message' => 'Bad Request'
        ], 400);
    }

    public function send(Request $request, $id){
        if($request->ajax()) {
            $user = Auth::user();

            $request->validate([
                'message' => 'required|string'
            ]);

            $conversation = Conversation::with('chats')->find($id);
            if (!$conversation->has_access) {
                return [
                    'sent' => false,
                    'message' => 'You have no access to send a message'
                ];
            }
            if (!$conversation->can_send_message) {
                return [
                    'sent' => false,
                    'message' => 'Another user is sending message'
                ];
            }

            $message = [
                "text" => $request->post('message'),
            ];

            $conversation->last_user_id = $user->id;
            $conversation->last_sent_at = Carbon::now();
            $conversation->save();
            $chat = new Chat([
                'conversation_id' => $conversation->id,
                'read_status' => 'WAITING',
                'number_type' => 'SENDER',
                'user_id' => $user->id,
                'message' => $message,
            ]);
            $chat->save();

            $data = [
                'chat_id' => $chat->id,
                'receiver' => $conversation->target_number,
                'message' => json_encode($message),
                'sender' => $conversation->number->body,
            ];
            $proc = Http::withOptions(['verify' => false])
                ->asForm()
                ->post(
                    env('WA_URL_SERVER') . '/backend-direct',
                    [
                        'data' => json_encode($data),
                        'delay' => 1,
                    ]
                );

            if(!($proc['status'] ?? false)){
                return [
                    'sent' => false,
                ];
            }
            return [
                'sent' => true,
            ];
        }
        return [
            'sent' => false,
            'message' => 'Ajax required'
        ];
    }

    public function delete(Request $request, $id){
        $conversation = Conversation::findOrFail($id);
        if(!$conversation->has_access){
            return new NotFoundHttpException;
        }
        if(!$conversation->can_send_message){
            return redirect()->back();
        }

        $conversation->chats()->delete();
        $conversation->delete();
        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => 'Your conversation has been deleted.',
        ]);;
    }

    public function switchChat(Request $request, $id){

        $conversation = Conversation::findOrFail($id);

        if(!$conversation->has_access){
            return new NotFoundHttpException;
        }
        if(!$conversation->can_send_message){
            return redirect()->back();
        }
        if(!$conversation->last_user_id){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'You don\'t have access to switch this'
            ]);
        }

        $request->validate([
           'target_username' => 'required|exists:users,username',
           'message' => 'required|string',
        ]);

        $message = $request->post('message');
        $targetUsername = $request->post('target_username');
        $targetUser = User::where('username', $targetUsername)->first();
        if(!$conversation->group_users->contains($targetUser)){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'The target user is invalid'
            ]);
        }

        $lastUser = Auth::user();

        if($targetUsername === $lastUser->username){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'You are not able to switch with yourself.'
            ]);
        }

        $conversation->last_user_id = $targetUser->id;
        $conversation->last_sent_at = Carbon::now();
        $conversation->save();
        $takeOver = new ConversationTakeOver([
           'target_id' => $targetUser->id,
           'from_id' => $lastUser->id,
            'message' => $message,
        ]);
        $takeOver->save();

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => 'Your conversation has been switched.',
        ]);
    }

    public function export(Request $request){
        $startTime = $request->post('start_time');
        $endTime = $request->post('end_time');
        $request->validate([
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date' . ($startTime ? '|after:start_time': ''),
            'options' => 'array',
            'options.*' => 'in:autoreplies,external_replies,current_user',
        ]);
        $options = $request->post('options', []);

        $conversations = Conversation::with(['chats' => function($q) use ($endTime, $startTime, $options) {
            if(!in_array('autoreplies', $options)){
                $q = $q->doesntHave('replierMessage')->doesntHave('autoreplyMessage')->where('number_type', '!=', 'AUTO_REPLY');
            }
            if(!in_array('external_replies', $options)){
                $q = $q->where(function($q){
                    $q->whereNotNull('user_id')->where('number_type', '=', 'SENDER');
                })->orWhere('number_type', '!=', 'SENDER');
            }
            if(in_array('current_user', $options)){
                $q = $q->where([
                    'user_id' => null,
                ])->orWhere([
                    'user_id' => Auth::id(),
                ]);
            }
            if($startTime || $endTime){
                if($startTime && $endTime){
                    $q->where('sent_at', '>=', Carbon::make($startTime))->where('sent_at', '<=', Carbon::make($endTime));
                } else if($startTime){
                    $q->where('sent_at', '>=', Carbon::make($startTime));
                } else {
                    $q->where('sent_at', '<=', Carbon::make($endTime));
                }
            }
            $q->where('message', '!=', ['text'=>'']);
            $q->where('message', '!=', json_encode(['text'=>'']));
            $q->with('user');
        }])->where(function(Builder $q){
            $q->whereHas('chats');
        })->whereHas('number', function($q){
            $q->whereHas('user', function($q){
                return $q->where('id', Auth::user()->id)->orWhereHas('createdUsers', function($q){
                    $q->where('id', Auth::user()->id);
                });
            })->whereLiveChat(1);
        })->get()->sortByDesc('latest_time');

        $date = Carbon::now()->format('Y-m-d_H_s_i');
        return Excel::download(new LiveChatExport($conversations), session()->get('selectedDevice') . '_' . $date . '_livechat_export_.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function notifications(){

        if(!Auth::user()->hasLiveChat){
            return response()->json([
                'popups' => 0,
                'unreads' => 0,
            ]);
        }

        $conversations = Conversation::with('unreadChats')->whereHas('number', function($q){
            $q->whereHas('user', function($q){
                return $q->where('id', Auth::user()->id)->orWhereHas('createdUsers', function($q){
                    $q->where('id', Auth::user()->id);
                });
            })->whereLiveChat(1);
        })->get();

        $needsPopup = $conversations->map(function($conversation){
            return $conversation->chats()->whereReadStatus('UNREAD')->whereNumberType('RECEIVER')->where('sent_at', '>=', Carbon::now()->setTimezone('UTC')->subMinutes(2)->toDateTimeString())->count();
        })->sum();
        $unreadChats = $conversations->map(function($conversation){
            return $conversation->chats()->whereReadStatus('UNREAD')->whereNumberType('RECEIVER')->count();
        })->sum();
        $lastChatId = $conversations->map(function($conversation){
            $first = $conversation->unreadChats->sortByDesc('sent_at')->first();
            return $first->id ?? 0;
        })->max();
        $lastMessageId = session()->get('last-message-id');
        $needsSound = false;
        if(!$lastMessageId || $lastChatId !== $lastMessageId){
            session()->put('last-message-id', $lastChatId);
            $needsSound = true;
        }


        return response()->json([
           'popups' => $needsPopup,
           'unreads' => $unreadChats,
            'needsSound' => $needsSound,
        ]);
    }
}
