<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class AdminController extends Controller
{
    
    public function manageUser()
    {
        $users = User::with(['numbers', 'package'])->latest();
        $authLevelId = Auth::user()->level_id;

        if($authLevelId !== Level::LEVEL_SUPER_ADMIN){
            $users = $users->where('registered_by', '=', Auth::user()->id);
            $levels = Level::where('id', '>', $authLevelId)->get();
        } else {
            $levels = Level::all();
        }

        if($authLevelId === Level::LEVEL_SUPER_ADMIN){
            $packages = Package::where('level_id', Level::LEVEL_ADMIN)->get();
        } else if($authLevelId === Level::LEVEL_RESELLER){
            $packages = Package::where('level_id', Level::LEVEL_RESELLER)->get();
        } else {
            $packages = collect();
        }

        $view = view('pages.admin.manageusers',[
            'groups' => $users->get()->groupBy('level_id'),
            'levels' => $levels,
            'adminPackages' => $packages,
        ]);

        return $view;
    }

    public function userStore(Request $request){
        $request->validate([
            'username' => 'required|unique:users',
            'email' => 'required|unique:users',
            'password' => 'required',
//            'limit_device' => 'required|numeric|max:10',
//            'active_subscription' => 'required|',
            'level_id' => 'required|exists:levels,id',
            'display_name' => 'required|string',
        ]);

        $user = new User();
        $requestLevelId = intval($request->level_id);
        $authLevelId = Auth::user()->level_id;

        // If subscription ended:
        if($authLevelId !== Level::LEVEL_SUPER_ADMIN){
            if(Auth::user()->active_subscription !== 'active' || Auth::user()->subscription_expired < date('Y-m-d')){
                return redirect()->back()->with('alert' , ['type' => 'danger', 'msg' => 'Subscribe to create a user']);
            }
        }

        if($requestLevelId !== Level::LEVEL_CUSTOMER_SERVICE){
            $request->validate([
                'active_subscription' => 'required|in:active,inactive,lifetime',
            ]);
            if($request->active_subscription == 'active'){
                $request->validate([
                    'subscription_expired' => 'required|date',
                ]);

                // subscription expired must be greater than today
                if($request->subscription_expired < date('Y-m-d')){
                    return redirect()->back()->with('alert' , ['type' => 'danger', 'msg' => 'Subscription expiry date must be greater than today']);
                }
            }
            $user->active_subscription = $request->active_subscription;
            $user->subscription_expired = $request->subscription_expired ?? null;
        } else if($authLevelId === Level::LEVEL_ADMIN){
            $user->active_subscription = Auth::user()->active_subscription;
            $user->subscription_expired = Auth::user()->subscription_expired;
        }
        // Level:

        if($authLevelId >= $requestLevelId && $authLevelId !== Level::LEVEL_SUPER_ADMIN){
            return redirect()->back()->with('alert' , ['type' => 'danger', 'msg' => 'You can\'t register a user higher than yours']);
        }


        $availablePackages = [];

        if($authLevelId === Level::LEVEL_RESELLER){
            $availablePackages = Package::where('level_id', Level::LEVEL_RESELLER)->pluck('id');
        } else if($authLevelId === Level::LEVEL_SUPER_ADMIN){
            $availablePackages = Package::where('level_id', Level::LEVEL_ADMIN)->pluck('id');
        }

        // If the added user has admin level.
        if($requestLevelId === Level::LEVEL_ADMIN){
            $request->validate([
                'package_id' => 'required|exists:packages,id',
            ]);
            $package = Package::whereIn('id', $availablePackages)->find($request->package_id);
            if(!$package){
                return redirect()->back()->with('alert' , ['type' => 'danger', 'msg' => 'Please select a proper package']);
            }

            $user->package_id = $package->id;
            $user->limit_device = $package->admin_device;
        } else if($requestLevelId === Level::LEVEL_CUSTOMER_SERVICE){
            if($authLevelId === Level::LEVEL_ADMIN){
                $package = Auth::user()->package;

                // If auth user subscription expired:
                if(!$package || Auth::user()->subscription_expired < date('Y-m-d')){
                    return redirect()->back()->with('alert' , ['type' => 'danger', 'msg' => 'Your subscription has been expired']);
                }

                $totalUsers = User::where('registered_by', Auth::user()->id)->count();

                if($package->users > $totalUsers){
                    $user->package_id = $package->id;
                    $user->limit_device = $package->user_device;
                } else {
                    return redirect()->back()->with('alert' , ['type' => 'danger', 'msg' => 'Cannot create new user, your subscription only provides ' . $package->users . ' users']);
                }
            }
        } else {
            $user->limit_device = $request->limit_device;
        }
         
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->api_key = Str::random(32);
        $user->chunk_blast = 0;
//        $user->limit_device = $request->limit_device;
        $user->level_id = $requestLevelId;
        $user->display_name = $request->display_name;
        $user->phone_number = $request->phone_number;
        $user->registered_by = Auth::user()->id;

        $user->save();
        return redirect()->back()->with('alert', ['type' => 'success', 'msg' => 'User created successfully']);
         


    }

    public function userEdit(){
        $id = request()->id;
        $user = User::find($id);
        // return data user to ajax
       return response()->json($user);
    }
    public function userUpdate(Request $request){

        $authLevelId = Auth::user()->level_id;
        $request->validate([
            'id' => 'required|exists:users,id',
            'username' => 'required|unique:users,username,'.$request->id,
            'email' => 'required|unique:users,email,'.$request->id,
//            'limit_device' => 'required|numeric|max:10',
            'active_subscription' => 'required|',

        ]);

        $requestLevelId = intval($request->level_id);

        if($request->active_subscription == 'active'){
            $request->validate([
               'subscription_expired' => 'required|date',
            ]);

            // subscription expired must be greater than today
            if($request->subscription_expired < date('Y-m-d')){
                return redirect()->back()->with('alert' , ['type' => 'danger', 'msg' => 'Subscription expired must be greater than today']);
            }
        }

       
        if($request->password != ''){
            $request->validate([
                'password' => 'min:6',
            ]);
        }
        $user = User::with('package')->find($request->id);

        if($requestLevelId === Level::LEVEL_ADMIN){
            $request->validate([
                'package_id' => 'required|exists:packages,id',
            ]);
            if($user->package_id !== $user->limit_device){
                $user->package_id = $request->package_id;
                $user->limit_device = Package::find($request->package_id)->admin_device;
            }
        } else if($requestLevelId === Level::LEVEL_RESELLER) {
            $user->limit_device = $request->limit_device;
        }

        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = $request->password != '' ? bcrypt($request->password) : $user->password;
        $user->phone_number = $request->phone_number;
        $user->active_subscription = $request->active_subscription;
        $user->subscription_expired = $request->subscription_expired ?? null;
        $user->save();

        if($user->level_id === Level::LEVEL_ADMIN){
            $user->createdUsers()->update([
               'active_subscription' => $user->active_subscription,
               'subscription_expired' => $user->subscription_expired,
               'package_id' => $user->package_id,
               'limit_device' => $user->package? $user->package->user_device: 0,
            ]);
        }

        return redirect()->back()->with('alert', ['type' => 'success', 'msg' => 'User updated successfully']);
    }

    public function userDelete($id){
        $user = User::find($id);
        $authLevelId = Auth::user()->level_id;
        if($user->level_id === Level::LEVEL_SUPER_ADMIN){
            return redirect()->back()->with('alert', ['type' => 'danger', 'msg' => 'You can not delete super admin']);
        }
        if($authLevelId === Level::LEVEL_SUPER_ADMIN && Auth::user()->id !== $user->registered_by){
            return redirect()->back()->with('alert', ['type' => 'danger', 'msg' => 'You can not delete other users']);
        }

        // delete all data user
        $user->numbers()->delete();
        $user->autoreplies()->delete();
        $user->contacts()->delete();
        $user->tags()->delete();
        $user->blasts()->delete();
        $user->campaigns()->delete();

        $user->delete();
        return redirect()->back()->with('alert', ['type' => 'success', 'msg' => 'User deleted successfully']);
    }
}
