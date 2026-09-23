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

    /**
     * Several at once, for the step of the "new colleague" wizard that asks
     * where they studied: it collects the rows and files them in one go.
     */
    public function storeMany(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate(
            ['records' => ['present', 'array', 'max:20'], ...$this->prefixed('records.*.')],
            attributes: $this->attributes('records.*.'),
        );

        $employee->educations()->createMany($data['records']);

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
        return $request->validate($this->prefixed(), attributes: $this->attributes());
    }

    /**
     * One record's rules, optionally under a prefix so the same ones cover a
     * list of records as well as a single one.
     *
     * @return array<string, mixed>
     */
    private function prefixed(string $prefix = ''): array
    {
        // "gte:started_year" has to point at the sibling in the same record.
        $started = "{$prefix}started_year";

        return [
            "{$prefix}institution" => ['required', 'string', 'max:200'],
            "{$prefix}faculty" => ['required', 'string', 'max:150'],
            "{$prefix}specialty" => ['required', 'string', 'max:150'],
            $started => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
            // Empty while still studying; a diploma cannot predate enrolment.
            "{$prefix}graduated_year" => ['nullable', 'integer', "gte:{$started}", 'max:'.(date('Y') + 10)],
            "{$prefix}diploma_number" => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $prefix = ''): array
    {
        return [
            "{$prefix}institution" => 'учебное заведение',
            "{$prefix}faculty" => 'факультет',
            "{$prefix}specialty" => 'специальность',
            "{$prefix}started_year" => 'год поступления',
            "{$prefix}graduated_year" => 'год окончания',
            "{$prefix}diploma_number" => 'номер диплома',
        ];
    }
}
