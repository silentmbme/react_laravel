<?php
namespace Database\Seeders;
use App\Models\User; use Illuminate\Database\Seeder; use Illuminate\Support\Facades\Hash;
class AdminSeeder extends Seeder {
 public function run(): void {
  User::updateOrCreate(['email'=>'admin@gmail.com'],['name'=>'Admin','password'=>Hash::make('123456'),'role'=>'superadmin']);
 }
}