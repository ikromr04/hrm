<?php

namespace App\Support;

use App\Models\User;

/**
 * What a card says about a person, block by block and line by line, and who may
 * read or change which.
 *
 * A position is given the lines it needs rather than "personal data" as a lump:
 * the head of a department has business knowing a telephone number and none at
 * all knowing a passport. Reading and changing are asked separately — plenty of
 * people should see a passport and nobody but HR should retype one.
 *
 * Each line is two rights, so the whole machinery around rights applies to them
 * unchanged: a position carries them, a personal exception overrules them, and
 * an administrator passes every check.
 *
 * The surname and the name are not on the list. A list of colleagues whose names
 * are hidden is a list of blank rows, and a card with no name on it answers
 * nothing; so those two are always readable and the rest is decided.
 */
final class EmployeeFields
{
    /**
     * The blocks a card is read in, in the order it shows them.
     *
     * key => [title, whether the block is chosen line by line]. A block that is
     * not — a passport, a family — is one answer: either it is open or it is not,
     * because half a passport is no use to anybody.
     *
     * @var array<string, array{string, bool}>
     */
    public const GROUPS = [
        'main' => ['Основные данные', true],
        'passport' => ['Паспорт', false],
        'contacts' => ['Контакты', true],
        'family' => ['Семья', false],
        'languages' => ['Знание языков', false],
        'employment' => ['Начало и стаж работы', false],
        'education' => ['Образование', false],
        'experience' => ['Трудовая деятельность', false],
        'equipment' => ['Оборудование', false],
    ];

    /**
     * key => [group, what it is called on the card].
     *
     * The key names the thing a person reads, not the column behind it: the
     * passport block shows "Серия и номер" as one line, so it is one field.
     *
     * @var array<string, array{string, string}>
     */
    public const FIELDS = [
        'patronymic' => ['main', 'Отчество'],
        'sex' => ['main', 'Пол'],
        'birth_date' => ['main', 'Дата рождения'],
        'birth_place' => ['main', 'Место рождения'],
        'citizenship' => ['main', 'Гражданство'],
        'nationality' => ['main', 'Национальность'],
        'home_address' => ['main', 'Домашний адрес'],
        'roles' => ['main', 'Позиция'],
        'positions' => ['main', 'Должность'],
        'departments' => ['main', 'Отдел'],

        'passport_number' => ['passport', 'Серия и номер'],
        'passport_issued_at' => ['passport', 'Дата выдачи'],
        'passport_issued_by' => ['passport', 'Кем выдан'],

        'email' => ['contacts', 'Электронная почта'],
        'phone' => ['contacts', 'Телефон'],
        'sos_phone' => ['contacts', 'Телефон SOS'],

        'marital_status' => ['family', 'Семейное положение'],
        'spouse' => ['family', 'Супруг и его дата рождения'],
        'children' => ['family', 'Дети'],

        'languages' => ['languages', 'Языки и уровень'],

        'hired_at' => ['employment', 'Начало работы и стаж'],

        'educations' => ['education', 'Учебные заведения'],

        'work_experiences' => ['experience', 'Места работы'],

        'equipment' => ['equipment', 'Оборудование на руках'],
    ];

    /**
     * What a position may read the moment it is created: everything that used to
     * be on a card for any colleague, and nothing that used to be private.
     *
     * @var list<string>
     */
    public const PUBLIC_FIELDS = ['patronymic', 'sex', 'roles', 'positions', 'departments', 'email', 'languages'];

    /**
     * The fields kept beside the account rather than on it: the row a card loads
     * separately, and the lists hanging off it. Nothing here is read unless one
     * of these is open, which saves loading it at all.
     *
     * @var list<string>
     */
    public const PRIVATE_FIELDS = [
        'birth_date', 'birth_place', 'citizenship', 'nationality', 'home_address',
        'passport_number', 'passport_issued_at', 'passport_issued_by',
        'phone', 'sos_phone',
        'marital_status', 'spouse', 'children',
        'educations', 'work_experiences', 'hired_at', 'equipment',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::FIELDS);
    }

    /** The right to read a line: "birth_date" => "employees.field.birth_date". */
    public static function permission(string $field): string
    {
        return "employees.field.{$field}";
    }

    /** The right to change it: "birth_date" => "employees.edit.birth_date". */
    public static function editPermission(string $field): string
    {
        return "employees.edit.{$field}";
    }

    /**
     * The gate behind a block, which is what a form that saves the whole block
     * is guarded by: "passport" => "employees.edit.block.passport".
     */
    public static function blockGate(string $group): string
    {
        return "employees.edit.block.{$group}";
    }

    /**
     * Every line as a right, both ways round, for the catalogue the seeder and
     * the pages read.
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            ...array_map(self::permission(...), self::keys()),
            ...array_map(self::editPermission(...), self::keys()),
        ];
    }

    /**
     * The fields of a position's default set, as rights. Reading only: what a
     * position may change is decided deliberately.
     *
     * @return list<string>
     */
    public static function defaultPermissions(): array
    {
        return array_map(self::permission(...), self::PUBLIC_FIELDS);
    }

    /** The fields of one block, in the order the card shows them. */
    public static function ofGroup(string $group): array
    {
        return array_keys(array_filter(self::FIELDS, fn (array $field) => $field[0] === $group));
    }

    /**
     * The catalogue as a dialog reads it: the blocks in order, each with its
     * lines and both rights behind them.
     *
     * @return list<array{key: string, title: string, expandable: bool, fields: list<array{key: string, title: string, permission: string, editPermission: string}>}>
     */
    public static function tree(): array
    {
        $tree = [];

        foreach (self::GROUPS as $group => [$title, $expandable]) {
            $fields = [];

            foreach (self::FIELDS as $key => [$in, $label]) {
                if ($in === $group) {
                    $fields[] = [
                        'key' => $key,
                        'title' => $label,
                        'permission' => self::permission($key),
                        'editPermission' => self::editPermission($key),
                    ];
                }
            }

            $tree[] = ['key' => $group, 'title' => $title, 'expandable' => $expandable, 'fields' => $fields];
        }

        return $tree;
    }

    /**
     * Which lines this viewer may read on that person's card.
     *
     * Everybody reads their own card whole: it is their passport and their own
     * telephone number. Beyond that it is line by line.
     *
     * @return list<string>
     */
    public static function visibleTo(User $viewer, ?User $employee = null): array
    {
        if ($employee !== null && $viewer->is($employee)) {
            return self::keys();
        }

        return array_values(array_filter(self::keys(), fn (string $field) => $viewer->can(self::permission($field))));
    }

    /**
     * Which lines this viewer may change. Reading is a condition of changing —
     * nobody retypes what they cannot see — so this is never wider than what
     * visibleTo() gives, own card included.
     *
     * @return list<string>
     */
    public static function editableBy(User $viewer, ?User $employee = null): array
    {
        $visible = self::visibleTo($viewer, $employee);

        return array_values(array_filter(
            self::keys(),
            fn (string $field) => in_array($field, $visible, true) && $viewer->can(self::editPermission($field)),
        ));
    }

    /** Whether anything kept beside the account is readable at all. */
    public static function anyPrivateVisibleTo(User $viewer): bool
    {
        foreach (self::PRIVATE_FIELDS as $field) {
            if ($viewer->can(self::permission($field))) {
                return true;
            }
        }

        return false;
    }
}
