<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use App\Models\UserDetail;
use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The steps of the "new colleague" wizard that file several records at once:
 * where they studied, where they worked, and what hardware they are given.
 * Each step may be left empty, which is how it is skipped.
 */
class EmployeeWizardStepsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, EquipmentTypeSeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function employee(): User
    {
        return User::factory()->has(UserDetail::factory(), 'details')->create();
    }

    public function test_several_places_of_study_are_filed_in_one_request()
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/educations/many", [
                'records' => [
                    ['institution' => 'ТНУ', 'faculty' => 'Экономический', 'specialty' => 'Финансы', 'started_year' => 2010, 'graduated_year' => 2014],
                    ['institution' => 'РТСУ', 'faculty' => 'Юридический', 'specialty' => 'Право', 'started_year' => 2015, 'graduated_year' => 2019],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertCount(2, $employee->educations);
    }

    public function test_a_bad_record_names_its_own_row()
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/educations/many", [
                'records' => [
                    ['institution' => 'ТНУ', 'faculty' => 'Экономический', 'specialty' => 'Финансы', 'started_year' => 2010],
                    // A diploma cannot predate enrolment, and the faculty is missing.
                    ['institution' => 'РТСУ', 'specialty' => 'Право', 'started_year' => 2015, 'graduated_year' => 2012],
                ],
            ])
            ->assertSessionHasErrors(['records.1.faculty', 'records.1.graduated_year'])
            ->assertSessionDoesntHaveErrors('records.0.institution');

        $this->assertCount(0, $employee->educations);
    }

    public function test_previous_jobs_are_filed_together_and_checked_row_by_row()
    {
        $employee = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/experiences/many", [
                'records' => [
                    [
                        'organization' => 'ООО «Ориён»',
                        'position' => 'Аналитик',
                        'country' => 'Таджикистан',
                        'started_month' => 3,
                        'started_year' => 2018,
                        'ended_month' => 8,
                        'ended_year' => 2021,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertCount(1, $employee->workExperiences);

        // Leaving before joining is caught on the row it happened in.
        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/experiences/many", [
                'records' => [
                    [
                        'organization' => 'ООО «Ориён»',
                        'position' => 'Аналитик',
                        'country' => 'Таджикистан',
                        'started_month' => 3,
                        'started_year' => 2021,
                        'ended_month' => 8,
                        'ended_year' => 2018,
                    ],
                ],
            ])
            ->assertSessionHasErrors('records.0.ended_year');
    }

    public function test_an_empty_step_is_simply_skipped()
    {
        $employee = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/employees/{$employee->id}/educations/many", ['records' => []])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/employees/{$employee->id}/experiences/many", ['records' => []])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/employees/{$employee->id}/equipment", ['equipment' => []])->assertSessionHasNoErrors();

        $this->assertCount(0, $employee->educations);
        $this->assertCount(0, $employee->workExperiences);
        $this->assertCount(0, $employee->equipment);
    }

    public function test_a_workplace_is_handed_over_in_one_go()
    {
        $employee = $this->employee();
        $type = EquipmentType::firstWhere('name', 'Ноутбуки');
        $laptop = Equipment::factory()->ofType($type)->create();
        $monitor = Equipment::factory()->ofType($type)->create();
        // Somebody else already has this one, so it stays with them.
        $taken = Equipment::factory()->ofType($type)->issuedTo(User::factory()->create()->id)->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/equipment", [
                'equipment' => [$laptop->id, $monitor->id, $taken->id],
                'issued_at' => '2026-03-02',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('issued', $laptop->refresh()->status);
        $this->assertSame($employee->id, $laptop->holder_user_id);
        $this->assertSame('2026-03-02', $laptop->issued_at->toDateString());
        // The handover is in its history, like any other.
        $this->assertSame($employee->id, $laptop->refresh()->holder_user_id);

        $this->assertSame($employee->id, $monitor->refresh()->holder_user_id);
        $this->assertNotSame($employee->id, $taken->refresh()->holder_user_id);
    }

    public function test_only_managers_file_these_steps()
    {
        $employee = $this->employee();

        $this->actingAs(User::factory()->create());
        $this->post("/employees/{$employee->id}/educations/many", ['records' => []])->assertForbidden();
        $this->post("/employees/{$employee->id}/experiences/many", ['records' => []])->assertForbidden();
        $this->post("/employees/{$employee->id}/equipment", ['equipment' => []])->assertForbidden();
    }
}
