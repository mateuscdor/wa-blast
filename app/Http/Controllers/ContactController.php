<?php

namespace App\Http\Controllers;

use App\Exports\ContactsExport;
use App\Imports\ContactImport;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Number;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{

    public function index($tag){
        $contacts = Contact::whereUserId(Auth::user()->id)->whereTagId($tag)->get();

        $tag = Tag::with('contacts')->get()->find($tag);
        // dd($tag->contacts);
        return view('pages.contact',[
            'contacts' => $contacts,
            'tag' => $tag
        ]);
    }

    public function fetchContacts($tagId){

        $tag = Tag::where('user_id', Auth::id())->find($tagId);

        if(!$tag){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'Tag not found',
            ]);
        }
        if(!session()->has('selectedDevice')){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'Please select a device'
            ]);
        }
        $deviceNumber = Number::where('body', session()->get('selectedDevice'))->whereUserId(Auth::id())->first();

        if(!$deviceNumber){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'Number not found'
            ]);
        }

        $url = env('WA_URL_SERVER') . '/backend-contacts';
        $result = Http::withoutVerifying()->asForm()->post($url, [
            'token' => $deviceNumber->body,
        ])->json();

        $count = 0;
        if(isset($result['data'])){
            $res = collect($result['data'])->map(function($item) use ($tagId) {
                $contact = [];
                $contact['user_id'] = Auth::id();
                $contact['tag_id'] = $tagId;
                $number = explode('@', $item['id'])[0];
                $name = $item['name'] ?? $item['notify'] ?? '';
                $contact['raw_values'] = json_encode([$name, $number]);
                $contact['number'] = $number;
                $contact['name'] = $name;
                $contact['document_id'] = null;
                return $contact;
            })->each(function($contact) use ($tagId, &$count) {
                $attrs = [
                    'number' => $contact['number'],
                    'tag_id' => $tagId,
                    'user_id' => Auth::id(),
                ];
                $c = Contact::where($attrs);
                if($c->count()){
                    $c->update($contact);
                } else {
                    $c = Contact::create($contact);
                    $count += 1;
                }
            });
        }

        return redirect()->back()->with('alert', [
            'type' => ($result['success'] ?? false)? 'success': 'danger',
            'msg' => ($result['success'] ?? false)? 'Imported ' . $count . ' contacts' : $result['danger'],
        ]);
    }

    public function store(Request $request){

        $request->validate([
            'number' => ['unique:contacts']
        ]);

        Contact::create([
            'user_id' => Auth::user()->id,
            'tag_id' => $request->tag,
            'name' => $request->name,
            'number' => $request->number,
            'raw_values' => '[]'
        ]);

        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Contact added!'
        ]);

    }

    public function update(Request $request){
        $request->validate([
            'name' => 'nullable|string|max:50',
            'number' => 'required|string|max:20',
            'id' => 'required|exists:contacts,id',
        ]);

        $contact = Contact::where('user_id', Auth::id())->find($request->id);
        if(!$contact) {
            return back()->with('alert',[
                'type' => 'danger',
                'msg' => 'Contact not found'
            ]);
        }

        $contact->update([
            'name' => $request->name,
            'number' => $request->number,
        ]);

        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Contact updated!'
        ]);
    }


    public function import(Request $request){
        try {
            $document = saveDocument($request->file('fileContacts'));
            Excel::import(new ContactImport($request->tag, $document), storage_path('app/') . $document->full_path);
            return back()->with('alert',[
                'type' => 'success',
                'msg' => 'Success Import'
            ]);
        } catch (\Throwable $th) {
            return back()->with('alert',[
                'type' => 'danger',
                'msg' => $th->getMessage()
            ]);
        }
    }

    public function export(Request $request){
        return Excel::download(new ContactsExport($request->tag),'contacts.xlsx');
    }

    public function deleteSelections(Request $request){
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'exists:contacts,id',
        ]);

        $userHasAccess = !Contact::with('user')->whereIn('id', $request->id)->where('user_id', '!=', Auth::id())->count();
        if(!$userHasAccess){
            return redirect()->back()->with('alert', [
                'type' => 'danger',
                'msg' => 'You don\'t have access to delete a contact or many contact of the selected items',
            ]);
        }
        Contact::with('document')->whereIn('id', $request->id)->each(function($item){
            $item->document()->delete();
            $item->delete();
        });
        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => count($request->id) . ' Contacts have been deleted',
        ]);
    }

    public function destroyAll(Request $request){

        $contacts = Contact::whereTagId($request->tag);
        Document::whereIn('id', $contacts->pluck('document_id'))->get()->each(function($doc){
            removeDocument($doc);
        });
        $contacts->delete();
        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'All contacts are deleted.'
        ]);
    }

    public function destroy($id){
        Contact::find($id)->delete();
        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Contact '.$id. ' deleted.'
        ]);
    }
}
