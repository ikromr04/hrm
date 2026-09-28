<?php

namespace Tests;

use App\Models\User;
use App\Support\Access;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    /**
     * A colleague who may look around: the employee list, the structure and the
     * fleet, which is what every position carries (Access::DEFAULTS). Anything
     * beyond looking is a right a test hands out for itself.
     */
    protected function colleague(array $attributes = []): User
    {
        return $this->mayLookAround(User::factory()->create($attributes));
    }

    /**
     * The same for somebody the test has already created, for when it matters
     * how many people there are.
     */
    protected function mayLookAround(User $user): User
    {
        if (Permission::query()->doesntExist()) {
            $this->seed(PermissionSeeder::class);
        }

        $user->givePermissionTo(Access::DEFAULTS);

        return $user;
    }
}
