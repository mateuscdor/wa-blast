<?php

namespace App\Http\Controllers;

use App\Models\Autoreply;
use App\Models\AutoreplyMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoreplyMessageController extends Controller
{
    public function index(){
        return view('pages.autoreply-history',[
            'autoreplyMessages' => AutoreplyMessages::with(['message', 'autoreply', 'repliedMessage'])->whereHas('autoreply', function($q){
                $q->where('user_id', Auth::id())->whereDevice(session()->get('selectedDevice'));
            })->latest()->paginate(15),
            'numbers' => Auth::user()->numbers()->get(),
            'templates' => Auth::user()->messageTemplates
        ]);
    }

    public function resendAll(){
        $history = AutoreplyMessages::whereHas('autoreply', function($q){
            $q->where('user_id', Auth::id());
        })->where('status', 'failed');
        if(!$history){
            return back()->with('alert', [
                'type' => 'danger',
                'msg' => 'Message is not found or you are not authorized to resend this message',
            ]);
        }
        $history->update([
            'status' => 'pending',
        ]);
        return back()->with('alert', [
            'type' => 'success',
            'msg' => 'Message is sent',
        ]);
    }

    public function resend($id){
        $history = AutoreplyMessages::whereHas('autoreply', function($q){
            $q->where('user_id', Auth::id());
        })->where('status', 'failed')->find($id);
        if(!$history){
            return back()->with('alert', [
                'type' => 'danger',
                'msg' => 'Message is not found or you are not authorized to resend this message',
            ]);
        }
        $history->update([
            'status' => 'pending',
        ]);
        return back()->with('alert', [
            'type' => 'success',
            'msg' => 'Message is sent',
        ]);
    }

    public function deleteSelections(Request $request){
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'exists:autoreply_messages,id',
        ]);

        $userHasAccess = !AutoreplyMessages::with('autoreply')->whereIn('id', $request->id)->whereHas('autoreply', function($q){
            $q->where('user_id', Auth::id())->whereDevice(session()->get('selectedDevice'));
        })->count();
        if(!$userHasAccess){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'You don\'t have access to delete an message or many messages of the selected items',
            ]);
        }
        AutoreplyMessages::whereIn('id', $request->id)->each(function($item){
            $item->delete();
        });
        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => count(($request->id)) . ' messages have been deleted',
        ]);
    }

    public function destroy(){
        $messages = AutoreplyMessages::whereHas('autoreply', function($q){
            $q->where('user_id', Auth::id())->whereDevice(session()->get('selectedDevice'));
        });
        $count = $messages->count();
        if(!$count){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'No messages is deleted',
            ]);
        }
        $messages->delete();
        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => $count . ' messages have been deleted',
        ]);
    }

    public function refresh(){
        $messages = AutoreplyMessages::with(['repliedMessage', 'message', 'autoreply'])->whereHas('autoreply', function($q){
            $q->where('user_id', Auth::id())->whereDevice(session()->get('selectedDevice'));
        })->paginate(15);
        return response()->json($messages->map(function($item){
            return [
                'id' => $item->id,
                'status' => $item->status,
                'view' => view('components.tables.autoreply-history-table-row', [
                    'message' => $item
                ])->render()
            ];
        }));
    }

}
