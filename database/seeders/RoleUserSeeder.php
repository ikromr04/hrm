<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        User::where('login', 'manager')->first()->roles()->sync([$managerRole->id, $employeeRole->id]);
        User::where('login', 'admin')->first()->roles()->sync([$adminRole->id, $employeeRole->id]);

        User::whereNotIn('login', ['manager', 'admin'])
            ->get()
            ->each(function ($user) use ($employeeRole) {
                $user->roles()->attach($employeeRole->id);
            });
    }
}
