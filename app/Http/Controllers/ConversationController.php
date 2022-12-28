<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationGroup;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    //
    public function move(Request $request){
        $request->validate([
            'group_id' => 'required|exists:conversation_groups,id',
            'id' => 'required|array',
            'id.*' => 'exists:conversations,id',
        ]);

        $conversations = Conversation::whereIn('id', $request->post('id'));
        foreach ($conversations as $conversation){
            if(!$conversation->has_access){
                return redirect()->back()->with('alert', [
                   'type' => 'danger',
                   'msg' => 'You don\'t have the access of these conversations',
                ]);
            }
        }

        $conversations->update([
            'group_id' => $request->post('group_id')
        ]);

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => 'Conversations has been moved to other groups'
        ]);
    }
}
