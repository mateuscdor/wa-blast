<?php

namespace App\Http\Controllers;

use App\Exports\AutoreplyHistoryExport;
use App\Models\Autoreply;
use App\Models\AutoreplyMessages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AutoreplyMessageController extends Controller
{
    public function index(){
        return view('pages.autoreply-history',[
            'autoreplyMessages' => AutoreplyMessages::with(['repliedMessage', 'message', 'autoreply'])->whereHas('autoreply', function($q){
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
            $q->where('user_id', Auth::id());
        })->count();
        if($userHasAccess){
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

    public function refresh(Request $request){

        $page = intval($request->get('page', 1));
        $page = $page < 1? 1: $page;

        $messages = AutoreplyMessages::with(['repliedMessage', 'message', 'autoreply'])->whereHas('autoreply', function($q){
            $q->where('user_id', Auth::id())->whereDevice(session()->get('selectedDevice'));
        })->latest()->skip(($page - 1) * 15)->take(15)->get();
        return response()->json([
            'data' => $messages->map(function($item){

                return [
                    'id' => $item->id,
                    'status' => $item->status,
                    'updated_at' => $item->sent_at,
                    'view' => view('components.tables.autoreply-history-table-row', [
                        'message' => $item
                    ])->render(),
                ];
            }),
        ]);
    }

    private function getHistoryQuery(){
        return AutoreplyMessages::with(['repliedMessage', 'message', 'autoreply'])->whereHas('autoreply', function($q){
            $q->where('user_id', Auth::id())->whereDevice(session()->get('selectedDevice'));
        })->latest();
    }

    public function ajaxTable(Request $request){
        $columns = [
            0 =>'target_name',
            1 =>'target_number',
            2=> 'incoming_message',
            3=> 'status',
            4=> 'received_at',
            5=> 'sent_at',
            6=> 'action',
        ];
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $totalData = $this->getHistoryQuery()->count();
        $totalFiltered = $totalData;

        if(empty($request->input('search.value')))
        {
            $messages = $this->getHistoryQuery()->offset($start)
                ->limit($limit);
        } else {
            $search = $request->input('search.value');

            $messages = $this->getHistoryQuery()->where(function($query) use ($search) {
                $query->where('id','LIKE',"%{$search}%")
                    ->orWhereHas('message', function($q) use ($search) {
                        $q->where('message', 'LIKE',"%{$search}%")->orWhere('message', 'LIKE',"%{$search}%");
                    })
                    ->orWhereHas('repliedMessage', function($q) use ($search) {
                        $q->where('message', 'LIKE',"%{$search}%")
                            ->orWhere('message', 'LIKE',"%{$search}%")
                            ->orWhereHas('conversation', function($q) use ($search) {
                                $q->Where('target_name', 'LIKE',"%{$search}%")
                                    ->orWhere('target_number', 'LIKE',"%{$search}%")
                                    ->orWhere('defined_name', 'LIKE',"%{$search}%");
                            });
                    })
                    ->orWhere('status', 'LIKE',"%{$search}%");
            })
                ->offset($start)
                ->limit($limit);

            $totalFiltered = $messages->count();
        }

        $messages = $messages->get();



        $data = array();
        if(!empty($messages))
        {
            foreach ($messages as $message)
            {

                $nestedData = [
                    'target_name' => view('components.tables.history.target_name', ['message' => $message])->render(),
                    'target_number' => view('components.tables.history.target_number', ['message' => $message])->render(),
                    'incoming_message' => view('components.tables.history.target_message', ['message' => $message])->render(),
                    'status' => view('components.tables.history.status', ['message' => $message])->render(),
                    'received_at' => view('components.tables.history.received_at', ['message' => $message])->render(),
                    'sent_at' => view('components.tables.history.sent_at', ['message' => $message])->render(),
                    'action' => view('components.tables.history.actions', ['message' => $message])->render(),
                    'id' => $message->id,
                ];
                $data[] = $nestedData;
            }
        }

        return response()->json(array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ));
    }

    public function export(Request $request){
        $startTime = $request->post('start_time');
        $endTime = $request->post('end_time');
        $request->validate([
           'start_time' => 'nullable|date',
           'end_time' => 'nullable|date' . ($startTime ? '|after:start_time': ''),
           'status' => 'array',
            'status.*' => 'in:processing,pending,failed,success',
        ]);

        $autoreplyMessages = $this->getHistoryQuery()->where(function($q) use ($endTime, $startTime) {
           if($startTime || $endTime){
               $q->whereHas('repliedMessage', function($q) use ($startTime, $endTime) {
                   if($startTime && $endTime){
                       $q->where('sent_at', '>=', Carbon::make($startTime))->where('sent_at', '<=', Carbon::make($endTime));
                   } else if($startTime){
                       $q->where('sent_at', '>=', Carbon::make($startTime));
                   } else {
                       $q->where('sent_at', '<=', Carbon::make($endTime));
                   }
               });
           }
        })->get();

        $date = Carbon::now()->format('Y-m-d_H_s_i');
        return Excel::download(new AutoreplyHistoryExport($autoreplyMessages), session()->get('selectedDevice') . '_' . $date . '_autoreply_export_.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

}
