<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "Основные данные" card of the profile: the name and personal facts, plus
 * the three directories the employee is filed under.
 */
class UpdatePersonalDataRequest extends FormRequest
{
    /**
     * The route already requires manage-employees.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'patronymic' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'citizenship' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'home_address' => ['nullable', 'string', 'max:255'],

            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name'), $this->adminRoleGuard()],
            'positions' => ['present', 'array'],
            'positions.*' => ['integer', 'distinct', Rule::exists('positions', 'id')],
            'departments' => ['present', 'array'],
            'departments.*' => ['integer', 'distinct', Rule::exists('departments', 'id')],
        ];
    }

    /**
     * Only an admin hands out or takes away admin rights, and an admin
     * cannot take them from themselves and lock everyone out.
     */
    private function adminRoleGuard(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if ($value === 'admin' && ! $this->user()->hasRole('admin')) {
                $fail('Назначать администратора может только администратор.');
            }
        };
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        /** @var User $employee */
        $employee = $this->route('employee');

        return [function ($validator) use ($employee) {
            $roles = (array) $this->input('roles', []);
            $hadAdmin = $employee->hasRole('admin');
            $keepsAdmin = in_array('admin', $roles, true);

            if ($hadAdmin && ! $keepsAdmin && ! $this->user()->hasRole('admin')) {
                $validator->errors()->add('roles', 'Снять администратора может только администратор.');
            }

            if ($hadAdmin && ! $keepsAdmin && $employee->is($this->user())) {
                $validator->errors()->add('roles', 'Нельзя снять с себя роль администратора.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'surname' => 'фамилия',
            'name' => 'имя',
            'patronymic' => 'отчество',
            'sex' => 'пол',
            'birth_date' => 'дата рождения',
            'birth_place' => 'место рождения',
            'citizenship' => 'гражданство',
            'nationality' => 'национальность',
            'home_address' => 'домашний адрес',
            'roles' => 'позиция',
            'positions' => 'должность',
            'departments' => 'отдел',
        ];
    }
}
