<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\User;
use App\Models\UserEquipment;
use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hardware is kept unit by unit: handing out a monitor must not rewrite the
 * rest of the person's kit.
 */
class EmployeeEquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PositionSeeder::class, EquipmentTypeSeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function type(string $name = 'Монитор'): EquipmentType
    {
        return EquipmentType::firstWhere('name', $name);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'equipment_type_id' => $this->type()->id,
            'description' => 'Dell P2422H, 24"',
            'inventory_number' => 'ИНВ-000451',
            ...$overrides,
        ];
    }

    public function test_an_admin_hands_out_a_unit_without_touching_the_others()
    {
        $employee = User::factory()->create();
        $kept = UserEquipment::factory()->for($employee)->ofType($this->type('Ноутбук'))->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/equipment", $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertCount(2, $employee->refresh()->equipment);
        $this->assertNotNull($kept->fresh());
    }

    public function test_an_admin_changes_one_unit()
    {
        $employee = User::factory()->create();
        $unit = UserEquipment::factory()->for($employee)->ofType($this->type())->create();

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}/equipment/{$unit->id}", $this->payload(['description' => 'Заменили матрицу']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Заменили матрицу', $unit->fresh()->description);
    }

    public function test_an_admin_takes_one_unit_back()
    {
        $employee = User::factory()->create();
        $unit = UserEquipment::factory()->for($employee)->ofType($this->type())->create();
        $kept = UserEquipment::factory()->for($employee)->ofType($this->type('Мышь'))->create();

        $this->actingAs($this->admin())
            ->delete("/employees/{$employee->id}/equipment/{$unit->id}")
            ->assertSessionHasNoErrors();

        $this->assertNull($unit->fresh());
        $this->assertNotNull($kept->fresh());
    }

    public function test_an_inventory_number_cannot_be_held_by_two_people()
    {
        $employee = User::factory()->create();
        UserEquipment::factory()->for(User::factory()->create())->ofType($this->type())->create(['inventory_number' => 'ИНВ-000451']);

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/equipment", $this->payload())
            ->assertSessionHasErrors('inventory_number');
    }

    public function test_a_unit_keeps_its_own_number_when_edited()
    {
        $employee = User::factory()->create();
        $unit = UserEquipment::factory()->for($employee)->ofType($this->type())->create(['inventory_number' => 'ИНВ-000451']);

        // Its own number is not a clash with itself.
        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}/equipment/{$unit->id}", $this->payload(['description' => 'Протёрли']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Протёрли', $unit->fresh()->description);
    }

    public function test_invalid_data_is_rejected()
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/equipment", $this->payload([
                'equipment_type_id' => '',
                'inventory_number' => '',
            ]))
            ->assertSessionHasErrors(['equipment_type_id', 'inventory_number']);
    }

    public function test_a_unit_cannot_be_reached_through_another_employee()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $unit = UserEquipment::factory()->for($owner)->ofType($this->type())->create(['description' => 'Свой']);

        // The id is real, but it belongs to somebody else.
        $this->actingAs($this->admin())
            ->put("/employees/{$stranger->id}/equipment/{$unit->id}", $this->payload())
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->delete("/employees/{$stranger->id}/equipment/{$unit->id}")
            ->assertNotFound();

        $this->assertSame('Свой', $unit->fresh()->description);
    }

    public function test_an_employee_cannot_touch_anyones_equipment()
    {
        $employee = User::factory()->create();
        $unit = UserEquipment::factory()->for($employee)->ofType($this->type())->create();

        // Not even their own: the block is managed by HR.
        $this->actingAs($employee);
        $this->post("/employees/{$employee->id}/equipment", $this->payload())->assertForbidden();
        $this->put("/employees/{$employee->id}/equipment/{$unit->id}", $this->payload())->assertForbidden();
        $this->delete("/employees/{$employee->id}/equipment/{$unit->id}")->assertForbidden();
    }
}
