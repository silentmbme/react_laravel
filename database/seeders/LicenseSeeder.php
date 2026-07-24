<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        License::insert([
            [
                'name' => 'Regular License',
                'slug' => 'regular-license',
                'status' => true
            ],
            [
                'name' => 'Extended License',
                'slug' => 'extended-license',
                'status' => true
            ]
        ]);
    }
}
