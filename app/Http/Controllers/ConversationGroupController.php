<?php

namespace App\Http\Controllers;

use App\Models\ConversationGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationGroupController extends Controller
{
    //
    public function store(Request $request){
        $request->validate([
            'label' => 'required|string'
        ]);

        ConversationGroup::create([
            'label' => $request->post('label'),
            'user_id' => Auth::id(),
        ]);

        return redirect()->back()->with('alert',[
            'type' => 'success',
            'msg' => 'Group label has been created successfully'
        ]);
    }

    public function update(Request $request){
        $request->validate([
            'label' => 'required|string',
            'id' => 'required|exists:conversation_groups,id'
        ]);

        $conversationGroup = ConversationGroup::find($request->post('id'));

        if($conversationGroup->is_updatable){
            $conversationGroup->update([
               'label' => $request->post('label'),
            ]);
            return redirect()->back()->with('alert',[
                'type' => 'success',
                'msg' => 'Group label has been updated successfully'
            ]);
        }

        return redirect()->back()->with('alert',[
            'type' => 'danger',
            'msg' => 'Group label is not updatable'
        ]);
    }

    public function delete(Request $request, $id){
        $group = ConversationGroup::find($id);

        if(!$group){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'Conversation group not found'
            ]);
        }

        if(!$group->is_updatable){
            return redirect()->back()->with('alert', [
               'type' => 'danger',
               'msg' => 'Conversation group cannot be deleted'
            ]);
        }

        $group->conversations()->update([
           'group_id' => null,
        ]);
        $group->delete();

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => 'Conversation Group has been deleted successfully'
        ]);
    }

}
