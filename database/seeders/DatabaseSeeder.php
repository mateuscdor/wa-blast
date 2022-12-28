<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            LevelSeeder::class,
            ModuleSeeder::class,
            LevelModuleSeeder::class,
            UserSeeder::class,
            NumberSeeder::class,
            PackageSeeder::class,
        ]);
    }
}
