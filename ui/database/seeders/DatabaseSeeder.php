<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Demo', 'email' => 'demo@sourcedraft.test', 'password' => 'sourcedraft']);
    }
}
