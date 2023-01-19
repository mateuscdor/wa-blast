<?php

namespace App\Exports;

use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LiveChatSheetExport implements FromView, WithTitle, ShouldAutoSize, WithColumnFormatting
{
    private $conversation;

    public function __construct($conversation)
    {
        $this->conversation = $conversation;
    }

    public function view(): View
    {
        return view('exportables.livechat', [
            'conversation' => $this->conversation,
            'chats' => $this->conversation->chats->sortByDesc('sent_at'),
        ]);
    }

    public function title(): string
    {
        return $this->conversation->target_number;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER,
        ];
    }
}
