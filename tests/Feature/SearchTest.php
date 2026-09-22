<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\UserDetail;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_search()
    {
        $this->getJson('/search?q=Азимова')->assertUnauthorized();
    }

    public function test_finds_working_employees_by_every_word_with_public_data_only()
    {
        $designer = Position::create(['name' => 'Дизайнер']);
        $nigina = User::factory()->create(['surname' => 'Азимова', 'name' => 'Нигина', 'email' => 'n.azimova@evolet.test']);
        $nigina->positions()->attach($designer);
        UserDetail::factory()->for($nigina)->create(['home_address' => 'г. Душанбе, ул. Азимова 1']);
        User::factory()->create(['surname' => 'Азимов', 'name' => 'Далер']);
        User::factory()->create(['surname' => 'Азимова', 'name' => 'Мадина', 'status' => 'fired']);
        User::factory()->create(['surname' => 'Азимова', 'name' => 'Зарина', 'status' => 'transferred']);

        $this->actingAs(User::factory()->create());

        // Several words narrow it down; one of them may be the position. (SQLite in tests
        // ignores case for Latin letters only, so the query keeps the stored case.)
        $this->getJson('/search?q=Азимова Дизайнер')->assertOk()->assertExactJson([
            'employees' => [[
                'id' => $nigina->id,
                'name' => 'Азимова Нигина',
                'avatar' => null,
                'email' => 'n.azimova@evolet.test',
                'positions' => ['Дизайнер'],
            ]],
            'departments' => [],
            'positions' => [],
            'roles' => [],
        ]);

        // People who left are not found; private fields are not searched.
        $this->getJson('/search?q=Азимов')->assertJsonCount(2, 'employees');
        $this->getJson('/search?q=Душанбе')->assertJsonCount(0, 'employees');
    }

    public function test_finds_departments_positions_and_roles()
    {
        $this->seed(RoleSeeder::class);
        $department = Department::create(['name' => 'Отдел Дизайна']);
        $position = Position::create(['name' => 'Графический дизайнер']);

        $this->actingAs(User::factory()->create())
            ->getJson('/search?q=изайн')
            ->assertOk()
            ->assertJsonPath('departments', [['id' => $department->id, 'name' => 'Отдел Дизайна']])
            ->assertJsonPath('positions', [['id' => $position->id, 'name' => 'Графический дизайнер']])
            ->assertJsonPath('roles.0.name', 'graphic-designer');
    }

    public function test_an_empty_query_finds_nothing()
    {
        User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/search?q=%20')
            ->assertExactJson(['employees' => [], 'departments' => [], 'positions' => [], 'roles' => []]);
    }
}
