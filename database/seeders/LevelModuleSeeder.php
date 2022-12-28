<?php

namespace Database\Seeders;

use App\Models\LevelModule;
use Illuminate\Database\Seeder;

class LevelModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        LevelModule::factory()->createMany([
            [
                'level_id' => 1,
                'module_id' => 1,
            ]
        ]);
    }
}
