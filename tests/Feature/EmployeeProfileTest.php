<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $employee = User::factory()->create();

        $this->get("/employees/{$employee->id}")->assertRedirect('/login');
    }

    public function test_colleagues_see_only_the_public_profile()
    {
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        $employee->assignRole('translator');

        $this->actingAs(User::factory()->create())
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/show')
                ->where('employee.surname', $employee->surname)
                ->where('employee.roles', ['Переводчик'])
                ->where('employee.private', null)
            );
    }

    public function test_employee_sees_their_full_profile_including_passport()
    {
        $user = User::factory()->has(UserDetail::factory(), 'details')->create();
        UserChild::factory(2)->for($user)->create();

        $this->actingAs($user)
            ->get("/employees/{$user->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('employee.private.passport.number', $user->details->passport_number)
                ->where('employee.private.passport.issued_by', $user->details->passport_issued_by)
                ->where('employee.private.birth_place', $user->details->birth_place)
                ->where('employee.private.sos_phone', $user->details->sos_phone)
                ->has('employee.private.children', 2)
            );
    }

    public function test_admin_sees_anyones_full_profile()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($admin)
            ->get("/employees/{$employee->id}")
            ->assertInertia(fn (Assert $page) => $page->where('employee.private.passport.number', $employee->details->passport_number));
    }

    public function test_unknown_employee_returns_404()
    {
        $this->actingAs(User::factory()->create())->get('/employees/999999')->assertNotFound();
    }
}
