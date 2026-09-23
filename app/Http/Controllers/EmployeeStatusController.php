<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Transfer, fire, restore and delete employees.
 *
 * Transferred and fired people keep their record and move to their own
 * lists; deleting removes the person and all their data for good.
 */
class EmployeeStatusController extends Controller
{
    public function transfer(Request $request, User $employee): RedirectResponse
    {
        $this->leave($request, $employee, 'transferred', noteRequired: true);

        return back();
    }

    public function fire(Request $request, User $employee): RedirectResponse
    {
        $this->leave($request, $employee, 'fired', noteRequired: false);

        return back();
    }

    public function restore(User $employee): RedirectResponse
    {
        $employee->update(['status' => 'active', 'status_changed_at' => null, 'status_note' => null]);

        return back();
    }

    public function destroy(Request $request, User $employee): RedirectResponse
    {
        abort_if($request->user()->is($employee), 403, 'Нельзя удалить самого себя.');

        // Deleted from the list, going back keeps the filters; deleted from the
        // employee's own profile, there is no page to go back to.
        $fromProfile = url()->previous() === route('employees.show', $employee);

        // Details, children, roles, positions and departments go with the row.
        $employee->delete();

        return $fromProfile ? to_route('employees.index') : back();
    }

    private function leave(Request $request, User $employee, string $status, bool $noteRequired): void
    {
        abort_if($request->user()->is($employee), 403, 'Нельзя изменить статус самому себе.');
        abort_unless($employee->isActive(), 422, 'Сотрудник уже не работает.');

        $data = $request->validate([
            'date' => ['required', 'date'],
            'note' => [$noteRequired ? 'required' : 'nullable', 'string', 'max:255'],
        ], attributes: [
            'date' => 'дата',
            'note' => $status === 'transferred' ? 'куда переведён' : 'причина',
        ]);

        $employee->update([
            'status' => $status,
            'status_changed_at' => $data['date'],
            'status_note' => $data['note'] ?? null,
        ]);
    }
}
