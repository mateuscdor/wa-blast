<?php

namespace App\Exports;

use App\Models\AutoreplyMessages;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AutoreplyHistoryExport implements FromView, ShouldAutoSize
{

    private $messages;
    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    public function view(): View
    {
        $messages = $this->messages->map(function(AutoreplyMessages $message){
           $message->extracted_message = $message->target_message['text'] ?? $message->target_message['caption'] ?? '(Undefined Message Type)';
           $message->keyword = $message->autoreply->keyword;
           $message->message_type = $message->autoreply->type;
           return $message;
        });

        return view('exportables.autoreply-history', compact('messages'));
    }
}
