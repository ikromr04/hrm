<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserEducation;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Education is kept record by record: adding one must not rewrite the rest.
 */
class EmployeeEducationTest extends TestCase
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
            'institution' => 'Таджикский национальный университет',
            'faculty' => 'Экономический',
            'specialty' => 'Финансы и кредит',
            'started_year' => 2010,
            'graduated_year' => 2015,
            'diploma_number' => 'AB 123456',
            ...$overrides,
        ];
    }

    public function test_an_admin_adds_a_place_of_study_without_touching_the_others()
    {
        $employee = User::factory()->create();
        $kept = UserEducation::factory()->for($employee)->create(['institution' => 'Курсы']);

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/educations", $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            ['Курсы', 'Таджикский национальный университет'],
            $employee->refresh()->educations->pluck('institution')->all(),
        );
        $this->assertNotNull($kept->fresh());
    }

    public function test_an_admin_changes_one_record()
    {
        $employee = User::factory()->create();
        $education = UserEducation::factory()->for($employee)->create();

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}/educations/{$education->id}", $this->payload(['faculty' => 'Юридический']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Юридический', $education->fresh()->faculty);
    }

    public function test_an_admin_deletes_one_record()
    {
        $employee = User::factory()->create();
        $education = UserEducation::factory()->for($employee)->create();
        $kept = UserEducation::factory()->for($employee)->create();

        $this->actingAs($this->admin())
            ->delete("/employees/{$employee->id}/educations/{$education->id}")
            ->assertSessionHasNoErrors();

        $this->assertNull($education->fresh());
        $this->assertNotNull($kept->fresh());
    }

    public function test_a_record_may_be_left_unfinished()
    {
        $employee = User::factory()->create();

        // Still studying: no graduation year and no diploma yet.
        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/educations", $this->payload(['graduated_year' => '', 'diploma_number' => '']))
            ->assertSessionHasNoErrors();

        $education = $employee->refresh()->educations->first();
        $this->assertNull($education->graduated_year);
        $this->assertNull($education->diploma_number);
    }

    public function test_invalid_data_is_rejected()
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/educations", $this->payload([
                'institution' => '',
                // Nobody graduates before enrolling.
                'started_year' => 2020,
                'graduated_year' => 2018,
            ]))
            ->assertSessionHasErrors(['institution', 'graduated_year']);
    }

    public function test_a_record_cannot_be_reached_through_another_employee()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $education = UserEducation::factory()->for($owner)->create(['faculty' => 'Экономический']);

        // The id is real, but it belongs to somebody else.
        $this->actingAs($this->admin())
            ->put("/employees/{$stranger->id}/educations/{$education->id}", $this->payload())
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->delete("/employees/{$stranger->id}/educations/{$education->id}")
            ->assertNotFound();

        $this->assertSame('Экономический', $education->fresh()->faculty);
    }

    public function test_an_employee_cannot_touch_anyones_education()
    {
        $employee = User::factory()->create();
        $education = UserEducation::factory()->for($employee)->create();

        // Not even their own: the block is managed by HR.
        $this->actingAs($employee);
        $this->post("/employees/{$employee->id}/educations", $this->payload())->assertForbidden();
        $this->put("/employees/{$employee->id}/educations/{$education->id}", $this->payload())->assertForbidden();
        $this->delete("/employees/{$employee->id}/educations/{$education->id}")->assertForbidden();
    }
}
