<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\AutoreplyController;
use App\Http\Controllers\BlastController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RestapiController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScheduleMessageController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;


require_once 'custom-route.php';

Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
  
    \UniSharp\LaravelFilemanager\Lfm::routes();
    
});
Route::get('/', function()
{
    return Redirect::to( '/login');
    // OR: return Redirect::intended('/bands'); // if using authentication
});
Route::middleware('installed.app','auth')->group(function (){
   
    Route::get('/file-manager',[FileManagerController::class,'index'])->name('file-manager');

    Route::get('/home',[HomeController::class,'index'])->name('home');
    Route::post('/home/setSessionSelectedDevice',[HomeController::class,'setSelectedDeviceSession'])->name('home.setSessionSelectedDevice');
    Route::post('/home/sethook',[HomeController::class,'setHook'])->name('setHook');
    Route::post('/home',[HomeController::class,'store'])->name('addDevice');
    Route::delete('/home',[HomeController::class,'destroy'])->name('deleteDevice');
    Route::get('/scan/{number:body}', ScanController::class)->name('scan');

    Route::get('/autoreply',[AutoreplyController::class,'index'])->name('autoreply');
 
    Route::post('/autoreply',[AutoreplyController::class,'store'])->name('autoreply');
    Route::get('/autoreply/{type}',[AutoreplyController::class,'getFormByType']);
    Route::delete('/autoreply',[AutoreplyController::class,'destroy'])->name('autoreply.delete');
    Route::delete('/autoreply/all',[AutoreplyController::class,'destroyAll'])->name('deleteAllAutoreply');
    Route::get('/autoreply/show-reply/{id}',[AutoreplyController::class,'show']);

    Route::post('/contact/add',[ContactController::class,'store'])->name('addcontact');
    Route::post('/contact/export',[ContactController::class,'export'])->name('exportContact');
    Route::delete('/contact/delete_all',[ContactController::class,'DestroyAll'])->name('deleteAll');
    Route::delete('/contact/delete/{id}',[ContactController::class,'destroy'])->name('contactDeleteOne');
    Route::post('/contact/import',[ContactController::class,'import'])->name('importContacts');
    Route::post('/contact',[ContactController::class,'store'])->name('contact');
    Route::get('/contact/{contacts:tag_id}',[ContactController::class,'index']);


  Route::get('/tags',[TagController::class,'index'])->name('tag');
  Route::post('/tags',[TagController::class,'store'])->name('tag.store');
  Route::delete('/tags',[TagController::class,'destroy'])->name('tag.delete');
  Route::get('/tag/view/{id}',[TagController::class,'view']);
  Route::post('fetch-groups',[TagController::class ,'fetchGroups'])->name('fetch.groups');

  Route::get('/campaign/create',[CampaignController::class,'index'])->name('campaign.create');
  // pause campaign
  Route::post('/campaign/pause/{id}',[CampaignController::class,'pause'])->name('campaign.pause');
  // start campaign
  Route::post('/campaign/resume/{id}',[CampaignController::class,'resume'])->name('campaign.resume');

  Route::get('/campaigns',[CampaignController::class,'lists'])->name('campaign.lists');
  Route::get('/campaign/show/{id}',[CampaignController::class,'show'])->name('campaign.show');
Route::delete('/delete-all-campaigns',[CampaignController::class,'destroyAll'])->name('campaigns.delete.all');

  Route::post('/blast',[BlastController::class,'blastProccess'])->name('blast');
  Route::get('/blast/scheduled',[BlastController::class,'scheduled'])->name('scheduledMessage');
  Route::get('/blast/text-message',[BlastController::class,'getPageBlastText']);
  Route::get('/blast/image-message',[BlastController::class,'getPageBlastImage']);
  Route::get('/blast/button-message',[BlastController::class,'getPageBlastButton']);
  Route::get('/blast/template-message',[BlastController::class,'getPageBlastTemplate']);
  Route::get('/blast/histories/{blast:campaign_id}',[BlastController::class,'histories'])->name('blastHistories');

  Route::get('/message/test',[MessagesController::class,'index'])->name('messagetest');
  Route::get('/message/test',[MessagesController::class,'index'])->name('messagetest');

  Route::post('/message/test/text',[MessagesController::class,'textMessageTest'])->name('textMessageTest');
  Route::post('/message/test/image',[MessagesController::class,'imageMessageTest'])->name('imageMessageTest');
  Route::post('/message/test/button',[MessagesController::class,'buttonMessageTest'])->name('buttonMessageTest');
  Route::post('/message/test/template',[MessagesController::class,'templateMessageTest'])->name('templateMessageTest');
  Route::post('/message/test/list',[MessagesController::class,'listMessageTest'])->name('listMessageTest');

  Route::get('/rest-api',RestapiController::class)->name('rest-api');

  Route::get('/user/change-password',[UserController::class,'changePassword'])->name('user.changePassword');
  Route::post('/user/change-password',[UserController::class,'changePasswordPost'])->name('changePassword');
  Route::post('/user/setting/apikey',[UserController::class,'generateNewApiKey'])->name('generateNewApiKey');
  Route::post('/user/settings/chunk',[UserController::class,'changeChunk'])->name('changeChunk');

  
  Route::get('/settings',[SettingController::class,'index'])->name('settings');
  Route::post('/settings/server',[SettingController::class,'setServer'])->name('setServer');

  Route::get('/schedule',[ScheduleMessageController::class,'index'])->name('scheduleMessage');

  Route::get('/admin/manage-user',[AdminController::class,'manageUser'])->name('admin.manageUser')->middleware('admin');
  Route::post('/admin/user/store',[AdminController::class,'userStore'])->name('user.store')->middleware('admin');
Route::delete('/admin/user/delete/{id}',[AdminController::class,'userDelete'])->name('user.delete')->middleware('admin');
  Route::get('admin/user/edit',[AdminController::class,'userEdit'])->name('user.edit')->middleware('admin');
  Route::post('admin/user/update',[AdminController::class,'userUpdate'])->name('user.update')->middleware('admin');


  Route::post('/logout', LogoutController::class)->name('logout');
});

Route::middleware('installed.app','guest')->group(function (){

    Route::get('/login',[LoginController::class,'index'])->name('login');
    Route::get('/register',[RegisterController::class,'index'])->name('register');
    Route::post('/register',[RegisterController::class,'store'])->name('register');
    Route::post('/login',[LoginController::class,'store'])->name('login');

});

Route::get('/install', [SettingController::class,'install'])->name('setting.install_app');
Route::post('/install', [SettingController::class,'install'])->name('settings.install_app');

Route::post('/settings/check_database_connection',[SettingController::class,'test_database_connection'])->name('connectDB');
Route::post('/settings/activate_license',[SettingController::class,'activate_license'])->name('activateLicense');


