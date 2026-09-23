<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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
        $admin = User::factory()->create()->assignRole('admin');
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
        $admin = User::factory()->create()->assignRole('admin');
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
        $admin = User::factory()->create()->assignRole('admin');
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
        $admin = User::factory()->create()->assignRole('admin');
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
        $admin = User::factory()->create()->assignRole('admin');
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
        $admin = User::factory()->create()->assignRole('admin');
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
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        $employee->details()->update(['marital_status' => 'married', 'spouse_name' => 'Азимова Нигина']);

        $this->actingAs($employee)
            ->get("/employees/{$employee->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('employee.private.spouse_name', 'Азимова Нигина'));

        // A colleague gets no private block at all, spouse included.
        $this->actingAs(User::factory()->create())
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

        $this->actingAs(User::factory()->create())
            ->put("/employees/{$employee->id}/family", $this->payload())
            ->assertForbidden();
    }
}
