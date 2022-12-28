<?php

namespace App\Http\Controllers;

use App\Exports\ContactsExport;
use App\Imports\ContactImport;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function store(Request $request){

        $request->validate([
            'number' => ['unique:contacts']
        ]);

        Contact::create([
            'user_id' => Auth::user()->id,
            'tag_id' => $request->tag,
            'name' => $request->name,
            'number' => $request->number
        ]);

        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Contact added!'
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
