<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use App\Models\UserDetail;
use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The equipment section: the whole fleet, the state each unit is in and who
 * holds it. A unit belongs to the company, not to whoever happens to have it.
 */
class EquipmentTest extends TestCase
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

    private function type(string $name = 'Ноутбуки'): EquipmentType
    {
        return EquipmentType::firstWhere('name', $name);
    }

    public function test_the_list_is_open_to_every_signed_in_colleague()
    {
        $this->get('/equipment')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/equipment')->assertOk();
    }

    public function test_the_tiles_count_the_fleet_and_the_share_handed_out()
    {
        $holder = User::factory()->create();
        Equipment::factory(3)->ofType($this->type())->issuedTo($holder->id)->create();
        Equipment::factory(1)->ofType($this->type())->create();
        Equipment::factory(2)->ofType($this->type())->writtenOff()->create();

        $this->actingAs($this->admin())
            ->get('/equipment')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('summary.total', 6)
                ->where('summary.issued', 3)
                ->where('summary.stock', 1)
                // Written-off units have left the fleet, so the share is 3 of 4.
                ->where('summary.issued_share', 75)
            );
    }

    public function test_the_tabs_count_every_status()
    {
        $holder = User::factory()->create();
        Equipment::factory(2)->ofType($this->type())->issuedTo($holder->id)->create();
        Equipment::factory(1)->ofType($this->type())->inRepair()->create();

        $this->actingAs($this->admin())
            ->get('/equipment')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('counts.all', 3)
                ->where('counts.issued', 2)
                ->where('counts.repair', 1)
                ->where('counts.written_off', 0)
            );
    }

    public function test_the_tab_above_the_table_picks_one_status()
    {
        $holder = User::factory()->create();
        Equipment::factory()->ofType($this->type())->issuedTo($holder->id)->create(['name' => 'Ноутбук A']);
        Equipment::factory()->ofType($this->type())->create(['name' => 'Ноутбук C']);

        $this->actingAs($this->admin())->get('/equipment?tab=stock')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 1)->where('equipment.data.0.name', 'Ноутбук C'));
    }

    public function test_every_column_has_a_filter_of_its_own()
    {
        $holder = User::factory()->create(['surname' => 'Абдуллаев']);
        $other = User::factory()->create(['surname' => 'Зокиров']);
        Equipment::factory()->ofType($this->type())->issuedTo($holder->id)
            ->create(['name' => 'Ноутбук A', 'inventory_number' => 'EV-0001', 'issued_at' => '2026-03-14']);
        Equipment::factory()->ofType($this->type('Мониторы'))->issuedTo($other->id)
            ->create(['name' => 'Монитор B', 'inventory_number' => 'EV-0002', 'issued_at' => '2026-01-05']);
        Equipment::factory()->ofType($this->type())->create(['name' => 'Ноутбук C', 'inventory_number' => 'EV-0003']);

        $admin = $this->admin();

        $only = fn (string $query, string $name) => $this->actingAs($admin)->get("/equipment?{$query}")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 1)->where('equipment.data.0.name', $name));

        $only('name=Монитор', 'Монитор B');
        $only('inventory_number=EV-0001', 'Ноутбук A');
        $only('type[]='.$this->type('Мониторы')->id, 'Монитор B');
        $only('status[]=stock', 'Ноутбук C');
        // "У кого" is typed in, not picked from a list, and a full name works
        // too: that is the link the employee's profile opens.
        $only('holder='.urlencode('Абдулла'), 'Ноутбук A');
        $only('holder='.urlencode("Абдуллаев {$holder->name}"), 'Ноутбук A');
        $only('issued_from=2026-02-01', 'Ноутбук A');
        $only('issued_to=2026-02-01', 'Монитор B');
    }

    public function test_the_holder_filter_also_finds_a_department()
    {
        $department = Department::create(['name' => 'Отдел бухгалтерии']);
        Equipment::factory()->ofType($this->type())->create(['name' => 'Принтер', 'status' => 'issued', 'holder_department_id' => $department->id]);
        Equipment::factory()->ofType($this->type())->create(['name' => 'Ноутбук']);

        $this->actingAs($this->admin())->get('/equipment?holder='.urlencode('бухгалтер'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('equipment.data', 1)->where('equipment.data.0.name', 'Принтер'));
    }

    public function test_the_list_sorts_by_a_column_in_either_direction()
    {
        Equipment::factory()->ofType($this->type())->create(['name' => 'Б', 'inventory_number' => 'EV-0002']);
        Equipment::factory()->ofType($this->type())->create(['name' => 'А', 'inventory_number' => 'EV-0001']);

        $admin = $this->admin();

        // The default order, and the same column turned around.
        $this->actingAs($admin)->get('/equipment')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('equipment.data.0.name', 'А'));

        $this->actingAs($admin)->get('/equipment?sort=name&direction=desc')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('equipment.data.0.name', 'Б'));

        // A column the table does not offer is refused rather than ignored.
        $this->actingAs($admin)->get('/equipment?sort=serial_number')->assertSessionHasErrors('sort');
    }

    public function test_search_covers_the_name_and_both_numbers()
    {
        Equipment::factory()->ofType($this->type())->create([
            'name' => 'Ноутбук Dell Latitude 5440',
            'inventory_number' => 'EV-0421',
            'serial_number' => '7K2L9P3',
        ]);
        Equipment::factory()->ofType($this->type())->create(['name' => 'Монитор', 'inventory_number' => 'EV-9999', 'serial_number' => 'ZZZ']);

        $admin = $this->admin();

        foreach (['Latitude', 'EV-0421', '7K2L9P3'] as $term) {
            $this->actingAs($admin)->get('/equipment?q='.urlencode($term))
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->has('equipment.data', 1)
                    ->where('equipment.data.0.inventory_number', 'EV-0421')
                );
        }
    }

    public function test_an_admin_puts_a_new_unit_on_the_books()
    {
        $this->actingAs($this->admin())
            ->post('/equipment', [
                'equipment_type_id' => $this->type()->id,
                'name' => 'Ноутбук Dell Latitude 5440',
                'maker' => 'Dell',
                'serial_number' => '7K2L9P3',
                'inventory_number' => 'EV-0421',
            ])
            ->assertSessionHasNoErrors();

        $unit = Equipment::firstWhere('inventory_number', 'EV-0421');
        $this->assertSame('Ноутбук Dell Latitude 5440', $unit->name);
        // Nobody holds it yet: it is in stock until it is handed out.
        $this->assertSame('stock', $unit->status);
        $this->assertNull($unit->holder_user_id);
        $this->assertNull($unit->issued_at);
    }

    public function test_a_new_unit_needs_a_name_a_category_and_a_free_inventory_number()
    {
        Equipment::factory()->ofType($this->type())->create(['inventory_number' => 'EV-0421']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/equipment', [])
            ->assertSessionHasErrors(['equipment_type_id', 'name', 'inventory_number']);

        $this->actingAs($admin)->post('/equipment', [
            'equipment_type_id' => $this->type()->id,
            'name' => 'Второй ноутбук',
            'inventory_number' => 'EV-0421',
        ])->assertSessionHasErrors('inventory_number');

        $this->assertSame(1, Equipment::count());
    }

    public function test_only_managers_add_equipment()
    {
        $this->actingAs(User::factory()->create())
            ->post('/equipment', [
                'equipment_type_id' => $this->type()->id,
                'name' => 'Ноутбук',
                'inventory_number' => 'EV-0001',
            ])
            ->assertForbidden();

        $this->assertSame(0, Equipment::count());
    }

    public function test_an_admin_hands_a_unit_to_an_employee()
    {
        $employee = User::factory()->create();
        $unit = Equipment::factory()->ofType($this->type())->create();

        $this->actingAs($this->admin())
            ->post("/equipment/{$unit->id}/issue", ['holder_user_id' => $employee->id, 'issued_at' => '2026-03-14'])
            ->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame('issued', $unit->status);
        $this->assertSame($employee->id, $unit->holder_user_id);
        $this->assertSame('2026-03-14', $unit->issued_at->toDateString());
    }

    public function test_a_unit_goes_to_one_holder_not_two()
    {
        $employee = User::factory()->create();
        $department = Department::create(['name' => 'Отдел бухгалтерии']);
        $unit = Equipment::factory()->ofType($this->type())->create();
        $admin = $this->admin();

        // A department may hold a unit, as the design shows...
        $this->actingAs($admin)
            ->post("/equipment/{$unit->id}/issue", ['holder_department_id' => $department->id, 'issued_at' => '2026-03-14'])
            ->assertSessionHasNoErrors();
        $this->assertSame($department->id, $unit->refresh()->holder_department_id);

        // ...but never a person and a department at once.
        $this->actingAs($admin)
            ->post("/equipment/{$unit->id}/issue", [
                'holder_user_id' => $employee->id,
                'holder_department_id' => $department->id,
                'issued_at' => '2026-03-14',
            ])
            ->assertSessionHasErrors('holder_user_id');
    }

    public function test_taking_a_unit_back_clears_who_had_it()
    {
        $employee = User::factory()->create();
        $unit = Equipment::factory()->ofType($this->type())->issuedTo($employee->id)->create();

        $this->actingAs($this->admin())
            ->post("/equipment/{$unit->id}/take")
            ->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame('stock', $unit->status);
        $this->assertNull($unit->holder_user_id);
        $this->assertNull($unit->issued_at);
    }

    public function test_a_written_off_unit_cannot_be_moved_again()
    {
        $employee = User::factory()->create();
        $unit = Equipment::factory()->ofType($this->type())->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/equipment/{$unit->id}/write-off", ['written_off_at' => '2026-02-02'])
            ->assertSessionHasNoErrors();
        $this->assertSame('written_off', $unit->refresh()->status);

        // Out of the fleet for good: no issuing, returning or repairing it.
        $this->actingAs($admin)
            ->post("/equipment/{$unit->id}/issue", ['holder_user_id' => $employee->id, 'issued_at' => '2026-03-14'])
            ->assertStatus(422);
        $this->assertSame('written_off', $unit->refresh()->status);
    }

    public function test_only_managers_move_equipment()
    {
        $employee = User::factory()->create();
        $unit = Equipment::factory()->ofType($this->type())->create();

        $this->actingAs($employee);
        $this->post("/equipment/{$unit->id}/issue", ['holder_user_id' => $employee->id, 'issued_at' => '2026-03-14'])->assertForbidden();
        $this->post("/equipment/{$unit->id}/take")->assertForbidden();
        $this->post("/equipment/{$unit->id}/write-off", ['written_off_at' => '2026-02-02'])->assertForbidden();
    }

    public function test_the_profile_shows_what_the_employee_holds()
    {
        $employee = User::factory()->has(UserDetail::factory(), 'details')->create();
        Equipment::factory()->ofType($this->type())->issuedTo($employee->id)->create(['inventory_number' => 'EV-0421']);
        // Somebody else's unit must not show up here.
        Equipment::factory()->ofType($this->type())->issuedTo(User::factory()->create()->id)->create();

        $this->actingAs($employee)
            ->get("/employees/{$employee->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('employee.private.equipment', 1)
                ->where('employee.private.equipment.0.inventory_number', 'EV-0421')
            );
    }

    public function test_the_directory_counts_units_in_service()
    {
        Equipment::factory(2)->ofType($this->type())->create();
        Equipment::factory()->ofType($this->type())->writtenOff()->create();

        $this->actingAs($this->admin())
            ->get('/directories/equipment')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('items', fn ($items) => collect($items)->firstWhere('name', 'Ноутбуки')['users_count'] === 2)
            );
    }
}
