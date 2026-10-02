<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Access;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * What the company's own installation starts with: the rights, the system
 * administrator's position and the one account that holds it — and nothing
 * else.
 *
 * No positions, departments, job titles, languages or equipment categories:
 * the company keeps those lists itself in «Справочники» and starts them empty,
 * rather than from somebody's draft it would first have to clean up. No people
 * and no hardware either: those are the demo (UserSeeder).
 *
 * It is run on the first day and again after every update, so it is harmless
 * the second time. The rights are brought in line with the code on every run —
 * an update that adds a right needs exactly this — and the administrator is
 * asked for only while there is none.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // The only thing here that ever removes anything: a right that has left
        // the catalogue in the code, which no page can show any more.
        $this->call(PermissionSeeder::class);

        // The one position the system cannot work without. It may be renamed in
        // the directory, so an existing one is left exactly as it is.
        Role::firstOrCreate(
            ['name' => Access::SOLE_ROLE, 'guard_name' => 'web'],
            ['title' => RoleSeeder::ROLES[Access::SOLE_ROLE]],
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->systemAdministrator();
    }

    /**
     * The one account. A seeder has no name or password of its own to give it,
     * so it asks whoever is at the terminal, through the command that makes
     * that account; where there is nobody to ask — cron, a test — it says how
     * to do it instead of inventing a password.
     */
    private function systemAdministrator(): void
    {
        if ($this->command === null || User::role(Access::SOLE_ROLE)->exists()) {
            return;
        }

        if ($this->canAsk()) {
            $this->command->call('hrm:sysadmin');

            return;
        }

        $this->command->getOutput()->writeln('  Системного администратора пока нет. Создайте его: php artisan hrm:sysadmin');
    }

    /** Whether somebody is sitting at a terminal to answer. */
    private function canAsk(): bool
    {
        return ! $this->command->option('no-interaction')
            && ! app()->runningUnitTests()
            && defined('STDIN')
            && stream_isatty(STDIN);
    }
}
