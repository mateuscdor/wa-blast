<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Number;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TagController extends Controller
{
    public function index(){

        return view('pages.tag',[
            'tags' => Auth::user()->tags()->get(),
            'senders' => Auth::user()->numbers()->get()
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'name' => ['required','min:3','unique:tags']
        ]);

        Tag::create([
            'user_id' => Auth::user()->id,
            'name' => $request->name
        ]);

        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Success add tag!'
        ]);
    }


    public function destroy(Request $request){
        $t = Tag::with('contacts')->find($request->id);
        $t->delete();
        return back()->with('alert',[
            'type' => 'success',
            'msg' => 'Success delete tag!'
        ]);
    }

    public function fetchGroups(Request $request){
       try {
        $number = Number::whereBody($request->sender)->first();
        if($number->status != 'Connected'){
                return back()->with('alert', [
                    'type' => 'danger',
                    'msg' => 'Your sender is not connected!'
                ]);
            }
           $fetch =Http::withOptions(['verify' => false])->asForm()->post(env('WA_URL_SERVER').'/backend-getgroups',['token' => $request->sender]);
          $respon = json_decode($fetch->body());

       if($respon->status === false){
        return back()->with('alert',[
            'type' => 'danger',
            'msg' => $respon->message
        ]);
       }
                foreach ($respon->data as $group) {
                    $tag = Tag::firstOrCreate(['user_id'=> Auth::user()->id,'name' => $group->subject .'( '.$group->id.' )']);
                    
                   foreach ($group->participants as $member) {
                      $number = str_replace('@s.whatsapp.net','',$member->id);
                      $cek = Number::whereId(Auth::user()->id)->whereBody($number)->count();
                     if($cek < 1){

                          $tag->contacts()->create(['user_id' => Auth::user()->id,'name' => $number,'number' => $number]);
                     }

                   }
                }
                return back()->with('alert',[
                    'type' => 'success',
                    'msg' => 'Generate success'
                ]);
         
       } catch (\Throwable $th) {
            throw $th;
       }
    }


    //  ajax
    public function view($id,Request $request){
        if($request->ajax()){
            $contacts = Tag::find($id)->contacts()->latest()->get();
            return view('ajax.tag.view',[
                'contacts' => $contacts
            ])->render();
        }
    }

    public function deleteSelections(Request $request){
        $request->validate([
            'id' => 'required|array',
            'id.*' => 'exists:tags,id',
        ]);

        $userHasAccess = !Tag::with('user')->whereIn('id', $request->id)->where('user_id', '!=', Auth::id())->count();
        if(!$userHasAccess){
            return redirect()->back()->with('alert', [
               'type' => 'danger',
               'msg' => 'You don\'t have access to delete a tag or many tags of the selected items',
            ]);
        }
        Tag::with('contacts')->whereIn('id', $request->id)->each(function($item){
           $item->contacts()->delete();
           $item->delete();
        });
        return redirect()->back()->with('alert', [
            'type' => 'danger',
            'msg' => 'Selected Tags has been deleted',
        ]);
    }

    public function livechatImport(Request $request){
        $request->validate([
            'book_id' => 'required|exists:tags,id',
            'id' => 'required|array',
            'id.*' => 'exists:conversations,id',
        ]);

        $conversations = Conversation::whereIn('id', $request->post('id'));
        $tag = Tag::findOrFail($request->book_id);

        foreach ($conversations as $conversation){
            if(!$conversation->has_access){
                return redirect()->back()->with('alert', [
                    'type' => 'danger',
                    'msg' => 'You don\'t have the access of these conversations',
                ]);
            }
        }

        $saved = 0;
        foreach ($conversations as $conversation){
            $contact = Contact::where([
                'number' => $conversation->target_number,
                'tag_id' => $tag->id,
                'user_id' => Auth::id(),
            ])->first();
            if(!$contact){
                $contact = new Contact([
                    'name' => $conversation->target_name ?: $conversation->target_number,
                    'number' => $conversation->target_number,
                    'tag_id' => $tag->id,
                    'user_id' => Auth::id(),
                    'raw_values' => '[]',
                ]);
                $contact->save();
                $saved += 1;
            }
        }

        if(!$saved){
            return redirect()->back()->with('alert', [
                'type' => 'warning',
                'msg' => 'Contact already exists on ' . $tag->name,
            ]);
        }

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'msg' => $saved . ' contacts have been added to phonebook ' . $tag->name,
        ]);
    }
}
