<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\UserDetail;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeEditTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(User $employee, array $overrides = []): array
    {
        return [
            'surname' => $employee->surname,
            'name' => $employee->name,
            'patronymic' => $employee->patronymic,
            'sex' => $employee->sex,
            'email' => $employee->email,
            'roles' => $employee->roles->pluck('name')->all(),
            'positions' => [],
            'departments' => [],
            'languages' => [],
            'children' => [],
            ...$overrides,
        ];
    }

    public function test_employees_cannot_edit_colleagues_or_themselves()
    {
        $employee = User::factory()->create();
        $this->actingAs($employee);

        $this->get("/employees/{$employee->id}/edit")->assertForbidden();
        $this->put("/employees/{$employee->id}", $this->payload($employee, ['surname' => 'Другой']))->assertForbidden();
        $this->assertNotSame('Другой', $employee->fresh()->surname);
    }

    public function test_the_edit_page_shows_every_field()
    {
        $employee = User::factory()->create(['surname' => 'Азимова', 'sex' => 'female']);
        UserDetail::factory()->for($employee)->create(['phone' => '+992901234567', 'hired_at' => '2020-05-01']);
        $employee->children()->create(['full_name' => 'Азимов Далер', 'birth_date' => '2015-02-03']);
        $department = Department::create(['name' => 'Отдел']);
        $employee->departments()->attach($department, ['is_head' => true]);
        $employee->assignRole('specialist');

        $this->actingAs($this->admin)->get("/employees/{$employee->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->component('employees/edit')
            ->where('employee.surname', 'Азимова')
            ->where('employee.roles', ['specialist'])
            ->where('employee.departments', [$department->id])
            ->where('employee.head_of', [$department->id])
            ->where('employee.phone', '+992901234567')
            ->where('employee.hired_at', '2020-05-01')
            ->where('employee.children', [['full_name' => 'Азимов Далер', 'birth_date' => '2015-02-03']])
            ->has('options.roles')
            ->has('options.departments', 1)
        );
    }

    public function test_an_admin_updates_public_and_private_data()
    {
        $employee = User::factory()->create();
        $employee->assignRole('specialist');
        $employee->children()->create(['full_name' => 'Старый ребёнок']);
        $position = Position::create(['name' => 'Дизайнер']);
        $keep = Department::create(['name' => 'Остаётся']);
        $leave = Department::create(['name' => 'Уходит']);
        $join = Department::create(['name' => 'Новый']);
        $employee->departments()->attach([$keep->id => ['is_head' => true], $leave->id => ['is_head' => true]]);

        $this->actingAs($this->admin)
            ->put("/employees/{$employee->id}", $this->payload($employee, [
                'surname' => 'Каримов',
                'patronymic' => '',
                'email' => 'karimov@evolet.test',
                'roles' => ['analyst', 'specialist'],
                'positions' => [$position->id],
                'departments' => [$keep->id, $join->id],
                'hired_at' => '2021-03-12',
                'marital_status' => 'married',
                'phone' => '90 123 45 67',
                'sos_phone' => '+992 (93) 555-44-33',
                'passport_number' => '01234567',
                'children' => [['full_name' => 'Каримов Далер', 'birth_date' => '2018-06-01']],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/employees/{$employee->id}");

        $employee->refresh()->load('details');
        $this->assertSame('Каримов', $employee->surname);
        $this->assertNull($employee->patronymic);
        $this->assertSame('karimov@evolet.test', $employee->email);
        $this->assertEqualsCanonicalizing(['analyst', 'specialist'], $employee->getRoleNames()->all());
        $this->assertSame([$position->id], $employee->positions->pluck('id')->all());
        $this->assertSame('2021-03-12', $employee->details->hired_at->toDateString());
        $this->assertSame('married', $employee->details->marital_status);
        $this->assertSame('+992901234567', $employee->details->phone);
        $this->assertSame('+992935554433', $employee->details->sos_phone);
        $this->assertSame(['Каримов Далер'], $employee->children()->pluck('full_name')->all());

        // Staying keeps the head flag; a new department starts without it.
        $this->assertEqualsCanonicalizing([$keep->id, $join->id], $employee->departments->pluck('id')->all());
        $this->assertSame([$keep->id], $employee->departments->filter(fn ($d) => $d->pivot->is_head)->pluck('id')->values()->all());
    }

    public function test_invalid_data_is_rejected()
    {
        $employee = User::factory()->create();
        $taken = User::factory()->create();

        $this->actingAs($this->admin)
            ->put("/employees/{$employee->id}", $this->payload($employee, [
                'surname' => '',
                'email' => $taken->email,
                'roles' => ['no-such-role'],
                'phone' => '12',
                'birth_date' => now()->addDay()->toDateString(),
                'children' => [['full_name' => '']],
            ]))
            ->assertSessionHasErrors(['surname', 'email', 'roles.0', 'phone', 'birth_date', 'children.0.full_name']);
    }

    public function test_an_admin_cannot_drop_their_own_admin_role()
    {
        $this->actingAs($this->admin)
            ->put("/employees/{$this->admin->id}", $this->payload($this->admin, ['roles' => ['specialist']]))
            ->assertSessionHasErrors('roles');

        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }
}
