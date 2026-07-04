<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        \App\Models\Program::create([
            'name' => 'English',
            'slug' => 'english',
            'description' => 'Just English Program 🏴󠁧󠁢󠁥󠁮󠁧󠁿',
        ]);
        \App\Models\Program::create([
            'name' => 'French',
            'slug' => 'french',
            'description' => 'French 🇫🇷  Program',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'BYAMUNGU Lewis',
            'email' => 'byamungulewis@gmail.com',
            'phone' => '+250785436135',
            'role' => 'super_admin',
            'password' => bcrypt('byamungu')
        ]);
        // \App\Models\User::factory(5)->create();
    }
}
