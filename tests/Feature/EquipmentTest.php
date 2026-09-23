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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->post("/equipment/{$unit->id}/issue", [
                'holder_user_id' => $employee->id,
                'issued_at' => '2026-03-14',
                'act_number' => '№ 214-1',
            ])
            ->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame('issued', $unit->status);
        $this->assertSame($employee->id, $unit->holder_user_id);
        $this->assertSame('2026-03-14', $unit->issued_at->toDateString());

        // The handover is written into the unit's history, act and all.
        $spell = $unit->currentAssignment;
        $this->assertSame($employee->id, $spell->holder_user_id);
        $this->assertSame('№ 214-1', $spell->act_number);
        $this->assertNull($spell->returned_at);
    }

    public function test_the_history_closes_one_spell_before_it_opens_the_next()
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $unit = Equipment::factory()->ofType($this->type())->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/equipment/{$unit->id}/issue", ['holder_user_id' => $first->id, 'issued_at' => '2026-01-10']);
        $this->actingAs($admin)->post("/equipment/{$unit->id}/take", ['condition_on_return' => 'Царапина на крышке']);
        $this->actingAs($admin)->post("/equipment/{$unit->id}/issue", ['holder_user_id' => $second->id, 'issued_at' => '2026-03-14']);

        // With the first colleague, on the shelf between them, then with the
        // second. The relation reads newest first; here the order of events is
        // what matters, so the sort is replaced rather than added to.
        $spells = $unit->assignments()->reorder('id')->get();
        $this->assertCount(3, $spells);
        $this->assertSame($first->id, $spells[0]->holder_user_id);
        $this->assertSame('Царапина на крышке', $spells[0]->condition_on_return);
        $this->assertNull($spells[1]->holder_user_id);
        $this->assertNotNull($spells[1]->returned_at);
        $this->assertSame($second->id, $spells[2]->holder_user_id);
        $this->assertNull($spells[2]->returned_at);

        // What came back is also what the card now says about its state.
        $this->assertSame('Царапина на крышке', $unit->fresh()->condition);
    }

    public function test_the_card_shows_the_unit_its_history_and_its_repairs()
    {
        $employee = User::factory()->create(['surname' => 'Рахимов']);
        $unit = Equipment::factory()->ofType($this->type())->issuedTo($employee->id)->create([
            'name' => 'Ноутбук Dell Latitude 5440',
            'processor' => 'Intel Core i5-1335U',
            'warranty_until' => '2024-03-05',
            'accessories' => ['Блок питания 65 Вт', 'Сумка'],
        ]);
        $unit->assignments()->create(['holder_user_id' => $employee->id, 'issued_at' => '2021-03-12', 'act_number' => '№ 214-1']);
        $unit->repairs()->create(['kind' => 'Замена аккумулятора', 'started_at' => '2024-11-02', 'ended_at' => '2024-11-06']);

        $this->actingAs($this->admin())
            ->get("/equipment/{$unit->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('equipment/show')
                ->where('unit.name', 'Ноутбук Dell Latitude 5440')
                ->where('unit.processor', 'Intel Core i5-1335U')
                // The cover ran out in 2024, so the card says so.
                ->where('unit.warranty_expired', true)
                ->where('unit.accessories.1', 'Сумка')
                ->where('unit.act_number', '№ 214-1')
                ->has('assignments', 1)
                ->has('repairs', 1)
                ->has('documents', 0)
            );
    }

    public function test_the_card_points_at_the_units_either_side_of_it()
    {
        $first = Equipment::factory()->ofType($this->type())->create(['name' => 'Монитор Dell P2422H']);
        $middle = Equipment::factory()->ofType($this->type())->create(['name' => 'Ноутбук Acer Aspire 5']);
        $last = Equipment::factory()->ofType($this->type())->create(['name' => 'Телефон Xiaomi Redmi 12']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get("/equipment/{$middle->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('neighbours.prev.name', $first->name)
                ->where('neighbours.next.name', $last->name)
            );

        // At either end of the list there is nowhere further to go.
        $this->actingAs($admin)
            ->get("/equipment/{$first->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('neighbours.prev', null)
                ->where('neighbours.next.name', $middle->name)
            );
    }

    public function test_walking_the_fleet_stays_within_one_status()
    {
        $holder = User::factory()->create();
        // In stock either side of the one that is out, and one more in repair.
        Equipment::factory()->ofType($this->type())->create(['name' => 'А, на складе']);
        $issued = Equipment::factory()->ofType($this->type())->issuedTo($holder->id)->create(['name' => 'Б, выдан']);
        Equipment::factory()->ofType($this->type())->create(['name' => 'В, на складе']);
        Equipment::factory()->ofType($this->type())->inRepair()->create(['name' => 'Г, в ремонте']);

        $later = Equipment::factory()->ofType($this->type())->issuedTo($holder->id)->create(['name' => 'Я, тоже выдан']);

        $this->actingAs($this->admin())
            ->get("/equipment/{$issued->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                // Nothing issued comes before it, whatever sits there in stock.
                ->where('neighbours.prev', null)
                ->where('neighbours.next.name', $later->name)
            );
    }

    public function test_the_card_blocks_are_edited_one_at_a_time()
    {
        $unit = Equipment::factory()->ofType($this->type())->create(['inventory_number' => 'EV-0421']);
        $taken = Equipment::factory()->ofType($this->type())->create(['inventory_number' => 'EV-0999']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/equipment/{$unit->id}/specs", [
                'equipment_type_id' => $this->type('Мониторы')->id,
                'name' => 'Монитор Dell P2422H',
                'maker' => 'Dell',
                'model' => 'P2422H',
                'inventory_number' => 'EV-0421',
                'price' => 1450,
            ])
            ->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame('Монитор Dell P2422H', $unit->name);
        $this->assertSame('P2422H', $unit->model);
        $this->assertSame($this->type('Мониторы')->id, $unit->equipment_type_id);
        // Its own number is not a clash with itself.
        $this->assertSame('EV-0421', $unit->inventory_number);

        // Somebody else's number still is.
        $this->actingAs($admin)
            ->put("/equipment/{$unit->id}/specs", [
                'equipment_type_id' => $unit->equipment_type_id,
                'name' => $unit->name,
                'inventory_number' => $taken->inventory_number,
            ])
            ->assertSessionHasErrors('inventory_number');

        // The list of accessories is replaced whole, blanks dropped.
        $this->actingAs($admin)
            ->put("/equipment/{$unit->id}/accessories", ['accessories' => ['Кабель HDMI', '  ', 'Подставка']])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Кабель HDMI', 'Подставка'], $unit->fresh()->accessories);

        $this->actingAs(User::factory()->create())
            ->put("/equipment/{$unit->id}/accessories", ['accessories' => []])
            ->assertForbidden();
    }

    public function test_the_sidebar_blocks_are_edited_too()
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $unit = Equipment::factory()->ofType($this->type())->issuedTo($first->id)->create();
        $unit->assignments()->create(['holder_user_id' => $first->id, 'issued_at' => '2026-01-10']);
        $admin = $this->admin();

        // "Состояние" is filled in by hand when somebody checks a unit in place.
        $this->actingAs($admin)
            ->put("/equipment/{$unit->id}/state", [
                'condition' => 'Рабочее, следы эксплуатации',
                'checked_at' => '2026-09-01',
                'next_inventory_at' => '2026-12-01',
            ])
            ->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame('Рабочее, следы эксплуатации', $unit->condition);
        $this->assertSame('2026-09-01', $unit->checked_at->toDateString());

        // Correcting the handover moves neither the status nor the history row.
        $this->actingAs($admin)
            ->put("/equipment/{$unit->id}/handover", [
                'holder_user_id' => $second->id,
                'issued_at' => '2026-02-20',
                'act_number' => '№ 300-2',
            ])
            ->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame('issued', $unit->status);
        $this->assertSame($second->id, $unit->holder_user_id);
        $this->assertSame('2026-02-20', $unit->issued_at->toDateString());
        $this->assertCount(1, $unit->assignments);
        $this->assertSame('№ 300-2', $unit->currentAssignment->act_number);

        // A unit nobody holds has no handover to correct.
        $stock = Equipment::factory()->ofType($this->type())->create();
        $this->actingAs($admin)
            ->put("/equipment/{$stock->id}/handover", ['holder_user_id' => $second->id, 'issued_at' => '2026-02-20'])
            ->assertStatus(422);
    }

    public function test_a_repair_without_an_end_date_takes_the_unit_out_of_service()
    {
        $employee = User::factory()->create();
        $unit = Equipment::factory()->ofType($this->type())->issuedTo($employee->id)->create();

        $this->actingAs($this->admin())
            ->post("/equipment/{$unit->id}/repairs", ['kind' => 'Диагностика', 'started_at' => '2026-09-01'])
            ->assertSessionHasNoErrors();

        $unit->refresh();
        $this->assertSame('repair', $unit->status);
        $this->assertNull($unit->holder_user_id);
        $this->assertCount(1, $unit->repairs);

        // A finished visit is only a record: the unit stays where it is.
        $stock = Equipment::factory()->ofType($this->type())->create();
        $this->actingAs($this->admin())
            ->post("/equipment/{$stock->id}/repairs", ['kind' => 'Плановое ТО', 'started_at' => '2026-09-01', 'ended_at' => '2026-09-03']);
        $this->assertSame('stock', $stock->refresh()->status);
    }

    public function test_a_document_is_uploaded_and_removed_with_its_file()
    {
        Storage::fake('public');
        $unit = Equipment::factory()->ofType($this->type())->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/equipment/{$unit->id}/documents", [
                'title' => 'Акт передачи № 214-1',
                'file' => UploadedFile::fake()->create('act.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $document = $unit->documents()->sole();
        $this->assertSame('pdf', $document->extension);
        Storage::disk('public')->assertExists($document->path);

        $this->actingAs($admin)->delete("/equipment/{$unit->id}/documents/{$document->id}")->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($document->path);
        $this->assertSame(0, $unit->documents()->count());
    }

    public function test_only_managers_touch_repairs_and_documents()
    {
        $unit = Equipment::factory()->ofType($this->type())->create();

        $this->actingAs(User::factory()->create());
        $this->post("/equipment/{$unit->id}/repairs", ['kind' => 'Диагностика', 'started_at' => '2026-09-01'])->assertForbidden();
        $this->post("/equipment/{$unit->id}/documents", ['title' => 'Акт', 'file' => UploadedFile::fake()->create('act.pdf')])->assertForbidden();
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
