<?php

namespace Database\Seeders;

use App\Models\Number;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'user_id' => 1,
                'body' => '6282298859671',
                'webhook' => '',
                'status' => 'Disconnect',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'user_id' => 1,
                'body' => '6289522811620',
                'webhook' => '',
                'status' => 'Disconnect',
                'created_at' => now(),
                'updated_at' => now(),
                'api_key' => Str::random(30)
            ],
            [
                'user_id' => 1,
                'body' => '6281511684195',
                'webhook' => '',
                'status' => 'Disconnect',
                'created_at' => now(),
                'updated_at' => now(),
                'api_key' => Str::random(30)
            ]
        ];

       foreach($data as $d){
           Number::create($d);
       }
    }
}
