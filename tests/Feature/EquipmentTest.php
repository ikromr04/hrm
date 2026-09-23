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
