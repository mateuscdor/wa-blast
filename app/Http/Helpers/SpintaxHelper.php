<?php

use App\Models\Contact;
use Illuminate\Support\Str;

if(!class_exists('SpintaxHelper')){
    class SpintaxHelper {

        private static function generateName(string $message, Contact $contact){
            return preg_replace('(\{nama\})', $contact->name, $message);
        }
        private static function generateHello(string $message){
            $hours = \Carbon\Carbon::now()->hour;
            $replaceString = "Selamat Malam";
            if($hours >= 9 && $hours < 12){
                $replaceString = "Selamat Pagi";
            }
            if($hours >= 12 && $hours <= 15){
                $replaceString = "Selamat Siang";
            }
            if($hours > 15 && $hours <= 18){
                $replaceString = "Selamat Sore";
            }
            return preg_replace('(\{halo\})', $replaceString, $message);
        }
        private static function generateColumnMap(string $message, Contact $contact){
            return preg_replace_callback('(\{(var)([0-9]+)\})', function ($matches) use($contact) {
                return $contact->raw_values[intval($matches[2]) + 1] ?? '';
            }, $message);
        }

        public static function generate(string $message, Contact $contact){
            $msg = self::generateName($message, $contact);
            $msg = self::generateHello($msg);
            return self::generateColumnMap($msg, $contact);
        }

    }
}