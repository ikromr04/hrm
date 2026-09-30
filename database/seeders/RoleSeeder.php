<?php

namespace Database\Seeders;

use App\Support\Access;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Positions: key used in code => title shown in the UI.
     *
     * All of them are ordinary, «Администратор» included: each starts with the
     * rights in Access::DEFAULTS and is given the rest on the access page. The one
     * exception is "sysadmin", which needs no rights of its own — Gate::before in
     * AppServiceProvider lets that single account through every check.
     */
    public const ROLES = [
        'department-head' => 'Руководитель Департамента',
        'division-head' => 'Руководитель Отдела',
        'mrb' => 'МРБ',
        'kpg' => 'КПГ',
        'lead-specialist' => 'Ведущий специалист',
        'technical-analyst' => 'Технический аналитик',
        'translator' => 'Переводчик',
        'graphic-designer' => 'Графический дизайнер',
        'scientific-editor' => 'Научный Редактор',
        'designer-scientific-editor' => 'Дизайнер - Научный редактор',
        'analyst' => 'Аналитик',
        'support-staff' => 'Поддерживающий Персонал',
        'dossier-specialist' => 'Специалист по составлению регистрационного досье',
        'dossier-registrar' => 'Регистратор Досье',
        'copywriter' => 'Копирайтер',
        'smm-specialist' => 'СММ специалист',
        'video-maker' => 'Видеомейкер',
        'intern' => 'Стажер',
        'scientific-analyst' => 'Научный аналитик',
        'webmaster' => 'Веб-мастер',
        'project-manager' => 'Проектный менеджер',
        'specialist' => 'Специалист',
        'junior-specialist' => 'Младший специалист',
        'admin' => 'Администратор',
        'sysadmin' => 'Системный администратор',
    ];

    public function run(): void
    {
        // The rights have to exist before a position can be given any.
        $this->call(PermissionSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $name => $title) {
            $role = Role::updateOrCreate(['name' => $name, 'guard_name' => 'web'], ['title' => $title]);

            // The system administrator holds no rights at all: it passes every
            // check through Gate::before, and a list beside it would only lie.
            if ($name === Access::SOLE_ROLE) {
                continue;
            }

            // Looking around comes with the job; anything more is handed out
            // deliberately on the access page, so a re-seed leaves it alone.
            $role->givePermissionTo(Access::defaults());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
