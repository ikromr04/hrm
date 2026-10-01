<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use App\Support\EmployeeFields;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The "Семья" card of the profile: marital status, the spouse and the children.
 */
class EmployeeFamilyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PositionSeeder::class]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'marital_status' => 'married',
            'spouse_name' => 'Азимова Нигина',
            'spouse_birth_date' => '1992-03-08',
            'has_children' => null,
            'children' => [],
            ...$overrides,
        ];
    }

    /**
     * An empty list alone cannot say whether the employee has no children or
     * whether nobody has filled the card in yet, so a flag keeps them apart.
     */
    public function test_an_untouched_card_is_not_the_same_as_having_no_children()
    {
        $admin = User::factory()->create()->assignRole('sysadmin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        $employee->details()->update(['has_children' => null]);

        // Saved without an answer, the question stays open.
        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/family", $this->payload())
            ->assertSessionHasNoErrors();
        $this->assertNull($employee->refresh()->details->has_children);

        // Ticking "детей нет" answers it.
        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/family", $this->payload(['has_children' => false]))
            ->assertSessionHasNoErrors();
        $this->assertFalse($employee->refresh()->details->has_children);
    }

    public function test_rows_on_file_always_mean_there_are_children()
    {
        $admin = User::factory()->create()->assignRole('sysadmin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        // Even told otherwise, the rows win: the two cannot drift apart.
        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/family", $this->payload([
                'has_children' => false,
                'children' => [['full_name' => 'Азимов Далер', 'birth_date' => '2015-09-01']],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($employee->refresh()->details->has_children);
    }

    public function test_an_admin_saves_the_spouse_and_the_children()
    {
        $admin = User::factory()->create()->assignRole('sysadmin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/family", $this->payload([
                'children' => [
                    ['full_name' => 'Азимов Далер', 'birth_date' => '2015-09-01'],
                    // A child whose birthday nobody wrote down.
                    ['full_name' => 'Азимова Мадина', 'birth_date' => ''],
                ],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $employee->refresh();
        $this->assertSame('married', $employee->details->marital_status);
        $this->assertSame('Азимова Нигина', $employee->details->spouse_name);
        $this->assertSame('1992-03-08', $employee->details->spouse_birth_date->toDateString());
        // The relation orders by birth date, and a missing one sorts first.
        $this->assertSame(
            [['Азимова Мадина', null], ['Азимов Далер', '2015-09-01']],
            $employee->children->map(fn (UserChild $c) => [$c->full_name, $c->birth_date?->toDateString()])->all(),
        );
    }

    public function test_the_children_are_replaced_wholesale()
    {
        $admin = User::factory()->create()->assignRole('sysadmin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        UserChild::factory(3)->for($employee)->create();

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/family", $this->payload([
                'children' => [['full_name' => 'Азимов Далер', 'birth_date' => '2015-09-01']],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['Азимов Далер'], $employee->refresh()->children->pluck('full_name')->all());
    }

    public function test_an_unmarried_employee_may_leave_the_card_empty()
    {
        $admin = User::factory()->create()->assignRole('sysadmin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/family", [
                'marital_status' => '',
                'spouse_name' => '',
                'spouse_birth_date' => '',
                'children' => [],
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertNull($employee->details->marital_status);
        $this->assertNull($employee->details->spouse_name);
        $this->assertCount(0, $employee->children);
    }

    public function test_invalid_data_is_rejected()
    {
        $admin = User::factory()->create()->assignRole('sysadmin');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}/family", $this->payload([
                'marital_status' => 'divorced',
                // Nobody is born tomorrow.
                'spouse_birth_date' => now()->addDay()->toDateString(),
                'children' => [['full_name' => '', 'birth_date' => now()->addDay()->toDateString()]],
            ]))
            ->assertSessionHasErrors([
                'marital_status',
                'spouse_birth_date',
                'children.0.full_name',
                'children.0.birth_date',
            ]);
    }

    public function test_the_spouse_reaches_the_profile_page()
    {
        $employee = $this->mayLookAround(User::factory()->has(UserDetail::factory(), 'details')->create());
        $employee->details()->update(['marital_status' => 'married', 'spouse_name' => 'Азимова Нигина']);

        $this->actingAs($employee)
            ->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('employee.private.spouse_name', 'Азимова Нигина'));

        // A colleague gets no private block at all, spouse included.
        $this->actingAs($this->colleague())
            ->get("/employees/{$employee->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('employee.private', null));
    }

    public function test_an_employee_cannot_edit_anyones_family()
    {
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        // Not even their own: the card is managed by HR.
        $this->actingAs($employee)
            ->put("/employees/{$employee->id}/family", $this->payload())
            ->assertForbidden();

        $this->actingAs($this->colleague())
            ->put("/employees/{$employee->id}/family", $this->payload())
            ->assertForbidden();
    }

    /**
     * Somebody whose position holds exactly these lines of the "Семья" card, to
     * read and to change, plus the staff list. Nobody retypes what they cannot
     * see, so each line comes with its right to read it.
     */
    private function withFamilyRights(string $scope, string ...$fields): User
    {
        $rights = collect($fields)->flatMap(fn (string $field) => [
            EmployeeFields::permission($field, $scope),
            EmployeeFields::editPermission($field, $scope),
        ])->all();

        $role = Role::create(['name' => 'f-'.Role::count(), 'title' => 'Роль '.Role::count(), 'guard_name' => 'web']);
        $role->syncPermissions(['employees.view', ...$rights]);

        return User::factory()->has(UserDetail::factory(), 'details')->create()->assignRole($role);
    }

    /** A card with a whole family already on file. */
    private function withFamily(User $employee): User
    {
        $employee->details()->update([
            'marital_status' => 'married',
            'spouse_name' => 'Азимова Нигина',
            'spouse_birth_date' => '1992-03-08',
            'has_children' => true,
        ]);
        UserChild::factory()->for($employee)->create(['full_name' => 'Азимов Далер', 'birth_date' => '2015-09-01']);

        return $employee->refresh();
    }

    private function assertSpouseKept(User $employee): void
    {
        $this->assertSame('Азимова Нигина', $employee->details->spouse_name);
        $this->assertSame('1992-03-08', $employee->details->spouse_birth_date->toDateString());
    }

    private function assertChildrenKept(User $employee): void
    {
        $this->assertTrue($employee->details->has_children);
        $this->assertSame(['Азимов Далер'], $employee->children->pluck('full_name')->all());
    }

    /**
     * The block opens for whoever may change any line of it, so a form that only
     * offered the marital status must not wipe the spouse and the children with
     * blanks it was never meant to send.
     */
    public function test_whoever_may_change_only_the_marital_status_leaves_the_rest_alone()
    {
        $clerk = $this->withFamilyRights(EmployeeFields::OTHERS, 'marital_status');
        $employee = $this->withFamily(User::factory()->has(UserDetail::factory(), 'details')->create());

        $this->actingAs($clerk)
            ->put("/employees/{$employee->id}/family", [
                'marital_status' => 'single',
                'spouse_name' => '',
                'spouse_birth_date' => '',
                'has_children' => false,
                'children' => [],
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame('single', $employee->details->marital_status);
        $this->assertSpouseKept($employee);
        $this->assertChildrenKept($employee);

        // Nor is a line they may not change checked: there is nothing for them to fix in it.
        $this->actingAs($clerk)
            ->put("/employees/{$employee->id}/family", ['marital_status' => 'married', 'spouse_birth_date' => 'не дата'])
            ->assertSessionHasNoErrors();
        $this->assertSame('married', $employee->refresh()->details->marital_status);
    }

    public function test_whoever_may_change_only_the_children_replaces_just_them()
    {
        $clerk = $this->withFamilyRights(EmployeeFields::OTHERS, 'children');
        $employee = $this->withFamily(User::factory()->has(UserDetail::factory(), 'details')->create());

        $this->actingAs($clerk)
            ->put("/employees/{$employee->id}/family", [
                'marital_status' => '',
                'spouse_name' => '',
                'children' => [['full_name' => 'Азимова Мадина', 'birth_date' => '2018-05-12']],
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame(['Азимова Мадина'], $employee->children->pluck('full_name')->all());
        $this->assertSame('married', $employee->details->marital_status);
        $this->assertSpouseKept($employee);

        // "Детей нет" is part of the children's line, and so theirs to tick.
        $this->actingAs($clerk)
            ->put("/employees/{$employee->id}/family", ['has_children' => false, 'children' => []])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertFalse($employee->details->has_children);
        $this->assertCount(0, $employee->children);
        $this->assertSame('married', $employee->details->marital_status);
    }

    public function test_whoever_may_change_every_line_saves_the_whole_card()
    {
        $clerk = $this->withFamilyRights(EmployeeFields::OTHERS, 'marital_status', 'spouse', 'children');
        $employee = $this->withFamily(User::factory()->has(UserDetail::factory(), 'details')->create());

        $this->actingAs($clerk)
            ->put("/employees/{$employee->id}/family", [
                'marital_status' => '',
                'spouse_name' => '',
                'spouse_birth_date' => '',
                'has_children' => null,
                'children' => [],
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertNull($employee->details->marital_status);
        $this->assertNull($employee->details->spouse_name);
        $this->assertNull($employee->details->spouse_birth_date);
        $this->assertNull($employee->details->has_children);
        $this->assertCount(0, $employee->children);
    }

    public function test_whoever_may_change_no_line_of_the_family_is_turned_away()
    {
        // Reading all of it is not changing any of it.
        $reader = $this->withFamilyRights(EmployeeFields::OTHERS);
        $reader->roles->first()->givePermissionTo(array_map(
            fn (string $field) => EmployeeFields::permission($field),
            ['marital_status', 'spouse', 'children'],
        ));
        $employee = $this->withFamily(User::factory()->has(UserDetail::factory(), 'details')->create());

        $this->actingAs($reader)
            ->put("/employees/{$employee->id}/family", $this->payload())
            ->assertForbidden();

        $employee->refresh();
        $this->assertSpouseKept($employee);
        $this->assertChildrenKept($employee);
    }

    /**
     * One's own card is read against the profile rights, line by line all the
     * same, and those rights open nobody else's.
     */
    public function test_ones_own_family_is_saved_line_by_line_by_the_profile_rights()
    {
        $employee = $this->withFamily($this->withFamilyRights(EmployeeFields::OWN, 'marital_status'));

        $this->actingAs($employee)
            ->put("/employees/{$employee->id}/family", [
                'marital_status' => 'single',
                'spouse_name' => '',
                'spouse_birth_date' => '',
                'children' => [],
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame('single', $employee->details->marital_status);
        $this->assertSpouseKept($employee);
        $this->assertChildrenKept($employee);

        $parent = $this->withFamily($this->withFamilyRights(EmployeeFields::OWN, 'children'));

        $this->actingAs($parent)
            ->put("/employees/{$parent->id}/family", [
                'marital_status' => '',
                'children' => [['full_name' => 'Азимова Мадина', 'birth_date' => '']],
            ])
            ->assertSessionHasNoErrors();

        $parent->refresh();
        $this->assertSame(['Азимова Мадина'], $parent->children->pluck('full_name')->all());
        $this->assertSame('married', $parent->details->marital_status);
        $this->assertSpouseKept($parent);

        $this->actingAs($parent)
            ->put("/employees/{$employee->id}/family", $this->payload())
            ->assertForbidden();
    }
}
