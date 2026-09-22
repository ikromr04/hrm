<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_employees_cannot_act_on_colleagues_or_see_who_left()
    {
        $colleague = User::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->post("/employees/{$colleague->id}/fire", ['date' => '2026-09-01'])->assertForbidden();
        $this->post("/employees/{$colleague->id}/transfer", ['date' => '2026-09-01', 'note' => 'X'])->assertForbidden();
        $this->delete("/employees/{$colleague->id}")->assertForbidden();
        $this->assertTrue($colleague->fresh()->isActive());

        $this->get('/employees?status=fired')->assertSessionHasErrors('status');
        $this->get('/employees')->assertInertia(fn (Assert $page) => $page
            ->where('statusCounts', null)
            ->where('auth.can.manageEmployees', false)
        );
    }

    public function test_transfer_needs_a_destination_and_moves_the_person_to_their_list()
    {
        $employee = User::factory()->create(['surname' => 'Азимов']);
        $this->actingAs($this->admin);

        $this->post("/employees/{$employee->id}/transfer", ['date' => '2026-09-01'])->assertSessionHasErrors('note');

        $this->post("/employees/{$employee->id}/transfer", ['date' => '2026-09-01', 'note' => 'Эволет Европа'])->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertSame('transferred', $employee->status);
        $this->assertSame('2026-09-01', $employee->status_changed_at->toDateString());
        $this->assertSame('Эволет Европа', $employee->status_note);

        $this->get('/employees')->assertInertia(fn (Assert $page) => $page
            ->where('employees.data', fn ($rows) => collect($rows)->doesntContain('id', $employee->id))
            ->where('statusCounts', ['active' => 1, 'transferred' => 1, 'fired' => 0])
        );
        $this->get('/employees?status=transferred')->assertInertia(fn (Assert $page) => $page
            ->where('status', 'transferred')
            ->has('employees.data', 1)
            ->where('employees.data.0.status_note', 'Эволет Европа')
        );
    }

    public function test_firing_takes_an_optional_reason_and_can_be_undone()
    {
        $employee = User::factory()->create();
        $this->actingAs($this->admin);

        $this->post("/employees/{$employee->id}/fire", ['date' => '2026-08-15'])->assertSessionHasNoErrors();
        $this->assertSame('fired', $employee->fresh()->status);
        $this->assertNull($employee->fresh()->status_note);

        // Already gone: cannot be fired again.
        $this->post("/employees/{$employee->id}/fire", ['date' => '2026-08-16'])->assertStatus(422);

        $this->post("/employees/{$employee->id}/restore")->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertTrue($employee->isActive());
        $this->assertNull($employee->status_changed_at);
    }

    public function test_deleting_removes_the_employee_and_their_private_data()
    {
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        $this->actingAs($this->admin);

        $this->delete("/employees/{$employee->id}")->assertSessionHasNoErrors();

        $this->assertNull(User::find($employee->id));
        $this->assertSame(0, UserDetail::where('user_id', $employee->id)->count());
    }

    public function test_admins_cannot_fire_transfer_or_delete_themselves()
    {
        $this->actingAs($this->admin);

        $this->post("/employees/{$this->admin->id}/fire", ['date' => '2026-09-01'])->assertForbidden();
        $this->post("/employees/{$this->admin->id}/transfer", ['date' => '2026-09-01', 'note' => 'X'])->assertForbidden();
        $this->delete("/employees/{$this->admin->id}")->assertForbidden();
        $this->assertTrue($this->admin->fresh()->isActive());
    }

    public function test_people_who_left_cannot_sign_in()
    {
        User::factory()->create(['email' => 'gone@evolet.test', 'status' => 'fired']);

        $this->post('/login', ['email' => 'gone@evolet.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Учётная запись отключена. Обратитесь в HR-отдел.']);

        $this->assertGuest();
    }

    public function test_someone_fired_while_signed_in_is_signed_out()
    {
        $employee = User::factory()->create();
        $this->actingAs($employee)->get('/dashboard')->assertOk();

        $employee->update(['status' => 'fired']);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
