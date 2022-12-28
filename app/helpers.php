<?php

use App\Models\Level;
use Illuminate\Support\Facades\Auth;

function str_extract($str, $pattern, $get = null, $default = null)
{
    $result = [];

    preg_match($pattern, $str, $matches);

    preg_match_all('/(\(\?P\<(?P<name>.+)\>\.\+\)+)/U', $pattern, $captures);

    $names = $captures['name'] ?? [];

    foreach($names as $name)
    {
        $result[$name] = $matches[$name] ?? null;
    }

    return $get ? ($result[$get] ?? $default) : $result;
}

function wrap_str($str = '', $first_delimiter = "'", $last_delimiter = null)
{
    if(!$last_delimiter)
    {
        return $first_delimiter.$str.$first_delimiter;
    }

    return $first_delimiter.$str.$last_delimiter;
}

function hasLiveChatAccess(){
    $package = Auth::user()->package;
    return in_array(Auth::user()->level_id, [Level::LEVEL_RESELLER, Level::LEVEL_SUPER_ADMIN]) || ($package && $package->live_chat);
}
function getLastJSTime(){
    return \Illuminate\Support\Str::random();
}

function getSystemSettings($name, $default = ''){
    $settings = \App\Models\System::where('name', $name)->first();
    if($settings){
        return $settings->value;
    }
    return $default;
}
?>
