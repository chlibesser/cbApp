<?php

namespace Database\Seeders;

use App\Core\Auth\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::create([
            'username' => 'admin',
            'email' => 'admin@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        Account::create([
            'username' => 'testuser',
            'email' => 'test@cbapp.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }
}
