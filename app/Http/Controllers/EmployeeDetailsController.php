<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateContactsRequest;
use App\Http\Requests\UpdatePersonalDataRequest;
use App\Models\Language;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Edits one block of the profile at a time, the way the page shows it: each
 * method takes just the fields of its own card.
 */
class EmployeeDetailsController extends Controller
{
    /** The name and sex live on the user, the rest of the plain fields on the details. */
    private const ON_USER = ['surname', 'name', 'patronymic', 'sex'];

    private const RELATIONS = ['roles', 'positions', 'departments'];

    /**
     * The "Основные данные" card.
     */
    public function personal(UpdatePersonalDataRequest $request, User $employee): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($employee, $data) {
            $employee->update(Arr::only($data, self::ON_USER));
            $employee->details()->updateOrCreate([], Arr::except($data, [...self::ON_USER, ...self::RELATIONS]));

            $employee->syncRoles($data['roles']);
            $employee->positions()->sync($data['positions']);
            // Departments the employee stays in keep their head flag.
            $employee->departments()->sync($data['departments']);
        });

        return back();
    }

    /**
     * The "Паспорт" card. Every field is optional: a new hire may be on file
     * before their document is.
     */
    public function passport(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'passport_series' => ['nullable', 'string', 'max:10'],
            'passport_number' => ['nullable', 'string', 'max:20'],
            'passport_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'passport_issued_by' => ['nullable', 'string', 'max:150'],
        ], attributes: [
            'passport_series' => 'серия паспорта',
            'passport_number' => 'номер паспорта',
            'passport_issued_at' => 'дата выдачи',
            'passport_issued_by' => 'кем выдан',
        ]);

        $employee->details()->updateOrCreate([], $data);

        return back();
    }

    /**
     * The "Контакты" card. The phones are normalised to E.164 by the request;
     * the email is the sign-in address and so lives on the user.
     */
    public function contacts(UpdateContactsRequest $request, User $employee): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($employee, $data) {
            $employee->update(['email' => $data['email']]);
            $employee->details()->updateOrCreate([], Arr::except($data, 'email'));
        });

        return back();
    }

    /**
     * The hire date, shown bare at the top of the sidebar. Tenure is counted
     * from it, so a date in the future would read as negative service.
     */
    public function employment(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'hired_at' => ['nullable', 'date', 'before_or_equal:today'],
        ], attributes: ['hired_at' => 'начало работы']);

        $employee->details()->updateOrCreate([], $data);

        return back();
    }

    /**
     * The "Знание языков" card. Unlike the other cards here, languages are
     * public: every colleague sees them.
     */
    public function languages(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'languages' => ['present', 'array'],
            // Each language once, so the list cannot hold two levels for one.
            'languages.*.id' => ['required', 'integer', 'distinct', Rule::exists('languages', 'id')],
            'languages.*.level' => ['required', Rule::in(Language::LEVELS)],
        ], attributes: [
            'languages.*.id' => 'язык',
            'languages.*.level' => 'уровень',
        ]);

        $employee->languages()->sync(collect($data['languages'])->mapWithKeys(fn (array $l) => [$l['id'] => ['level' => $l['level']]]));

        return back();
    }

    /**
     * The "Семья" card: marital status, the spouse and the children. The
     * children are replaced wholesale, the way the dialog edits them.
     */
    public function family(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'marital_status' => ['nullable', Rule::in(['single', 'married'])],
            'spouse_name' => ['nullable', 'string', 'max:150'],
            'spouse_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            // Only false carries meaning here ("HR says there are none"); the
            // true case is derived from the rows below, so the two cannot drift.
            'has_children' => ['nullable', 'boolean'],
            'children' => ['present', 'array', 'max:20'],
            'children.*.full_name' => ['required', 'string', 'max:150'],
            'children.*.birth_date' => ['nullable', 'date', 'before_or_equal:today'],
        ], attributes: [
            'marital_status' => 'семейное положение',
            'spouse_name' => 'ФИО супруга',
            'spouse_birth_date' => 'дата рождения супруга',
            'children.*.full_name' => 'ФИО ребёнка',
            'children.*.birth_date' => 'дата рождения ребёнка',
        ]);

        // "Не указано" and "детей нет" look the same in an empty list, so the
        // flag keeps them apart; rows on file always mean there are children.
        $hasChildren = match (true) {
            $data['children'] !== [] => true,
            ($data['has_children'] ?? null) === false => false,
            default => null,
        };

        DB::transaction(function () use ($employee, $data, $hasChildren) {
            $employee->details()->updateOrCreate([], [...Arr::except($data, 'children'), 'has_children' => $hasChildren]);

            $employee->children()->delete();
            $employee->children()->createMany(array_map(
                fn (array $child) => ['full_name' => $child['full_name'], 'birth_date' => $child['birth_date'] ?? null],
                $data['children'],
            ));
        });

        return back();
    }
}
