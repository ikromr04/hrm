<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Time off: what a colleague has left of each kind, what they have asked for,
 * and — for whoever decides — what is waiting on them.
 */
class LeaveController extends Controller
{
    public const PER_PAGE_OPTIONS = [25, 50, 100];

    /** The tabs above the table; "all" is not a status. */
    private const TABS = ['open', 'approved', 'rejected'];

    public function index(Request $request): Response
    {
        $viewer = $request->user();
        // Heads and HR see everybody's requests; everyone else sees their own.
        $decides = $viewer->can('approve-leave');

        $input = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
            'type' => ['nullable', 'array'],
            'type.*' => ['integer', Rule::exists('leave_types', 'id')],
            'employee' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $year = (int) ($input['year'] ?? Carbon::today()->year);
        $tab = $input['tab'] ?? null;
        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_OPTIONS[0]);

        $filters = [
            'type' => array_map('intval', $input['type'] ?? []),
            'employee' => trim($input['employee'] ?? ''),
            'from' => $input['from'] ?? null,
            'to' => $input['to'] ?? null,
        ];

        $query = LeaveRequest::query()
            ->with(['user:id,name,surname,avatar', 'type:id,name,tone'])
            ->when(! $decides, fn (Builder $q) => $q->where('user_id', $viewer->id))
            ->when($tab === 'open', fn (Builder $q) => $q->open())
            ->when($tab === 'approved', fn (Builder $q) => $q->where('status', 'approved'))
            ->when($tab === 'rejected', fn (Builder $q) => $q->whereIn('status', ['rejected', 'cancelled']))
            ->when($filters['type'], fn (Builder $q, array $ids) => $q->whereIn('leave_type_id', $ids))
            ->when($filters['from'], fn (Builder $q, string $date) => $q->whereDate('ended_on', '>=', $date))
            ->when($filters['to'], fn (Builder $q, string $date) => $q->whereDate('started_on', '<=', $date))
            ->when($filters['employee'], fn (Builder $q, string $term) => $q->whereHas(
                'user',
                fn (Builder $q) => $q->where('surname', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"),
            ))
            ->orderByDesc('started_on')
            ->orderByDesc('id');

        $requests = $query->paginate($perPage)->withQueryString()->through(fn (LeaveRequest $leave) => $this->row($leave, $viewer));

        return Inertia::render('leave/index', [
            'requests' => $requests,
            'filters' => $filters,
            'tab' => $tab,
            'year' => $year,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'balances' => $this->balances($viewer, $year),
            'types' => LeaveType::query()->orderBy('position')->get(['id', 'name', 'days_per_year', 'max_part_days', 'tone']),
            'counts' => $this->counts($viewer, $decides),
            'decides' => $decides,
        ]);
    }

    /**
     * How many days of each kind the person has left this year. Anything not
     * refused or withdrawn counts, so a request still being decided already
     * holds its days — two overlapping asks cannot both be granted by accident.
     *
     * @return array<int, array<string, mixed>>
     */
    private function balances(User $user, int $year): array
    {
        $used = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->counted()
            ->whereYear('started_on', $year)
            ->selectRaw('leave_type_id, sum(days) as total')
            ->groupBy('leave_type_id')
            ->pluck('total', 'leave_type_id');

        return LeaveType::query()
            ->orderBy('position')
            ->get()
            ->map(fn (LeaveType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'tone' => $type->tone,
                'days_per_year' => $type->days_per_year,
                'max_part_days' => $type->max_part_days,
                'used' => (int) ($used[$type->id] ?? 0),
                // An unlimited kind has nothing left to count down.
                'left' => $type->days_per_year === null ? null : max(0, $type->days_per_year - (int) ($used[$type->id] ?? 0)),
            ])
            ->all();
    }

    /**
     * The number beside each tab.
     *
     * @return array<string, int>
     */
    private function counts(User $viewer, bool $decides): array
    {
        $scope = fn () => LeaveRequest::query()->when(! $decides, fn (Builder $q) => $q->where('user_id', $viewer->id));

        return [
            'all' => (int) $scope()->count(),
            'open' => (int) $scope()->open()->count(),
            'approved' => (int) $scope()->where('status', 'approved')->count(),
            'rejected' => (int) $scope()->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(LeaveRequest $leave, User $viewer): array
    {
        return [
            'id' => $leave->id,
            'employee' => [
                'id' => $leave->user->id,
                'name' => "{$leave->user->surname} {$leave->user->name}",
                'avatar' => $leave->user->avatar,
            ],
            'type' => ['name' => $leave->type->name, 'tone' => $leave->type->tone],
            'started_on' => $leave->started_on->toDateString(),
            'ended_on' => $leave->ended_on->toDateString(),
            'days' => $leave->days,
            'note' => $leave->note,
            'status' => $leave->status,
            'decision_note' => $leave->decision_note,
            // What this viewer may do with it, worked out once here rather
            // than guessed at in the browser.
            'can' => [
                'decide' => $this->decidable($leave, $viewer),
                'cancel' => $leave->user_id === $viewer->id && in_array($leave->status, LeaveRequest::OPEN, true),
            ],
        ];
    }

    /**
     * Whether this viewer is the one the request is waiting on: the head of
     * the employee's department while it sits with the head, HR after that.
     * Nobody decides on their own request.
     */
    private function decidable(LeaveRequest $leave, User $viewer): bool
    {
        if ($leave->user_id === $viewer->id) {
            return false;
        }

        return match ($leave->status) {
            'pending_head' => $viewer->headOf($leave->user) || $viewer->can('manage-employees'),
            'pending_hr' => $viewer->can('manage-employees'),
            default => false,
        };
    }
}
