<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'message'
    ];

    protected $casts = [
        'message' => 'json'
    ];

    public function relatedCampaigns(){
        return $this->hasMany(CampaignTemplate::class, 'template_id');
    }

    public static function parseRequest(Request $request){

        $message = $request->post('message');
        $footer = $request->post('footer');
        $image = $request->post('image', null);
        $messageType = $request->post('message_type', 'text');
        $list = $request->post('list');
        $buttons = $request->post('buttons', []);

        return [
            'footer' => $footer,
            'message' => $message,
            'message_type' => $messageType,
            'list' => $list,
            'image' => $image,
            'buttons' => $buttons
        ];
    }

    public static function generateFromMessage($obj){

        switch ($obj->message_type) {
            case 'text':
                $msg = ['text' => $obj->message];
                break;
            case 'image':
                $arr = explode('.', $obj->image);
                $ext = end($arr);
                $allowext = ['jpg', 'png', 'jpeg'];
                if (!in_array($ext, $allowext)) {
                    session()->flash('alert', [
                        'type' => 'danger',
                        'msg' => 'File type not allowed',
                    ]);
                    return false;
                }
                $msg = [
                    'image' => ['url' => $obj->image],
                    'caption' => $obj->message ?? '',
                ];
                break;
            case 'button':
                if ($obj->image) {
                    $arr = explode('.', $obj->image);
                    $ext = end($arr);
                    $allowext = ['jpg', 'png', 'jpeg'];
                    if (!in_array($ext, $allowext)) {
                        session()->flash('alert', [
                            'type' => 'danger',
                            'msg' => 'Image type not allowed',
                        ]);
                        return false;
                    }
                }

                $buttons = collect($obj->buttons ?? [])->map(function($item, $index){
                    $typePurpose = $item->type === 'url' ? 'url' : ($item->type === 'phone'? 'phoneNumber': 'id');
                    $type = $item->type === 'url' ? 'urlButton' : ($item->type === 'phone'? 'callButton': 'quickReplyButton');

                    return [
                        'index' => $index + 1,
                        $type => ['displayText' => $item->label, $typePurpose => $typePurpose === 'id'? $item->id: $item->text],
                    ];
                });

                $buttonMessage = [
                    'text' => $obj->message,
                    'footer' => $obj->footer ?? '',
                    'templateButtons' => $buttons,
                    'headerType' => 1,
                ];

                //add image to buttonMessage if exists
                if ($obj->image) {
                    unset($buttonMessage['text']);
                    $buttonMessage['caption'] = $obj->message;
                    $buttonMessage['image'] = ['url' => $obj->image];
                    $buttonMessage['headerType'] = 4;
                }
                $msg = $buttonMessage;

                break;
            case 'template':
                try {
                    if ($obj->image) {
                        $arr = explode('.', $obj->image);
                        $ext = end($arr);
                        $allowext = ['jpg', 'png', 'jpeg'];
                        if (!in_array($ext, $allowext)) {
                            session()->flash('alert', [
                                'type' => 'danger',
                                'msg' => 'Image type not allowed',
                            ]);
                            return false;
                        }
                    }
                    $templateButtons = collect($obj->buttons ?? [])->map(function($item, $index){
                        $typePurpose = $item->type === 'url' ? 'url' : ($item->type === 'phone'? 'phoneNumber': 'id');
                        $type = $item->type === 'url' ? 'urlButton' : ($item->type === 'phone'? 'callButton': 'quickReplyButton');

                        return [
                            'index' => $index,
                            $type => ['displayText' => $item->label, $typePurpose => $typePurpose === 'id'? $item->id: $item->text],
                        ];
                    });

                    $templateMessage = [
                        'text' => $obj->message,
                        'footer' => $obj->footer ?? '',
                        'templateButtons' => $templateButtons,
                        'viewOnce' => true,
                    ];
                    //add image to templateMessage if exists
                    if ($obj->image) {
                        unset($templateMessage['text']);
                        $templateMessage['caption'] = $obj->message;
                        $templateMessage['image'] = [
                            'url' => $obj->image,
                        ];
                    }
                    $msg = $templateMessage;
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                    session()->flash('alert', [
                        'type' => 'danger',
                        'msg' => 'ups, an error occurred!',
                    ]);
                    return false;
                }

                break;
            case 'list':
                if (!$obj->list || !count($obj->list->items)) {
                    session()->flash('alert', [
                        'type' => 'danger',
                        'msg' => 'Please select a list minimum 1!',
                    ]);
                    return false;
                }

                $section = [
                    'title' => $obj->list->header,
                ];
                $section['rows'] = collect($obj->list->items)->map(function($item){
                   return [
                       'title' => $item->title,
                       'rowId' => $item->id,
                       'description' => $item->description,
                   ];
                });

                $listMessage = [
                    'text' => $obj->message,
                    'footer' => $obj->footer ?? '',
                    'title' => $obj->list->title,
                    'buttonText' => $obj->list->button,
                    'sections' => [$section],
                ];

                $msg = $listMessage;
                break;

            default:
                # code...
                session()->flash('alert', [
                    'type' => 'danger',
                    'msg' => 'Some error occurred!',
                ]);
                return false;
        }

        return $msg;
    }
}
