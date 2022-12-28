<?php

namespace App\Http\Controllers;

use App\Models\UserTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserTemplateController extends Controller
{
    //
    public function index(){
        return view('pages.template-lists', [
            'templates' => UserTemplate::withCount(['relatedCampaigns'])->where('user_id', Auth::user()->id)->get()
        ]);
    }

    public function create(){
        return view('pages.template-create', [

        ]);
    }
    public function edit($id){
        $userTemplate = UserTemplate::where('user_id', Auth::user()->id)->findOrFail($id);
        return view('pages.template-create', [
            'template' => $userTemplate
        ]);
    }

    public function update(Request $request, $id){

        if ($request->ajax()) {
            if ($request->user()->is_expired_subscription) {
                session()->flash('alert', [
                    'type' => 'danger',
                    'msg' =>
                        'Your subscription has expired. Please renew your subscription.',
                ]);
                return 'false';
            }
            $userTemplate = UserTemplate::where('user_id', Auth::user()->id)->findOrFail($id);

            $parsedRequest = UserTemplate::parseRequest($request);
            if(!$parsedRequest){
                session()->flash('alert', [
                    'type' => 'danger',
                    'msg' =>
                        'Invalid Format',
                ]);
                return 'false';
            }

            $data = [
                'user_id' => Auth::user()->id,
                'label' => $request->post('label', 'Unlabeled'),
                'message' => $parsedRequest,
            ];

            $userTemplate->update($data);

            session()->flash('alert', [
                'type' => 'success',
                'msg' =>
                    'Template has been created successfully',
            ]);
            return;
        }
        session()->flash('alert', [
            'type' => 'danger',
            'msg' =>
                'Please use ajax call',
        ]);
        return 'false';
    }

    public function store(Request $request){

        if ($request->ajax()) {
            if ($request->user()->is_expired_subscription) {
                session()->flash('alert', [
                    'type' => 'danger',
                    'msg' =>
                        'Your subscription has expired. Please renew your subscription.',
                ]);
                return 'false';
            }

            $parsedRequest = UserTemplate::parseRequest($request);
            if(!$parsedRequest){
                session()->flash('alert', [
                    'type' => 'danger',
                    'msg' =>
                        'Invalid Format',
                ]);
                return 'false';
            }

            $userTemplate = new UserTemplate([
               'user_id' => Auth::user()->id,
               'label' => $request->post('label', 'Unlabeled'),
               'message' => $parsedRequest,
            ]);
            $userTemplate->save();

            session()->flash('alert', [
                'type' => 'success',
                'msg' =>
                    'Template has been created successfully',
            ]);
            return;
        }
        session()->flash('alert', [
            'type' => 'danger',
            'msg' =>
                'Please use ajax call',
        ]);
        return 'false';
    }

    public function remove($id){
        $template = UserTemplate::findOrFail($id);
        $template->delete();
        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' =>
                'Template has been deleted successfully',
        ]);
    }
}
