<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserEducation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * One place of study at a time. The other private blocks are saved whole, but
 * a degree is a record of its own: adding one should not rewrite the rest.
 */
class EmployeeEducationController extends Controller
{
    public function store(Request $request, User $employee): RedirectResponse
    {
        $employee->educations()->create($this->validated($request));

        return back();
    }

    public function update(Request $request, User $employee, UserEducation $education): RedirectResponse
    {
        $this->belongsTo($employee, $education);
        $education->update($this->validated($request));

        return back();
    }

    public function destroy(User $employee, UserEducation $education): RedirectResponse
    {
        $this->belongsTo($employee, $education);
        $education->delete();

        return back();
    }

    /**
     * The record id travels beside an employee id, so the two must agree:
     * otherwise one person's URL would reach another person's record.
     */
    private function belongsTo(User $employee, UserEducation $education): void
    {
        abort_unless($education->user_id === $employee->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'institution' => ['required', 'string', 'max:200'],
            'faculty' => ['required', 'string', 'max:150'],
            'specialty' => ['required', 'string', 'max:150'],
            'started_year' => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
            // Empty while still studying; a diploma cannot predate enrolment.
            'graduated_year' => ['nullable', 'integer', 'gte:started_year', 'max:'.(date('Y') + 10)],
            'diploma_number' => ['nullable', 'string', 'max:50'],
        ], attributes: [
            'institution' => 'учебное заведение',
            'faculty' => 'факультет',
            'specialty' => 'специальность',
            'started_year' => 'год поступления',
            'graduated_year' => 'год окончания',
            'diploma_number' => 'номер диплома',
        ]);
    }
}
