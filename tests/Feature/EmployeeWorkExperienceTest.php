<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserWorkExperience;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Previous jobs are kept record by record: adding one must not rewrite the rest.
 */
class EmployeeWorkExperienceTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return [
            'organization' => 'ООО «Шифобахш»',
            'position' => 'Фармацевт',
            'country' => 'Таджикистан',
            'started_month' => 3,
            'started_year' => 2015,
            'ended_month' => 6,
            'ended_year' => 2018,
            ...$overrides,
        ];
    }

    public function test_an_admin_adds_a_job_without_touching_the_others()
    {
        $employee = User::factory()->create();
        $kept = UserWorkExperience::factory()->for($employee)->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/experiences", $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertCount(2, $employee->refresh()->workExperiences);
        $this->assertNotNull($kept->fresh());
    }

    public function test_an_admin_changes_one_record()
    {
        $employee = User::factory()->create();
        $job = UserWorkExperience::factory()->for($employee)->create();

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}/experiences/{$job->id}", $this->payload(['position' => 'Аналитик']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Аналитик', $job->fresh()->position);
    }

    public function test_an_admin_deletes_one_record()
    {
        $employee = User::factory()->create();
        $job = UserWorkExperience::factory()->for($employee)->create();
        $kept = UserWorkExperience::factory()->for($employee)->create();

        $this->actingAs($this->admin())
            ->delete("/employees/{$employee->id}/experiences/{$job->id}")
            ->assertSessionHasNoErrors();

        $this->assertNull($job->fresh());
        $this->assertNotNull($kept->fresh());
    }

    public function test_a_job_the_person_still_holds_has_no_end()
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/experiences", $this->payload(['ended_month' => '', 'ended_year' => '']))
            ->assertSessionHasNoErrors();

        $job = $employee->refresh()->workExperiences->first();
        $this->assertNull($job->ended_month);
        $this->assertNull($job->ended_year);
    }

    public function test_half_an_end_date_is_rejected()
    {
        $employee = User::factory()->create();

        // A month without a year says nothing about when the job ended.
        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/experiences", $this->payload(['ended_year' => '']))
            ->assertSessionHasErrors('ended_year');
    }

    public function test_dates_must_make_sense()
    {
        $employee = User::factory()->create();
        $admin = $this->admin();

        // Left before joining.
        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/experiences", $this->payload([
                'started_month' => 5,
                'started_year' => 2020,
                'ended_month' => 4,
                'ended_year' => 2020,
            ]))
            ->assertSessionHasErrors('ended_year');

        // Nobody starts a past job next year.
        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/experiences", $this->payload([
                'started_month' => 1,
                'started_year' => (int) date('Y') + 1,
                'ended_month' => '',
                'ended_year' => '',
            ]))
            ->assertSessionHasErrors('started_year');
    }

    public function test_a_record_cannot_be_reached_through_another_employee()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $job = UserWorkExperience::factory()->for($owner)->create(['position' => 'Фармацевт']);

        // The id is real, but it belongs to somebody else.
        $this->actingAs($this->admin())
            ->put("/employees/{$stranger->id}/experiences/{$job->id}", $this->payload())
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->delete("/employees/{$stranger->id}/experiences/{$job->id}")
            ->assertNotFound();

        $this->assertSame('Фармацевт', $job->fresh()->position);
    }

    public function test_an_employee_cannot_touch_anyones_work_experience()
    {
        $employee = User::factory()->create();
        $job = UserWorkExperience::factory()->for($employee)->create();

        // Not even their own: the block is managed by HR.
        $this->actingAs($employee);
        $this->post("/employees/{$employee->id}/experiences", $this->payload())->assertForbidden();
        $this->put("/employees/{$employee->id}/experiences/{$job->id}", $this->payload())->assertForbidden();
        $this->delete("/employees/{$employee->id}/experiences/{$job->id}")->assertForbidden();
    }
}
