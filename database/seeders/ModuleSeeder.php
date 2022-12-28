<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Module::factory()->createMany([
           [
               'name' => 'user_management',
               'label' => 'Management Pengguna',
               'description' => 'Pengelolaan dan Pengaturan Akun Pengguna',
           ]
        ]);
    }
}
