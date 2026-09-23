<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hire date, shown bare at the top of the profile sidebar.
 */
class EmployeeEmploymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PositionSeeder::class]);
    }

    public function test_an_admin_sets_the_hire_date()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/employment", ['hired_at' => '2019-03-14'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('2019-03-14', $employee->refresh()->details->hired_at->toDateString());
    }

    public function test_the_details_row_is_created_when_the_employee_has_none()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $employee = User::factory()->create();

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/employment", ['hired_at' => '2019-03-14'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2019-03-14', $employee->refresh()->details->hired_at->toDateString());
    }

    public function test_the_date_may_be_cleared()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/employment", ['hired_at' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($employee->refresh()->details->hired_at);
    }

    public function test_a_date_in_the_future_is_rejected()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        // Tenure counts from this date, so a future one would read as negative service.
        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/employment", ['hired_at' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('hired_at');
    }

    public function test_an_employee_cannot_set_anyones_hire_date()
    {
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        // Not even their own: the date is managed by HR.
        $this->actingAs($employee)
            ->put("/employees/{$employee->id}/employment", ['hired_at' => '2019-03-14'])
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->put("/employees/{$employee->id}/employment", ['hired_at' => '2019-03-14'])
            ->assertForbidden();
    }
}
