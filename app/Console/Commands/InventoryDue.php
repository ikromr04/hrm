<?php

namespace App\Console\Commands;

use App\Models\Equipment;
use App\Models\User;
use App\Notifications\InventoryDue as InventoryDueNotification;
use App\Support\EquipmentAccess;
use App\Support\Recipients;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * Reminds the people who keep the inventory that a unit's day has come.
 *
 * Meant to run once a day. A unit is due from the day written on its card
 * onwards, so a day the scheduler missed is caught up on the next one; and a
 * reminder is remembered by its unit and its date, so running this twice — or
 * every day while the unit stays uncounted — says it once. Moving the date on
 * is what makes the next reminder.
 */
class InventoryDue extends Command
{
    protected $signature = 'hrm:inventory-due';

    protected $description = 'Напомнить о единицах, у которых подошёл срок инвентаризации';

    public function handle(): int
    {
        // Whoever may change the «Состояние и инвентаризация» block at all. Which
        // of them a given unit concerns is asked unit by unit below: the right
        // reaches only as far as the part of the fleet its holder sees.
        $keepers = Recipients::holding(EquipmentAccess::blockPermission('state'));
        $sent = 0;

        if ($keepers->isEmpty()) {
            $this->info('Напоминаний: 0');

            return self::SUCCESS;
        }

        Equipment::query()
            ->inService()
            ->whereNotNull('next_inventory_at')
            ->whereDate('next_inventory_at', '<=', Carbon::today())
            ->orderBy('id')
            ->each(function (Equipment $unit) use ($keepers, &$sent) {
                // Who has already been told about this unit and this date.
                $told = DatabaseNotification::query()
                    ->where('type', InventoryDueNotification::class)
                    ->where('notifiable_type', (new User)->getMorphClass())
                    ->where('data->reminder', InventoryDueNotification::reminderKey($unit))
                    ->pluck('notifiable_id')
                    ->all();

                $keepers
                    ->reject(fn (User $keeper) => in_array($keeper->id, $told))
                    ->filter(fn (User $keeper) => EquipmentAccess::canEditBlock($keeper, $unit, 'state'))
                    ->each(function (User $keeper) use ($unit, &$sent) {
                        $keeper->notify(new InventoryDueNotification($unit));
                        $sent++;
                    });
            });

        $this->info("Напоминаний: {$sent}");

        return self::SUCCESS;
    }
}
