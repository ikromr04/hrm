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
        // Открывается ли раздел вообще. Ставится не галочкой, а самим выбором
        // полей: позиция, которая не читает ни одной строки карточки, в разделе
        // ничего и не найдёт.
        'employees.view' => ['Просмотр', 'Список сотрудников и их карточки. Что именно видно в карточке — выбирается по строкам.'],

        'employees.transfer' => ['Перевод', 'Перевести сотрудника в другой отдел или на другую должность.'],
        'employees.fire' => ['Увольнение', 'Уволить сотрудника и восстановить уволенного.'],
        'employees.delete' => ['Удаление', 'Удалить сотрудника вместе со всем, что на него записано.'],

        'equipment.view' => ['Просмотр', 'Список техники и карточки единиц.'],
        'equipment.manage' => ['Изменение', 'Постановка на баланс, выдача, возврат, списание, обслуживание.'],
        'equipment.journal' => ['Журнал операций', 'Что происходило с техникой за период.'],
        'equipment.delete' => ['Удаление', 'Снятие единицы с учёта вместе с её журналом.'],

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
    public const DEFAULTS = ['employees.view', 'equipment.view'];

    /**
     * The same, with the fields a card used to show any colleague: a position is
     * created able to see what was never private in the first place.
     *
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [...self::DEFAULTS, ...EmployeeFields::defaultPermissions()];
    }

    /**
     * Every right there is: the sections, and then one per field of an employee
     * card — those are rights like any other, so positions, personal exceptions
     * and Gate::before apply to them unchanged.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [...array_keys(self::PERMISSIONS), ...EmployeeFields::permissions()];
    }

    /**
     * The rights a table of positions against rights shows as columns: the
     * sections only. The lines of a card are four dozen rights and belong in a
     * dialog behind a counter, not in a column each.
     *
     * @return list<string>
     */
    public static function sectionKeys(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    /**
     * The three things one does to a colleague rather than to a line of their
     * card, which is what the "Действия" column of the table holds.
     *
     * @var list<string>
     */
    public const ACTIONS = ['employees.transfer', 'employees.fire', 'employees.delete'];

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
