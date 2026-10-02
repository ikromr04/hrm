<?php

namespace App\Notifications;

use App\Models\User;

/**
 * A colleague was put on the books. Told to the others who add people, so two
 * of them do not enter the same person twice.
 */
class EmployeeAdded extends InAppNotification
{
    public function __construct(private readonly User $employee) {}

    protected function kind(): string
    {
        return 'employee.added';
    }

    protected function text(): string
    {
        return "Добавлен сотрудник: {$this->employee->surname} {$this->employee->name}";
    }

    protected function target(): array
    {
        return ['type' => 'employee', 'id' => $this->employee->id];
    }
}
