<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = config('demo.password');
        if (! is_string($password) || strlen($password) < 8) {
            $this->command?->warn('Defina DEMO_PASSWORD no .env para gerar a biblioteca de demonstração.');

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => config('demo.email')],
            ['name' => config('demo.name'), 'password' => Hash::make($password)],
        );
        (new LibraryDemoSeeder)->seedFor($user);
    }
}
