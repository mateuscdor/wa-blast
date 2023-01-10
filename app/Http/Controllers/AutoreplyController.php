<?php

namespace App\Http\Controllers;

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

//        $cek = Autoreply::whereDevice($request->device)->whereKeyword($request->keyword)->first();
//        if($cek){
//            session()->flash('alert', [
//                'type' => 'danger',
//                'msg' => 'Keyword already exists in same number'
//            ]);
//
//            return response()->json('error', 400);
//        }

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
        Autoreply::create([
            'user_id' => Auth::id(),
            'device' => $request->device,
            'keyword' => $request->keyword,
            'type_keyword' => $request->type_keyword,
            'type' => $messageType,
            'reply' => $jsonReply,
            'reply_when' => $request->reply_when
        ]);

        session()->flash('alert', [
            'type' => 'success',
            'msg' => 'Your auto reply was added!'
        ]);

        return response()->json('success');
    }

    public function show($id,Request $request){

        if($request->ajax()){
            $dataAutoReply = Autoreply::find($id);

            switch ($dataAutoReply->type) {
                case 'list':
                    return view('ajax.autoreply.listshow', [
                        'keyword'=>$dataAutoReply->keyword,
                        'message'=> json_decode($dataAutoReply->reply)->text,
                        'title'=> json_decode($dataAutoReply->reply)->title,
                        'footer'=> json_decode($dataAutoReply->reply)->footer,
                        'buttonText' => json_decode($dataAutoReply->reply)->buttonText,
                        'sections' => json_decode($dataAutoReply->reply)->sections,
                    ]);
                case 'text':
                    return view('ajax.autoreply.textshow',[
                        'keyword'=>$dataAutoReply->keyword,
                        'text'=> json_decode($dataAutoReply->reply)->text
                    ])->render();
                    break;
                case 'image':
                    return  view('ajax.autoreply.imageshow',[
                        'keyword'=>$dataAutoReply->keyword,
                        'caption'=> json_decode($dataAutoReply->reply)->caption,
                        'image'=> json_decode($dataAutoReply->reply)->image->url,
                        'buttons'=> json_decode($dataAutoReply->reply)->buttons,
                    ])->render();
                    break;
                case 'button':
                    // if exists property image in $dataAutoreply->reply
                    return  view('ajax.autoreply.buttonshow',[
                        'keyword'=>$dataAutoReply->keyword,
                        'message'=> json_decode($dataAutoReply->reply)->text ?? json_decode($dataAutoReply->reply)->caption,
                        'footer' => json_decode($dataAutoReply->reply)->footer,
                        'buttons'=> json_decode($dataAutoReply->reply)->buttons,
                        'image'=> json_decode($dataAutoReply->reply)->image->url ?? null,
                    ])->render();
                    break;
                case 'template':

                    $templates = [];
                    // if exists template 1

                    return  view('ajax.autoreply.templateshow',[
                        'keyword'=>$dataAutoReply->keyword,
                        'message'=> json_decode($dataAutoReply->reply)->text ?? json_decode($dataAutoReply->reply)->caption,
                        'footer' => json_decode($dataAutoReply->reply)->footer,
                        'templates' => json_decode($dataAutoReply->reply)->templateButtons,
                        'image' => json_decode($dataAutoReply->reply)->image->url ?? null,
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
