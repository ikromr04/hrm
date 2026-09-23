<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserWorkExperience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * One previous job at a time, the way the tab lists them: adding a job should
 * not rewrite the ones already on file.
 */
class EmployeeWorkExperienceController extends Controller
{
    public function store(Request $request, User $employee): RedirectResponse
    {
        $employee->workExperiences()->create($this->validated($request));

        return back();
    }

    /**
     * Several at once, for the step of the "new colleague" wizard that asks
     * where they worked before: it collects the rows and files them in one go.
     */
    public function storeMany(Request $request, User $employee): RedirectResponse
    {
        $records = (array) $request->input('records', []);

        $validator = Validator::make(
            $request->all(),
            ['records' => ['present', 'array', 'max:20'], ...$this->rules('records.*.')],
            attributes: $this->attributes('records.*.'),
        );

        foreach (array_keys($records) as $index) {
            $this->checkDates($validator, (array) $records[$index], "records.{$index}.");
        }

        $employee->workExperiences()->createMany($validator->validate()['records']);

        return back();
    }

    public function update(Request $request, User $employee, UserWorkExperience $experience): RedirectResponse
    {
        $this->belongsTo($employee, $experience);
        $experience->update($this->validated($request));

        return back();
    }

    public function destroy(User $employee, UserWorkExperience $experience): RedirectResponse
    {
        $this->belongsTo($employee, $experience);
        $experience->delete();

        return back();
    }

    /**
     * The record id travels beside an employee id, so the two must agree:
     * otherwise one person's URL would reach another person's record.
     */
    private function belongsTo(User $employee, UserWorkExperience $experience): void
    {
        abort_unless($experience->user_id === $employee->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), $this->rules(), attributes: $this->attributes());

        $this->checkDates($validator, $request->all());

        return $validator->validate();
    }

    /**
     * One record's rules, optionally under a prefix so the same ones cover a
     * list of records as well as a single one.
     *
     * @return array<string, mixed>
     */
    private function rules(string $prefix = ''): array
    {
        return [
            "{$prefix}organization" => ['required', 'string', 'max:200'],
            "{$prefix}position" => ['required', 'string', 'max:150'],
            "{$prefix}country" => ['required', 'string', 'max:100'],
            "{$prefix}started_month" => ['required', 'integer', 'between:1,12'],
            "{$prefix}started_year" => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
            // Both empty while the person still works there; one without the
            // other would be half a date.
            "{$prefix}ended_month" => ['nullable', "required_with:{$prefix}ended_year", 'integer', 'between:1,12'],
            "{$prefix}ended_year" => ['nullable', "required_with:{$prefix}ended_month", 'integer', 'min:1950', 'max:'.date('Y')],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $prefix = ''): array
    {
        return [
            "{$prefix}organization" => 'организация',
            "{$prefix}position" => 'должность',
            "{$prefix}country" => 'страна',
            "{$prefix}started_month" => 'месяц вступления',
            "{$prefix}started_year" => 'год вступления',
            "{$prefix}ended_month" => 'месяц ухода',
            "{$prefix}ended_year" => 'год ухода',
        ];
    }

    /**
     * Neither date in the future, and the leaving date after the joining one.
     * Rules cannot say this on their own: the two halves of a date are
     * separate fields, so they are compared here.
     *
     * @param  array<string, mixed>  $record
     */
    private function checkDates(\Illuminate\Validation\Validator $validator, array $record, string $prefix = ''): void
    {
        $validator->after(function ($validator) use ($record, $prefix) {
            // Months counted from year 0, so the two dates compare as plain numbers.
            $month = fn ($year, $m) => is_numeric($year) && is_numeric($m) ? (int) $year * 12 + (int) $m : null;
            $now = (int) date('Y') * 12 + (int) date('n');

            $start = $month($record['started_year'] ?? null, $record['started_month'] ?? null);
            $end = $month($record['ended_year'] ?? null, $record['ended_month'] ?? null);

            if ($start !== null && $start > $now) {
                $validator->errors()->add("{$prefix}started_year", 'Дата вступления не может быть в будущем.');
            }
            if ($end !== null && $end > $now) {
                $validator->errors()->add("{$prefix}ended_year", 'Дата ухода не может быть в будущем.');
            }
            if ($start !== null && $end !== null && $end < $start) {
                $validator->errors()->add("{$prefix}ended_year", 'Дата ухода раньше даты вступления.');
            }
        });
    }
}
