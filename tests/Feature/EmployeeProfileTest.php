<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use App\Models\UserEducation;
use Database\Seeders\PositionSeeder;
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

        $this->seed([RoleSeeder::class, PositionSeeder::class]);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $employee = User::factory()->create();

        $this->get("/employees/{$employee->id}")->assertRedirect('/login');
    }

    public function test_colleagues_see_only_the_public_profile()
    {
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        $employee->positions()->attach(Position::firstWhere('name', 'Переводчик'));

        $this->actingAs(User::factory()->create())
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/show')
                ->where('employee.surname', $employee->surname)
                ->where('employee.positions', ['Переводчик'])
                ->where('employee.private', null)
            );
    }

    public function test_the_profile_links_to_the_previous_and_next_colleague_in_the_same_list()
    {
        $a = User::factory()->create(['surname' => 'Азимов', 'name' => 'Далер']);
        $b = User::factory()->create(['surname' => 'Азимов', 'name' => 'Фаррух']);
        $c = User::factory()->create(['surname' => 'Бобоева', 'name' => 'Нигина']);
        User::factory()->create(['surname' => 'Абдуллоев', 'name' => 'Умед', 'status' => 'fired']);

        $this->actingAs($a);

        $this->get("/employees/{$b->id}")->assertInertia(fn (Assert $page) => $page
            ->where('neighbours.prev', ['id' => $a->id, 'name' => 'Азимов Далер'])
            ->where('neighbours.next', ['id' => $c->id, 'name' => 'Бобоева Нигина'])
        );
        // First and last of the list; someone who left is not in it.
        $this->get("/employees/{$a->id}")->assertInertia(fn (Assert $page) => $page->where('neighbours.prev', null));
        $this->get("/employees/{$c->id}")->assertInertia(fn (Assert $page) => $page->where('neighbours.next', null));
    }

    public function test_employee_sees_their_full_profile_including_passport()
    {
        $user = User::factory()->has(UserDetail::factory(), 'details')->create();
        UserChild::factory(2)->for($user)->create();
        UserEducation::factory()->for($user)->create();

        $this->actingAs($user)
            ->get("/employees/{$user->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('employee.private.passport.number', $user->details->passport_number)
                ->where('employee.private.passport.issued_by', $user->details->passport_issued_by)
                ->where('employee.private.birth_place', $user->details->birth_place)
                ->where('employee.private.sos_phone', $user->details->sos_phone)
                ->has('employee.private.children', 2)
                ->has('employee.private.educations', 1)
                ->where('employee.private.educations.0.institution', $user->educations->first()->institution)
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
