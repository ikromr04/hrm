<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One spell of time off somebody has asked for. It travels from the head of
 * their department to HR; until both have said yes it is only a request, but
 * it already counts against the balance, so two overlapping asks cannot both
 * be granted by accident.
 */
class LeaveRequest extends Model
{
    /** Still on its way through: neither settled nor withdrawn. */
    public const OPEN = ['pending_head', 'pending_hr'];

    /** Counts against the balance: granted, or on its way to being granted. */
    public const COUNTED = ['pending_head', 'pending_hr', 'approved'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'started_on',
        'ended_on',
        'days',
        'note',
        'status',
        'head_id',
        'head_decided_at',
        'hr_id',
        'hr_decided_at',
        'decision_note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
            'days' => 'integer',
            'head_decided_at' => 'datetime',
            'hr_decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    /** Waiting on somebody's decision. */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', self::OPEN);
    }

    /** What a balance is counted from: everything not refused or withdrawn. */
    public function scopeCounted(Builder $query): void
    {
        $query->whereIn('status', self::COUNTED);
    }
}
