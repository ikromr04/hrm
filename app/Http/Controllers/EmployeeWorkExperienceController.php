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
        $validator = Validator::make($request->all(), [
            'organization' => ['required', 'string', 'max:200'],
            'position' => ['required', 'string', 'max:150'],
            'country' => ['required', 'string', 'max:100'],
            'started_month' => ['required', 'integer', 'between:1,12'],
            'started_year' => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
            // Both empty while the person still works there; one without the
            // other would be half a date.
            'ended_month' => ['nullable', 'required_with:ended_year', 'integer', 'between:1,12'],
            'ended_year' => ['nullable', 'required_with:ended_month', 'integer', 'min:1950', 'max:'.date('Y')],
        ], attributes: [
            'organization' => 'организация',
            'position' => 'должность',
            'country' => 'страна',
            'started_month' => 'месяц вступления',
            'started_year' => 'год вступления',
            'ended_month' => 'месяц ухода',
            'ended_year' => 'год ухода',
        ]);

        $validator->after(function ($validator) use ($request) {
            // Months counted from year 0, so the two dates compare as plain numbers.
            $month = fn ($year, $m) => is_numeric($year) && is_numeric($m) ? (int) $year * 12 + (int) $m : null;
            $now = (int) date('Y') * 12 + (int) date('n');

            $start = $month($request->input('started_year'), $request->input('started_month'));
            $end = $month($request->input('ended_year'), $request->input('ended_month'));

            if ($start !== null && $start > $now) {
                $validator->errors()->add('started_year', 'Дата вступления не может быть в будущем.');
            }
            if ($end !== null && $end > $now) {
                $validator->errors()->add('ended_year', 'Дата ухода не может быть в будущем.');
            }
            if ($start !== null && $end !== null && $end < $start) {
                $validator->errors()->add('ended_year', 'Дата ухода раньше даты вступления.');
            }
        });

        return $validator->validate();
    }
}
