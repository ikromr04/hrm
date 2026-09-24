<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Time off: what a colleague has left of each kind, what they ask for, and the
 * two people who decide — the head of their department, then HR.
 */
class LeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, LeaveTypeSeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function type(string $name = 'Ежегодный отпуск'): LeaveType
    {
        return LeaveType::firstWhere('name', $name);
    }

    /**
     * A department with a head and one of its members.
     *
     * @return array{User, User}
     */
    private function team(): array
    {
        $department = Department::create(['name' => 'Отдел разработки']);
        $head = User::factory()->create();
        $employee = User::factory()->create();

        $head->departments()->attach($department, ['is_head' => true]);
        $employee->departments()->attach($department);

        return [$head, $employee];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'leave_type_id' => $this->type()->id,
            'started_on' => '2026-10-05',
            'ended_on' => '2026-10-16',
            ...$overrides,
        ];
    }

    public function test_the_section_shows_a_colleague_their_own_balance_and_requests()
    {
        [, $employee] = $this->team();
        $other = User::factory()->create();

        $employee->leaveRequests()->create([
            'leave_type_id' => $this->type()->id,
            'started_on' => '2026-05-05',
            'ended_on' => '2026-05-16',
            'days' => 12,
            'status' => 'approved',
        ]);
        $other->leaveRequests()->create([
            'leave_type_id' => $this->type()->id,
            'started_on' => '2026-05-05',
            'ended_on' => '2026-05-09',
            'days' => 5,
            'status' => 'approved',
        ]);

        $this->actingAs($employee)
            ->get('/leave?year=2026')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('leave/index')
                // Somebody else's leave is none of their business.
                ->has('requests.data', 1)
                ->where('decides', false)
                // Twelve of twenty-eight days spent, sixteen left.
                ->where('balances.0.used', 12)
                ->where('balances.0.left', 16)
            );
    }

    public function test_a_head_sees_the_whole_team_and_hr_sees_everybody()
    {
        [$head, $employee] = $this->team();
        $employee->leaveRequests()->create([
            'leave_type_id' => $this->type()->id,
            'started_on' => '2026-05-05',
            'ended_on' => '2026-05-09',
            'days' => 5,
            'status' => 'pending_head',
        ]);

        $this->actingAs($head)
            ->get('/leave')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('decides', true)->has('requests.data', 1));

        $this->actingAs($this->admin())
            ->get('/leave')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('decides', true)->has('requests.data', 1));
    }

    public function test_a_request_goes_to_the_head_first_and_to_hr_after()
    {
        [$head, $employee] = $this->team();

        $this->actingAs($employee)->post('/leave', $this->payload())->assertSessionHasNoErrors();

        $leave = $employee->leaveRequests()->sole();
        $this->assertSame('pending_head', $leave->status);
        $this->assertSame(12, $leave->days);

        // Nobody decides on their own request, whatever they head.
        $this->actingAs($employee)->post("/leave/{$leave->id}/approve")->assertForbidden();

        $this->actingAs($head)->post("/leave/{$leave->id}/approve")->assertSessionHasNoErrors();
        $this->assertSame('pending_hr', $leave->refresh()->status);
        $this->assertSame($head->id, $leave->head_id);

        // A head cannot settle it: that is HR's step.
        $this->actingAs($head)->post("/leave/{$leave->id}/approve")->assertForbidden();

        $hr = $this->admin();
        $this->actingAs($hr)->post("/leave/{$leave->id}/approve")->assertSessionHasNoErrors();

        $leave->refresh();
        $this->assertSame('approved', $leave->status);
        $this->assertSame($hr->id, $leave->hr_id);
    }

    public function test_without_a_head_the_request_waits_on_hr_alone()
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->post('/leave', $this->payload())->assertSessionHasNoErrors();

        $this->assertSame('pending_hr', $employee->leaveRequests()->sole()->status);
    }

    public function test_a_refusal_needs_a_reason_and_frees_the_days_again()
    {
        [$head, $employee] = $this->team();
        $this->actingAs($employee)->post('/leave', $this->payload());
        $leave = $employee->leaveRequests()->sole();

        $this->actingAs($head)->post("/leave/{$leave->id}/reject")->assertSessionHasErrors('decision_note');

        $this->actingAs($head)
            ->post("/leave/{$leave->id}/reject", ['decision_note' => 'В эти дни отдел закрывает квартал'])
            ->assertSessionHasNoErrors();

        $leave->refresh();
        $this->assertSame('rejected', $leave->status);
        $this->assertSame('В эти дни отдел закрывает квартал', $leave->decision_note);

        // Refused days are not spent, so the same dates may be asked for again.
        $this->actingAs($employee)->post('/leave', $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(2, $employee->leaveRequests()->count());
    }

    public function test_the_asker_may_withdraw_it_while_it_is_still_on_its_way()
    {
        [$head, $employee] = $this->team();
        $this->actingAs($employee)->post('/leave', $this->payload());
        $leave = $employee->leaveRequests()->sole();

        // Somebody else's request is not theirs to withdraw.
        $this->actingAs($head)->post("/leave/{$leave->id}/cancel")->assertForbidden();

        $this->actingAs($employee)->post("/leave/{$leave->id}/cancel")->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $leave->refresh()->status);

        // Once settled there is nothing left to withdraw.
        $this->actingAs($employee)->post("/leave/{$leave->id}/cancel")->assertStatus(422);
    }

    public function test_a_spell_keeps_inside_the_allowance_and_the_longest_part()
    {
        $employee = User::factory()->create();
        $annual = $this->type();

        // Annual leave may be split, but no part may run past fourteen days.
        $this->actingAs($employee)
            ->post('/leave', $this->payload(['started_on' => '2026-10-05', 'ended_on' => '2026-10-25']))
            ->assertSessionHasErrors('ended_on');

        // Twenty-eight days a year, and no more.
        $employee->leaveRequests()->create([
            'leave_type_id' => $annual->id,
            'started_on' => '2026-05-05',
            'ended_on' => '2026-05-18',
            'days' => 14,
            'status' => 'approved',
        ]);
        $employee->leaveRequests()->create([
            'leave_type_id' => $annual->id,
            'started_on' => '2026-07-06',
            'ended_on' => '2026-07-19',
            'days' => 14,
            'status' => 'approved',
        ]);

        $this->actingAs($employee)->post('/leave', $this->payload())->assertSessionHasErrors('ended_on');
        $this->assertSame(2, $employee->leaveRequests()->count());
    }

    public function test_two_spells_cannot_cover_the_same_days()
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->post('/leave', $this->payload(['started_on' => '2026-10-05', 'ended_on' => '2026-10-09']));

        // Even a kind of its own cannot sit on days already asked for.
        $this->actingAs($employee)
            ->post('/leave', $this->payload([
                'leave_type_id' => $this->type('Больничный')->id,
                'started_on' => '2026-10-08',
                'ended_on' => '2026-10-12',
            ]))
            ->assertSessionHasErrors('started_on');

        $this->assertSame(1, $employee->leaveRequests()->count());
    }

    public function test_hr_may_file_on_somebody_else_s_behalf_and_a_colleague_may_not()
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())
            ->post('/leave', $this->payload(['user_id' => $employee->id, 'leave_type_id' => $this->type('Больничный')->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $employee->leaveRequests()->count());

        $other = User::factory()->create();
        $this->actingAs($other)
            ->post('/leave', $this->payload(['user_id' => $employee->id]))
            ->assertSessionHasErrors('user_id');
    }

    public function test_an_unlimited_kind_has_no_balance_to_run_out_of()
    {
        $employee = User::factory()->create();
        $unpaid = $this->type('Отпуск без содержания');

        $this->actingAs($employee)
            ->post('/leave', $this->payload(['leave_type_id' => $unpaid->id, 'started_on' => '2026-10-01', 'ended_on' => '2026-10-20']))
            ->assertSessionHasNoErrors();

        $this->actingAs($employee)
            ->get('/leave?year=2026')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('balances.4.days_per_year', null)
                ->where('balances.4.left', null)
                ->where('balances.4.used', 20)
            );
    }

    public function test_the_section_is_open_to_every_signed_in_colleague()
    {
        $this->get('/leave')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/leave')->assertOk();
    }
}
