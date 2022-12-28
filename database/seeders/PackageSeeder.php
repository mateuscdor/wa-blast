<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Package::factory()->createMany([
           [
               'name' => 'Paket A',
               'users' => 10,
               'user_device' => 10,
               'admin_device' => 10,
               'live_chat' => false,
               'level_id' => Level::LEVEL_RESELLER
           ],
            [
                'name' => 'Paket B',
                'users' => 20,
                'user_device' => 20,
                'admin_device' => 10,
                'live_chat' => false,
                'level_id' => Level::LEVEL_RESELLER
            ],
            [
                'name' => 'Paket C',
                'users' => 20,
                'user_device' => 50,
                'admin_device' => 10,
                'live_chat' => false,
                'level_id' => Level::LEVEL_RESELLER
            ],
            [
                'name' => 'Paket A',
                'users' => 20,
                'user_device' => 50,
                'admin_device' => 10,
                'live_chat' => true,
                'level_id' => Level::LEVEL_ADMIN
            ],

        ]);
    }
}
