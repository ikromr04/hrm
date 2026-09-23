<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserEquipment;
use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PositionSeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    /**
     * The edit form posts every section at once, so the other lists have to be
     * present even when only equipment is under test.
     *
     * @param  array<int, array<string, string>>  $equipment
     * @return array<string, mixed>
     */
    private function payload(User $employee, array $equipment): array
    {
        return [
            'surname' => $employee->surname,
            'name' => $employee->name,
            'patronymic' => $employee->patronymic ?? '',
            'sex' => $employee->sex,
            'email' => $employee->email,
            'roles' => [],
            'positions' => [],
            'departments' => [],
            'languages' => [],
            'children' => [],
            'educations' => [],
            'work_experiences' => [],
            'equipment' => $equipment,
        ];
    }

    public function test_only_admins_manage_the_equipment_directory()
    {
        $this->actingAs(User::factory()->create())->get('/directories/equipment')->assertForbidden();
        $this->actingAs($this->admin())->get('/directories/equipment')->assertOk();
    }

    public function test_an_admin_adds_renames_and_removes_a_kind_of_equipment()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/directories/equipment', ['name' => 'Монитор'])->assertSessionHasNoErrors();
        $type = EquipmentType::firstWhere('name', 'Монитор');

        // The same name twice would make the two indistinguishable in the picker.
        $this->actingAs($admin)->post('/directories/equipment', ['name' => 'Монитор'])->assertSessionHasErrors('name');

        $this->actingAs($admin)->put("/directories/equipment/{$type->id}", ['name' => 'Монитор 24"'])->assertSessionHasNoErrors();
        $this->assertSame('Монитор 24"', $type->fresh()->name);

        $this->actingAs($admin)->delete("/directories/equipment/{$type->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('equipment_types', ['id' => $type->id]);
    }

    public function test_the_directory_counts_each_holder_once()
    {
        $this->seed(EquipmentTypeSeeder::class);
        $monitor = EquipmentType::firstWhere('name', 'Монитор');
        $employee = User::factory()->create();

        // Two monitors, one employee.
        UserEquipment::factory(2)->for($employee)->ofType($monitor)->create();
        UserEquipment::factory()->for(User::factory()->create(['status' => 'fired']))->ofType($monitor)->create();

        $this->actingAs($this->admin())
            ->get('/directories/equipment')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('items', fn ($items) => collect($items)->firstWhere('name', 'Монитор')['users_count'] === 1)
            );
    }

    public function test_an_admin_saves_several_units_for_one_employee()
    {
        $this->seed(EquipmentTypeSeeder::class);
        $laptop = EquipmentType::firstWhere('name', 'Ноутбук');
        $monitor = EquipmentType::firstWhere('name', 'Монитор');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}", $this->payload($employee, [
                ['equipment_type_id' => (string) $laptop->id, 'description' => 'HP ProBook 450 G9', 'inventory_number' => 'ИНВ-000451'],
                // Two of the same kind, and a unit with no description.
                ['equipment_type_id' => (string) $monitor->id, 'description' => 'Dell P2422H, 24"', 'inventory_number' => 'ИНВ-000452'],
                ['equipment_type_id' => (string) $monitor->id, 'description' => '', 'inventory_number' => 'ИНВ-000453'],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/employees/{$employee->id}");

        $this->assertSame(
            [
                [$laptop->id, 'HP ProBook 450 G9', 'ИНВ-000451'],
                [$monitor->id, 'Dell P2422H, 24"', 'ИНВ-000452'],
                [$monitor->id, null, 'ИНВ-000453'],
            ],
            $employee->refresh()->equipment->map(fn (UserEquipment $e) => [$e->equipment_type_id, $e->description, $e->inventory_number])->all(),
        );
    }

    public function test_an_inventory_number_cannot_be_held_by_two_people()
    {
        $this->seed(EquipmentTypeSeeder::class);
        $laptop = EquipmentType::firstWhere('name', 'Ноутбук');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        UserEquipment::factory()->for(User::factory()->create())->ofType($laptop)->create(['inventory_number' => 'ИНВ-000451']);

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}", $this->payload($employee, [
                ['equipment_type_id' => (string) $laptop->id, 'description' => '', 'inventory_number' => 'ИНВ-000451'],
            ]))
            ->assertSessionHasErrors('equipment.0.inventory_number');
    }

    public function test_the_same_number_cannot_repeat_within_one_form()
    {
        $this->seed(EquipmentTypeSeeder::class);
        $laptop = EquipmentType::firstWhere('name', 'Ноутбук');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}", $this->payload($employee, [
                ['equipment_type_id' => (string) $laptop->id, 'description' => '', 'inventory_number' => 'ИНВ-000451'],
                ['equipment_type_id' => (string) $laptop->id, 'description' => '', 'inventory_number' => 'ИНВ-000451'],
                // No kind chosen, and no number typed.
                ['equipment_type_id' => '', 'description' => 'Просто текст', 'inventory_number' => ''],
            ]))
            ->assertSessionHasErrors([
                'equipment.1.inventory_number',
                'equipment.2.equipment_type_id',
                'equipment.2.inventory_number',
            ]);
    }

    public function test_an_employee_keeps_their_numbers_when_saved_again()
    {
        $this->seed(EquipmentTypeSeeder::class);
        $laptop = EquipmentType::firstWhere('name', 'Ноутбук');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        UserEquipment::factory()->for($employee)->ofType($laptop)->create(['inventory_number' => 'ИНВ-000451']);

        // The rows are replaced on every save, so the employee's own number
        // must not count as taken by someone else.
        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}", $this->payload($employee, [
                ['equipment_type_id' => (string) $laptop->id, 'description' => 'Заменили диск', 'inventory_number' => 'ИНВ-000451'],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Заменили диск', $employee->refresh()->equipment->first()->description);
    }

    public function test_equipment_is_private_and_shown_on_the_profile()
    {
        $this->seed(EquipmentTypeSeeder::class);
        $monitor = EquipmentType::firstWhere('name', 'Монитор');
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        UserEquipment::factory()->for($employee)->ofType($monitor)->create(['inventory_number' => 'ИНВ-000452']);

        $this->actingAs($employee)
            ->get("/employees/{$employee->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('employee.private.equipment', 1)
                ->where('employee.private.equipment.0.type', 'Монитор')
                ->where('employee.private.equipment.0.inventory_number', 'ИНВ-000452')
            );

        // A colleague sees no private block at all, equipment included.
        $this->actingAs(User::factory()->create())
            ->get("/employees/{$employee->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('employee.private', null));
    }

    public function test_deleting_a_kind_takes_its_units_with_it()
    {
        $this->seed(EquipmentTypeSeeder::class);
        $monitor = EquipmentType::firstWhere('name', 'Монитор');
        $unit = UserEquipment::factory()->for(User::factory()->create())->ofType($monitor)->create();

        $this->actingAs($this->admin())->delete("/directories/equipment/{$monitor->id}")->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('user_equipment', ['id' => $unit->id]);
    }
}
