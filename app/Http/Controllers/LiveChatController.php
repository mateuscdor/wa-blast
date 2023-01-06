<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Conversation;
use App\Models\ConversationGroup;
use App\Models\ConversationTakeOver;
use App\Models\Level;
use App\Models\Number;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        return view('pages.livechat-lists',[
            'conversations' => Conversation::withCount('unreadChats')->whereHas('number', function($q){
                $q->whereHas('user', function($q){
                    return $q->where('id', Auth::user()->id)->orWhereHas('createdUsers', function($q){
                        $q->where('id', Auth::user()->id);
                    });
                });
            })->get()->groupBy('group_id'),
            'groups' => $groups,
            'device' => $device
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

    public function show($id){
        $user = Auth::user();
        $conversation = Conversation::with(['chats' => function($q){
            $q->whereIn('read_status', ['READ', 'UNREAD', 'DELIVERED']);
        }])->find($id);
        if(!$conversation->has_access){
            throw new NotFoundHttpException();
        }

        if($conversation->can_send_message){
            $conversation->chats()->where([
                'read_status' => 'UNREAD'
            ])->update([
                'read_status' => 'READ'
            ]);
        }

        return view('pages.livechat-view', [
            'conversation' => $conversation,
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
                    ->get();
            } else {
                $chats = $conversation->chats()->where('read_status', 'UNREAD');
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
                    'chats' => $chats
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
                'read_status' => 'PENDING',
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
}
