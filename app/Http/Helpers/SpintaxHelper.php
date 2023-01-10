<?php

use App\Models\Contact;
use Illuminate\Support\Str;

if(!class_exists('SpintaxHelper')){
    class SpintaxHelper {

        private static function generateName(string $message, Contact $contact){
            return preg_replace('(\{\{nama\}\})', $contact->name, $message);
        }
        private static function generateNumber(string $message, Contact $contact){
            return preg_replace('(\{\{nomor\}\})', $contact->number, $message);
        }
        public static function generateSpintaxMap(string $message){
            return preg_replace_callback('/(\{\{([\w\s]*([|][\w\s]*)*)\}\})/gi', function ($matches) {
                $splits = explode('|', $matches[1] ?? '');
                $items = array_filter($splits, function($item){return !!strlen($item);});
                if(!count($items))
                    return '';
                return $items[array_rand($items)];
            }, $message);
        }
        private static function generateColumnMap(string $message, Contact $contact){
            return preg_replace_callback('(\{\{(var)([0-9]+)\}\})', function ($matches) use($contact) {
                return $contact->raw_values[intval($matches[2]) + 1] ?? '';
            }, $message);
        }

        public static function generate(string $message, Contact $contact){
            $msg = self::generateName($message, $contact);
            $msg = self::generateNumber($msg, $contact);
            $msg = self::generateSpintaxMap($msg);
            return self::generateColumnMap($msg, $contact);
        }

    }
}