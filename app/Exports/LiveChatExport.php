<?php

namespace App\Exports;

use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LiveChatExport implements WithMultipleSheets
{
    private $conversations;
    public function __construct($conversations)
    {
        $this->conversations = $conversations;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->conversations as $conversation){
            $sheets[$conversation->target_number] = new LiveChatSheetExport($conversation);
        }
        return $sheets;
    }
}
