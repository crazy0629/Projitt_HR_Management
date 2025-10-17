<?php

namespace Database\Seeders;

use Database\Seeders\Country\CountrySeeder;
use Database\Seeders\Country\UsStatsSeeder;
use Database\Seeders\Master\MasterSeeder;
use Database\Seeders\Question\QuestionSeeder;
use Database\Seeders\Role\RoleSeeder;
use Database\Seeders\User\UserSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([

            RoleSeeder::class,
            UserSeeder::class,
            MasterSeeder::class,
            CountrySeeder::class,
            UsStatsSeeder::class,
            QuestionSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
