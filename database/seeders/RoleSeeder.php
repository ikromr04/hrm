<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'display_name' => 'Администратор'],
            ['name' => 'manager', 'display_name' => 'Менеджер'],
            ['name' => 'employee', 'display_name' => 'Сотрудник'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
