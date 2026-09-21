<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Mock accounts for local development. Every account uses the password "password".
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $accounts = [
            ['name' => 'Дилноза Назарова', 'email' => 'd.nazarova@evolet.test'],
            ['name' => 'Сухроб Каримов', 'email' => 's.karimov@evolet.test'],
            ['name' => 'Фарход Рахимов', 'email' => 'f.rakhimov@evolet.test'],
            ['name' => 'Мадина Каримова', 'email' => 'm.karimova@evolet.test'],
            ['name' => 'Алишер Шарипов', 'email' => 'a.sharipov@evolet.test'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                User::factory()->raw($account),
            );
        }

        User::factory(20)->create();
    }
}
