<?php

namespace App\Http\Controllers;

use App\Models\AutoreplyMessages;
use App\Models\UserTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Autoreply;
use App\Models\Number;
use Illuminate\Validation\ValidationException;

class AutoreplyController extends Controller
{


    public function index(Request $request){

        return view('pages.autoreply',[
            'autoreplies' => Autoreply::where('user_id', Auth::id())->whereDevice(session()->get('selectedDevice'))->latest()->paginate(15),
            'numbers' => $request->user()->numbers()->get(),
            'templates' => Auth::user()->messageTemplates
        ]);
    }




    public function store(Request $request){

        $isUpdating = $request->id;
        $autoreply = null;
        if($isUpdating){
            $autoreply = Autoreply::where('user_id', Auth::id())->find($request->id);
            if(!$autoreply){
                session()->flash('alert', [
                    'type' => 'danger',
                    'msg' => 'Autoreply not found'
                ]);

                return response()->json('error', 404);
            }
        }
//        $cek = Autoreply::whereDevice($request->device)->whereKeyword($request->keyword)->first();
//        if($cek){
//            session()->flash('alert', [
//                'type' => 'danger',
//                'msg' => 'Keyword already exists in same number'
//            ]);
//
//            return response()->json('error', 400);
//        }
        $keyword = $request->keyword_input;
        if($request->keyword){
            $keywords = explode('[|]', $request->keyword);
            if($keyword){
                $keywords[] = $keyword;
            }
            $keyword = implode('[|]', $keywords);
        } else if(!$keyword) {
            session()->flash('alert', [
                'type' => 'danger',
                'msg' => 'Keyword must be defined'
            ]);

            return response()->json('error', 400);
        }

        $messageType = $request->message_type;
        // create text
        if($request->template_id){
            $userTemplate = UserTemplate::where([
                'user_id' => Auth::id(),
                'id' => $request->template_id
            ])->first();
            if(!$userTemplate){
                session()->flash('alert', [
                    'type' => 'danger',
                    'msg' => 'Message Template Not Found',
                ]);
                return false;
            }
            $msg = $userTemplate->message;
            $messageType = $msg['message_type'] ?? 'text';
        } else {
            $msg = UserTemplate::parseRequest($request);
        }

        $message = UserTemplate::generateFromMessage(json_decode(json_encode($msg)));


        $jsonReply = json_encode($message);
        $attributes = [
            'user_id' => Auth::id(),
            'device' => $request->device,
            'keyword' => $keyword,
            'type_keyword' => $request->type_keyword,
            'type' => $messageType,
            'reply' => $jsonReply,
            'reply_when' => $request->reply_when,
            'settings' => [
                'startTime' => $request->start_time,
                'endTime' => $request->end_time,
                'activeDays' => json_decode($request->active_days ?? '[]') ?: ($request->active_days ?? [])
            ]
        ];
        if($autoreply){
            $autoreply->update($attributes);
        } else {
            $autoreply = new Autoreply($attributes);
            $autoreply->save();
        }

        session()->flash('alert', [
            'type' => 'success',
            'msg' => 'Your auto reply was ' . ($isUpdating? 'updated': 'saved') . '!'
        ]);

        return response()->json('success');
    }

