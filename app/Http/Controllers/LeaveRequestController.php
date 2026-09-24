<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Asking for time off, and what becomes of the ask: the head of the employee's
 * department decides first, HR after them. Either of them may turn it down,
 * and whoever asked may withdraw it while it is still on its way.
 */
class LeaveRequestController extends Controller
{
    /**
     * A colleague files for themselves; HR may file on anyone's behalf, which
     * is how a sick note that arrives on paper gets onto the books.
     */
    public function store(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        $forOthers = $viewer->can('manage-employees');

        $data = $request->validate([
            'user_id' => $forOthers
                ? ['nullable', 'integer', Rule::exists('users', 'id')]
                : ['nullable', Rule::in([$viewer->id])],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'started_on' => ['required', 'date'],
            'ended_on' => ['required', 'date', 'after_or_equal:started_on'],
            'note' => ['nullable', 'string', 'max:300'],
        ], attributes: [
            'user_id' => 'сотрудник',
            'leave_type_id' => 'вид отсутствия',
            'started_on' => 'первый день',
            'ended_on' => 'последний день',
            'note' => 'комментарий',
        ]);

        $employee = isset($data['user_id']) ? User::findOrFail($data['user_id']) : $viewer;
        $type = LeaveType::findOrFail($data['leave_type_id']);
        $started = Carbon::parse($data['started_on']);
        $ended = Carbon::parse($data['ended_on']);
        $days = $started->diffInDays($ended) + 1;

        $this->check($employee, $type, $started, $ended, $days);

        // With no head over them — the head of their own department, or nobody
        // at all — there is no first step to wait for.
        $status = $this->headOf($employee) === null ? 'pending_hr' : 'pending_head';

        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'started_on' => $started->toDateString(),
            'ended_on' => $ended->toDateString(),
            'days' => $days,
            'note' => $data['note'] ?? null,
            'status' => $status,
        ]);

        return back();
    }

    /**
     * Yes, from whoever the request is waiting on. The head's yes passes it to
     * HR; HR's yes settles it.
     */
    public function approve(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $viewer = $request->user();
        $step = $this->step($leave, $viewer);

        if ($step === 'head') {
            $leave->update(['status' => 'pending_hr', 'head_id' => $viewer->id, 'head_decided_at' => now()]);

            return back();
        }

        // HR may say yes while it is still with the head, which settles both
        // steps at once: there is nobody above HR to ask.
        $leave->update([
            'status' => 'approved',
            'head_id' => $leave->head_id ?? $viewer->id,
            'head_decided_at' => $leave->head_decided_at ?? now(),
            'hr_id' => $viewer->id,
            'hr_decided_at' => now(),
        ]);

        return back();
    }

    /**
     * No, from either step, with a reason: a refusal nobody can explain is
     * worse than none.
     */
    public function reject(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $viewer = $request->user();
        $step = $this->step($leave, $viewer);

        $data = $request->validate([
            'decision_note' => ['required', 'string', 'max:300'],
        ], attributes: ['decision_note' => 'причина']);

        $leave->update([
            'status' => 'rejected',
            'decision_note' => $data['decision_note'],
            ...$step === 'head'
                ? ['head_id' => $viewer->id, 'head_decided_at' => now()]
                : ['hr_id' => $viewer->id, 'hr_decided_at' => now()],
        ]);

        return back();
    }

    /**
     * Withdrawn by whoever asked, while it is still on its way.
     */
    public function cancel(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($leave->user_id === $request->user()->id, 403);
        abort_unless(in_array($leave->status, LeaveRequest::OPEN, true), 422, 'Решение по заявке уже принято.');

        $leave->update(['status' => 'cancelled']);

        return back();
    }

    /**
     * Which step of the chain this viewer is, or a refusal if they are none of
     * it. Nobody decides on their own request, whatever they may head.
     */
    private function step(LeaveRequest $leave, User $viewer): string
    {
        abort_unless(in_array($leave->status, LeaveRequest::OPEN, true), 422, 'Решение по заявке уже принято.');
        abort_if($leave->user_id === $viewer->id, 403, 'Нельзя решать по собственной заявке.');

        if ($leave->status === 'pending_head' && $viewer->headOf($leave->user) && ! $viewer->can('manage-employees')) {
            return 'head';
        }

        abort_unless($viewer->can('manage-employees'), 403);

        return 'hr';
    }

    /**
     * Who decides first for this employee: the head of a department they are
     * in, other than themselves.
     */
    private function headOf(User $employee): ?User
    {
        $departments = $employee->departments()->pluck('departments.id');

        // The pivot is joined by whereHas, so the flag is read off it here;
        // wherePivot belongs to the relation itself and has no place inside.
        return User::query()
            ->whereKeyNot($employee->id)
            ->whereHas('departments', fn (Builder $q) => $q
                ->whereIn('departments.id', $departments)
                ->where('department_user.is_head', true))
            ->first();
    }

    /**
     * The rules a spell of leave has to keep: inside the allowance, no longer
     * than one part may run, and not on top of another spell of their own.
     */
    private function check(User $employee, LeaveType $type, Carbon $started, Carbon $ended, int $days): void
    {
        /** @var array<string, string> $errors */
        $errors = [];

        if ($type->max_part_days !== null && $days > $type->max_part_days) {
            $errors['ended_on'] = "«{$type->name}» нельзя брать больше {$type->max_part_days} дн. подряд.";
        }

        if ($type->days_per_year !== null) {
            $used = (int) LeaveRequest::query()
                ->where('user_id', $employee->id)
                ->where('leave_type_id', $type->id)
                ->counted()
                ->whereYear('started_on', $started->year)
                ->sum('days');

            $left = $type->days_per_year - $used;

            if ($days > $left) {
                $errors['ended_on'] = $left > 0
                    ? "Остаток на {$started->year} год — {$left} дн., а в заявке {$days}."
                    : "Дни по виду «{$type->name}» на {$started->year} год уже израсходованы.";
            }
        }

        $overlaps = LeaveRequest::query()
            ->where('user_id', $employee->id)
            ->counted()
            ->whereDate('started_on', '<=', $ended->toDateString())
            ->whereDate('ended_on', '>=', $started->toDateString())
            ->exists();

        if ($overlaps) {
            $errors['started_on'] = 'На эти дни уже есть заявка.';
        }

        // Thrown rather than added to a validator: these checks have no rules
        // of their own to run, and a validator would clear them on the way out.
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
