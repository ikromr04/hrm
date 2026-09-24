<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

/**
 * The kinds of time off the company recognises, with the days a year each one
 * allows. Annual leave may be taken in parts, but no part may run past two
 * weeks; unpaid leave has no yearly allowance at all.
 */
class LeaveTypeSeeder extends Seeder
{
    /** name => [days a year, longest single spell, badge tone] */
    public const TYPES = [
        'Ежегодный отпуск' => [28, 14, 'success'],
        'Дополнительный отпуск' => [7, 7, 'info'],
        'Больничный' => [30, null, 'warning'],
        'Отгул' => [5, 2, 'neutral'],
        'Отпуск без содержания' => [null, 30, 'neutral'],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::TYPES as $name => [$days, $part, $tone]) {
            LeaveType::updateOrCreate(
                ['name' => $name],
                ['days_per_year' => $days, 'max_part_days' => $part, 'tone' => $tone, 'position' => $position++],
            );
        }
    }
}
