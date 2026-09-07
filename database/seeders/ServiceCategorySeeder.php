<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('service_categories')->insert([
            'name' => 'Grooming',
            'description' => 'Pet grooming services',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('service_categories')->insert([
            'name' => 'Daycare',
            'description' => 'Pet daycare services',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('service_categories')->insert([
            'name' => 'Boarding',
            'description' => 'Pet boarding services',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('service_categories')->insert([
            'name' => 'Private Training',
            'description' => 'Pet private training services',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('service_categories')->insert([
            'name' => 'Group Class',
            'description' => 'Pet group class services',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('service_categories')->insert([
            'name' => 'A La Carte',
            'description' => 'Pet a la carte services',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('service_categories')->insert([
            'name' => 'Package',
            'description' => 'Pet package services',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
