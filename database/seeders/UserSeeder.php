<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const EMPLOYEES = 67;

    /**
     * Mock accounts for local development: one admin plus 67 employees.
     * Every account uses the password "password".
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->callOnce(RoleSeeder::class);

        $admin = $this->account(['name' => 'Некруз', 'surname' => 'Абдуллоев', 'patronymic' => 'Саидович', 'sex' => 'male', 'email' => 'admin@evolet.test']);
        $admin->assignRole('admin');

        // Employees that are easy to remember when logging in.
        $fixed = [
            ['name' => 'Дилноза', 'surname' => 'Назарова', 'patronymic' => 'Рустамовна', 'sex' => 'female', 'email' => 'd.nazarova@evolet.test'],
            ['name' => 'Сухроб', 'surname' => 'Каримов', 'patronymic' => 'Азизович', 'sex' => 'male', 'email' => 's.karimov@evolet.test'],
            ['name' => 'Фарход', 'surname' => 'Рахимов', 'patronymic' => 'Джамшедович', 'sex' => 'male', 'email' => 'f.rakhimov@evolet.test'],
            ['name' => 'Мадина', 'surname' => 'Каримова', 'patronymic' => 'Умедовна', 'sex' => 'female', 'email' => 'm.karimova@evolet.test'],
            ['name' => 'Алишер', 'surname' => 'Шарипов', 'patronymic' => 'Далерович', 'sex' => 'male', 'email' => 'a.sharipov@evolet.test'],
        ];

        foreach ($fixed as $attributes) {
            $this->account($attributes);
        }

        $missing = self::EMPLOYEES - User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->count();

        if ($missing > 0) {
            User::factory($missing)->has(UserDetail::factory(), 'details')->create();
        }

        $this->assignRoles();
        $this->addChildren();
    }

    /**
     * Married employees get 0–3 children, born after the parent turned 20.
     */
    private function addChildren(): void
    {
        User::doesntHave('children')
            ->whereHas('details', fn ($q) => $q->where('marital_status', 'married'))
            ->with('details')
            ->get()
            ->each(function (User $user) {
                $earliest = $user->details->birth_date?->copy()->addYears(20)->max(now()->subYears(25));

                if (! $earliest || $earliest->isFuture()) {
                    return;
                }

                UserChild::factory(fake()->numberBetween(0, 3))
                    ->for($user)
                    ->ofFamily($user->surname)
                    ->state(fn () => ['birth_date' => fake()->dateTimeBetween($earliest, '-1 month')])
                    ->create();
            });
    }

    /**
     * A couple of department heads, a few division heads, everyone else spread
     * over the remaining roles. About one in six also holds a second regular
     * position (roles are many-to-many).
     */
    private function assignRoles(): void
    {
        $regular = array_keys(array_diff_key(RoleSeeder::ROLES, array_flip(['admin', 'department-head', 'division-head'])));
        $pool = [...array_fill(0, 2, 'department-head'), ...array_fill(0, 6, 'division-head')];

        User::doesntHave('roles')->orderBy('id')->get()->each(function (User $user, int $index) use ($pool, $regular) {
            $first = $pool[$index] ?? fake()->randomElement($regular);
            $roles = [$first];

            if (fake()->boolean(17)) {
                $roles[] = fake()->randomElement(array_values(array_diff($regular, [$first])));
            }

            $user->assignRole($roles);
        });
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function account(array $attributes): User
    {
        $user = User::updateOrCreate(
            ['email' => $attributes['email']],
            User::factory()->raw($attributes),
        );

        if (! $user->details()->exists()) {
            UserDetail::factory()->for($user)->create();
        }

        return $user;
    }
}