    public function show($id,Request $request){

        $history = $request->get('historyId');
        if($history){
            $history = AutoreplyMessages::find($history);
        }

        if($request->ajax()){
            $dataAutoReply = Autoreply::find($id);
            if($history && isset($history->prepared_message) && $history->prepared_message){
                $reply = $history->prepared_message;
                $keyword = $history->repliedMessage->message['text'] ?? $history->repliedMessage->message['caption'];
            } else {
                $reply = $dataAutoReply->reply;
                $keyword = implode(' OR ', explode('[|]', $dataAutoReply->keyword));
            }

            switch ($dataAutoReply->type) {
                case 'list':
                    return view('ajax.autoreply.listshow', [
                        'keyword'=>$keyword,
                        'message'=> json_decode($reply)->text,
                        'title'=> json_decode($reply)->title,
                        'footer'=> json_decode($reply)->footer,
                        'buttonText' => json_decode($reply)->buttonText,
                        'sections' => json_decode($reply)->sections,
                    ]);
                case 'text':
                    return view('ajax.autoreply.textshow',[
                        'keyword'=>$keyword,
                        'text'=> json_decode($reply)->text
                    ])->render();
                    break;
                case 'image':
                    return  view('ajax.autoreply.imageshow',[
                        'keyword'=>$keyword,
                        'caption'=> json_decode($reply)->caption,
                        'image'=> json_decode($reply)->image->url,
                        'buttons'=> json_decode($reply)->buttons,
                    ])->render();
                    break;
                case 'button':
                    // if exists property image in $reply
                    return  view('ajax.autoreply.buttonshow',[
                        'keyword'=>$keyword,
                        'message'=> json_decode($reply)->text ?? json_decode($reply)->caption,
                        'footer' => json_decode($reply)->footer,
                        'buttons'=> json_decode($reply)->buttons,
                        'image'=> json_decode($reply)->image->url ?? null,
                    ])->render();
                    break;
                case 'template':

                    $templates = [];
                    // if exists template 1

                    return  view('ajax.autoreply.templateshow',[
                        'keyword'=>$keyword,
                        'message'=> json_decode($reply)->text ?? json_decode($reply)->caption,
                        'footer' => json_decode($reply)->footer,
                        'templates' => json_decode($reply)->templateButtons,
                        'image' => json_decode($reply)->image->url ?? null,
                    ])->render();
                    break;
                default:
                    # code...
                    break;
            }
        }
    }

    public function getFormByType($type,Request $request){
        if($request->ajax()){
            switch ($type) {
                case 'text':
                    return view('ajax.autoreply.formtext')->render();
                    break;
                case 'image' :
                    return view('ajax.autoreply.formimage')->render();
                    break;
                case 'button' :
                    return view('ajax.autoreply.formbutton')->render();
                    break;
                case 'template' :
                    return view('ajax.autoreply.formtemplate')->render();
                    break;
                case 'list':
                    return view('ajax.autoreply.formlist')->render();
                    break;
                default:
                    # code...
                    break;
            }
            return;
        }
        return 'http request';
    }

    public function deleteSelections(Request $request){
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'exists:autoreplies,id',
        ]);

        $userHasAccess = !Autoreply::with('user')->whereIn('id', $request->id)->where('user_id', '!=', Auth::id())->count();
        if(!$userHasAccess){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'You don\'t have access to delete an autoreply or many autoreplies of the selected items',
            ]);
        }
        Autoreply::with('messages')->whereIn('id', $request->id)->each(function($item){
            $item->messages()->delete();
            $item->delete();
        });
        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => count(($request->id)) . ' Autoreplies have been deleted',
        ]);
    }

    public function destroy(Request $request){
        Autoreply::whereId($request->id)->delete();
        return redirect(route('autoreply'))->with('alert',[
            'type' => 'success',
            'msg' => 'Deleted'
        ]);
    }
    public function destroyAll(Request $request){
        Autoreply::whereUserId(Auth::user()->id)->whereDevice(session()->get('selectedDevice'))->delete();
        return redirect(route('autoreply'))->with('alert',[
            'type' => 'success',
            'msg' => 'Deleted'
        ]);

    }


    public function makeTemplateButton($templateButton,$no){
        $allowType = ['callButton', 'urlButton'];
        $template = $templateButton;
        $type = explode('|', $template)[0] . 'Button';
        $text = explode('|', $template)[1];
        $urlOrNumber = explode('|', $template)[2];

        if (!in_array($type, $allowType)) {
            return redirect(route('autoreply'))->with('alert', [
                'type' => 'danger',
                'msg' => 'The Templates are not valid!'
            ]);
        }

        $typePurpose = explode('|', $template)[0] === 'url' ? 'url' : 'phoneNumber';
        return ["index" => $no, $type => ["displayText" => $text, $typePurpose => $urlOrNumber]];
    }
}
