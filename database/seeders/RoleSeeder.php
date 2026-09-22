<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Access roles: key used in code => title shown in the UI.
     *
     * "admin" needs no permissions: Gate::before in AppServiceProvider lets
     * admins through every check.
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
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $name => $title) {
            Role::updateOrCreate(['name' => $name, 'guard_name' => 'web'], ['title' => $title]);
        }
    }
}
