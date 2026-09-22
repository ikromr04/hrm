<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * The route already requires manage-employees.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Phones arrive as typed ("90 123 45 67", "+992 90 123-45-67") and are
     * stored in E.164, like the rest of the data.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(collect(['phone', 'sos_phone'])
            ->filter(fn (string $key) => filled($this->input($key)))
            ->mapWithKeys(fn (string $key) => [$key => self::normalizePhone((string) $this->input($key))])
            ->all());
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // A local 9-digit mobile number gets the Tajik country code.
        return '+'.(strlen($digits) === 9 ? '992'.$digits : $digits);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $employee */
        $employee = $this->route('employee');
        $date = ['nullable', 'date', 'before_or_equal:today'];
        $phone = ['nullable', 'string', 'regex:/^\+\d{11,15}$/'];

        return [
            'surname' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'patronymic' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee)],

            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name'), $this->adminRoleGuard()],
            'positions' => ['present', 'array'],
            'positions.*' => ['integer', 'distinct', Rule::exists('positions', 'id')],
            'departments' => ['present', 'array'],
            'departments.*' => ['integer', 'distinct', Rule::exists('departments', 'id')],

            'hired_at' => $date,
            'birth_date' => $date,
            'birth_place' => ['nullable', 'string', 'max:150'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'citizenship' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', Rule::in(['single', 'married'])],
            'home_address' => ['nullable', 'string', 'max:255'],
            'phone' => $phone,
            'sos_phone' => $phone,

            'passport_series' => ['nullable', 'string', 'max:10'],
            'passport_number' => ['nullable', 'string', 'max:20'],
            'passport_issued_at' => $date,
            'passport_issued_by' => ['nullable', 'string', 'max:150'],

            'children' => ['present', 'array', 'max:20'],
            'children.*.full_name' => ['required', 'string', 'max:150'],
            'children.*.birth_date' => $date,
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
            'email' => 'почта',
            'roles.*' => 'позиция',
            'positions.*' => 'должность',
            'departments.*' => 'отдел',
            'hired_at' => 'начало работы',
            'birth_date' => 'дата рождения',
            'birth_place' => 'место рождения',
            'nationality' => 'национальность',
            'citizenship' => 'гражданство',
            'marital_status' => 'семейное положение',
            'home_address' => 'домашний адрес',
            'phone' => 'телефон',
            'sos_phone' => 'телефон SOS',
            'passport_series' => 'серия паспорта',
            'passport_number' => 'номер паспорта',
            'passport_issued_at' => 'дата выдачи',
            'passport_issued_by' => 'кем выдан',
            'children.*.full_name' => 'ФИО ребёнка',
            'children.*.birth_date' => 'дата рождения ребёнка',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Номер телефона указан неверно.',
            'sos_phone.regex' => 'Номер телефона указан неверно.',
        ];
    }
}
