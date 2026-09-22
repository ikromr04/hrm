<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_departments_as_a_tree()
    {
        $this->seed(DepartmentSeeder::class);

        $this->assertSame(26, Department::count());
        $this->assertSame(8, Department::whereNull('parent_id')->count());

        $design = Department::firstWhere('name', 'Отдел Дизайна');
        $this->assertSame('Департамент маркетинга', $design->parent->name);
        $this->assertSame('Департамент маркетинга › Отдел Дизайна', $design->path());
    }

    public function test_seeder_can_run_twice_without_duplicates()
    {
        $this->seed(DepartmentSeeder::class);
        $this->seed(DepartmentSeeder::class);

        $this->assertSame(26, Department::count());
    }

    public function test_any_department_can_be_nested_at_any_depth()
    {
        $root = Department::create(['name' => 'Корень']);
        $middle = Department::create(['name' => 'Середина', 'parent_id' => $root->id]);
        $leaf = Department::create(['name' => 'Лист', 'parent_id' => $middle->id]);

        $this->assertEqualsCanonicalizing([$middle->id, $leaf->id], $root->descendantIds()->all());
        $this->assertSame('Корень › Середина › Лист', $leaf->path());
    }

    public function test_a_department_cannot_be_moved_under_itself_or_its_sub_department()
    {
        $root = Department::create(['name' => 'Корень']);
        $child = Department::create(['name' => 'Дочерний', 'parent_id' => $root->id]);

        $this->expectException(InvalidArgumentException::class);

        $root->update(['parent_id' => $child->id]);
    }

    public function test_a_department_cannot_be_its_own_parent()
    {
        $department = Department::create(['name' => 'Отдел']);

        $this->expectException(InvalidArgumentException::class);

        $department->update(['parent_id' => $department->id]);
    }

    public function test_an_employee_can_be_in_none_one_or_several_departments()
    {
        [$first, $second] = [Department::create(['name' => 'Первый']), Department::create(['name' => 'Второй'])];
        $user = User::factory()->create();

        $this->assertCount(0, $user->departments);

        $user->departments()->attach([$first->id, $second->id]);

        $this->assertSame(['Второй', 'Первый'], $user->fresh()->departments->pluck('name')->all());
        $this->assertSame(1, $first->users()->count());
    }

    public function test_seeded_head_positions_lead_their_department()
    {
        $this->seed(DatabaseSeeder::class);

        foreach (PositionSeeder::HEADS as $position => $name) {
            $department = Department::with('heads.positions')->firstWhere('name', $name);

            $this->assertNotEmpty($department->heads, "{$name} should have a head");
            $this->assertTrue($department->heads->every(fn (User $head) => $head->isActive()));
            $this->assertTrue($department->heads->contains(fn (User $head) => $head->positions->contains('name', $position)));
        }
    }

    public function test_seeded_heads_sit_in_the_unit_they_lead()
    {
        $this->seed(DatabaseSeeder::class);

        User::role('department-head')->with('departments')->get()->each(
            fn (User $head) => $this->assertNull($head->departments->sole()->parent_id),
        );
        User::role('division-head')->with('departments')->get()->each(
            fn (User $head) => $this->assertNotNull($head->departments->sole()->parent_id),
        );

        $this->assertCount(0, User::role('admin')->first()->departments);
        $this->assertSame(0, User::has('departments', '>', 2)->count());
    }
}
