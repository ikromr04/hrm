<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_positions_once()
    {
        $this->seed(PositionSeeder::class);
        $this->seed(PositionSeeder::class);

        $this->assertSame(39, Position::count());
        $this->assertTrue(Position::where('name', 'Руководитель отдела дизайна')->exists());
    }

    public function test_an_employee_can_hold_several_positions()
    {
        $user = User::factory()->create();
        Position::create(['name' => 'Переводчик']);
        Position::create(['name' => 'Копирайтер']);
        $user->positions()->attach(Position::pluck('id'));

        $this->assertSame(['Копирайтер', 'Переводчик'], $user->fresh()->positions->pluck('name')->all());
    }

    public function test_each_head_title_is_held_by_someone_in_the_unit_they_lead()
    {
        $this->seed(DatabaseSeeder::class);

        foreach (PositionSeeder::HEADS as $title => $department) {
            $holders = Position::firstWhere('name', $title)->users()->with('departments')->get();

            $this->assertCount(1, $holders, "{$title} should have exactly one holder");
            $this->assertContains($department, $holders->first()->departments->pluck('name'));
        }
    }

    public function test_every_employee_but_the_admin_has_a_position()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::withoutRole('admin')->doesntHave('positions')->count());
        $this->assertCount(0, User::role('admin')->first()->positions);
        $this->assertSame(0, User::where('sex', 'male')->whereHas('positions', fn ($q) => $q->where('name', 'Уборщица'))->count());
    }
}
