<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Level::factory()->createMany([
            [
                'id' => 1,
                'name' => 'Super Admin'
            ],
            [
                'id' => 2,
                'name' => 'Reseller'
            ],
            [
                'id' => 3,
                'name' => 'Admin'
            ],
            [
                'id' => 4,
                'name' => 'Customer Service'
            ]
        ]);
    }
}
