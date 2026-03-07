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
        $admin = Role::where('name', 'admin')->first();
        $manager = Role::where('name', 'manager')->first();
        $employee = Role::where('name', 'employee')->first();

        User::where('login', 'lolita_holmatova')->first()->roles()->attach([$manager->id, $employee->id]);
        User::where('login', 'ikulobm')->first()->roles()->attach([$admin->id, $employee->id]);

        User::whereNotIn('login', ['lolita_holmatova', 'ikulobm'])
            ->get()
            ->each(function ($user) use ($employee) {
                $user->roles()->attach($employee->id);
            });
    }
}
