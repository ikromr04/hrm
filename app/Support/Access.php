<?php

namespace App\Support;

/**
 * Every right the system knows about.
 *
 * A right is a section plus what it lets a person do there, which is how the
 * access table reads: one column group per section, one column per action. The
 * list lives in code rather than in the database because the sections and the
 * actions are the application itself — positions come and go, but "Оборудование:
 * выдача" only exists as long as there is a page that hands equipment out.
 *
 * Administrators and system administrators are not on this list: they pass every
 * check through Gate::before, including rights added later.
 */
final class Access
{
    /** The sections, in the order the table shows them. */
    public const SECTIONS = [
        'employees' => 'Сотрудники',
        'equipment' => 'Оборудование',
        'departments' => 'Структура компании',
        'directories' => 'Справочники',
    ];

    /**
     * key => [what it is called, what it actually opens up].
     *
     * The key is the section, a dot and the action, so the section a right
     * belongs to is read off the key itself.
     *
     * @var array<string, array{string, string}>
     */
    public const PERMISSIONS = [
        'employees.view' => ['Просмотр', 'Список сотрудников и их карточки — без личных данных.'],
        'employees.manage' => ['Изменение', 'Новый сотрудник, правка карточек, аватар, оборудование на руках.'],
        'employees.private' => ['Личные данные', 'Паспорт, семья, адрес, телефоны, дата рождения и приёма.'],
        'employees.status' => ['Перевод и увольнение', 'Перевод, увольнение и восстановление сотрудника.'],
        'employees.delete' => ['Удаление', 'Удаление сотрудника вместе со всем, что на него записано.'],

        'equipment.view' => ['Просмотр', 'Список техники и карточки единиц.'],
        'equipment.manage' => ['Изменение', 'Постановка на баланс, выдача, возврат, списание, обслуживание.'],
        'equipment.journal' => ['Журнал операций', 'Что происходило с техникой за период.'],
        'equipment.delete' => ['Удаление', 'Снятие единицы с учёта вместе с её журналом.'],

        'departments.view' => ['Просмотр', 'Структура компании и состав отделов.'],

        'directories.view' => ['Просмотр', 'Позиции, должности, отделы, языки и категории техники.'],
        'directories.manage' => ['Изменение', 'Добавление, переименование и удаление записей справочников.'],
    ];

    /**
     * What a position gets when it is first created: everybody who works here
     * may look around. Anything beyond looking is handed out deliberately, on
     * the access page.
     *
     * @var list<string>
     */
    public const DEFAULTS = ['employees.view', 'equipment.view', 'departments.view'];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function has(string $key): bool
    {
        return isset(self::PERMISSIONS[$key]);
    }

    /** The section a right belongs to: "employees.view" => "employees". */
    public static function section(string $key): string
    {
        return explode('.', $key)[0];
    }

    /**
     * The catalogue as the access page reads it: the sections in order, each
     * with its rights.
     *
     * @return list<array{key: string, title: string, rights: list<array{key: string, title: string, hint: string}>}>
     */
    public static function tree(): array
    {
        $tree = [];

        foreach (self::SECTIONS as $section => $title) {
            $rights = [];

            foreach (self::PERMISSIONS as $key => [$action, $hint]) {
                if (self::section($key) === $section) {
                    $rights[] = ['key' => $key, 'title' => $action, 'hint' => $hint];
                }
            }

            $tree[] = ['key' => $section, 'title' => $title, 'rights' => $rights];
        }

        return $tree;
    }
}
