<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_details_are_not_shared_with_the_page()
    {
        $user = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.surname', $user->surname)
                ->where('auth.user.sex', $user->sex)
                ->missing('auth.user.details')
                ->missing('auth.user.passport_number')
                ->missing('auth.user.phone')
                ->missing('auth.user.birth_date')
                ->missing('auth.user.hired_at')
                ->missing('auth.user.nationality')
            );
    }
}
