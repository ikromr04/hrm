import InputError from '@/components/input-error';
import { MultiSelect } from '@/components/multi-select';
import { PersonAvatar } from '@/components/person-avatar';
import { statusTones } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import {
    formatPhone,
    languageLevelLabels,
    languageLevels,
    maritalLabels,
    monthNames,
    sexLabels,
    type LanguageLevel,
    type Marital,
    type Sex,
} from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { useId, type FormEventHandler, type ReactNode } from 'react';

type EmployeeForm = {
    surname: string;
    name: string;
    patronymic: string;
    sex: Sex;
    email: string;
    /** Role names ("Позиция"). */
    roles: string[];
    /** Position ids ("Должность"). */
    positions: number[];
    departments: number[];
    hired_at: string;
    birth_date: string;
    birth_place: string;
    nationality: string;
    citizenship: string;
    marital_status: Marital | '';
    home_address: string;
    phone: string;
    sos_phone: string;
    sos_contact: string;
    passport_series: string;
    passport_number: string;
    passport_issued_at: string;
    passport_issued_by: string;
    children: { full_name: string; birth_date: string }[];
    /** Every field as text; years are parsed on the server. */
    educations: Record<'institution' | 'faculty' | 'specialty' | 'started_year' | 'graduated_year' | 'diploma_number', string>[];
    languages: { id: number; level: LanguageLevel }[];
    /** Every field as text; empty end month and year mean "still works there". */
    work_experiences: Record<'organization' | 'position' | 'country' | 'started_month' | 'started_year' | 'ended_month' | 'ended_year', string>[];
};

interface Props {
    employee: EmployeeForm & { id: number; status: 'active' | 'transferred' | 'fired'; head_of: number[] };
    options: {
        roles: { name: string; title: string }[];
        positions: { id: number; name: string }[];
        departments: { id: number; name: string; depth: number }[];
        nationalities: string[];
        citizenships: string[];
        languages: { id: number; name: string }[];
        /** Countries already used, as suggestions. */
        countries: string[];
    };
}

function Section({ title, children, className }: { title: string; children: ReactNode; className?: string }) {
    return (
        <Card className={cn('flex flex-col gap-4 rounded-xl px-6 py-5', className)}>
            <h2 className="text-base font-semibold">{title}</h2>
            {children}
        </Card>
    );
}

/** A labelled control with its validation error; the label points at the first control inside. */
function Field({ label, error, wide, children }: { label: string; error?: string; wide?: boolean; children: (id: string) => ReactNode }) {
    const id = useId();

    return (
        <div className={cn('flex min-w-0 flex-col gap-1.5', wide && 'col-span-2')}>
            <Label htmlFor={id} className="text-muted-foreground text-[13px] font-normal">
                {label}
            </Label>
            {children(id)}
            <InputError message={error} />
        </div>
    );
}

function Fields({ children }: { children: ReactNode }) {
    return <div className="grid grid-cols-2 gap-x-4 gap-y-4">{children}</div>;
}

export default function EditEmployee({ employee, options }: Props) {
    const shortName = `${employee.surname} ${employee.name}`;

    const form = useForm<EmployeeForm>({
        surname: employee.surname,
        name: employee.name,
        patronymic: employee.patronymic,
        sex: employee.sex,
        email: employee.email,
        roles: employee.roles,
        positions: employee.positions,
        departments: employee.departments,
        hired_at: employee.hired_at,
        birth_date: employee.birth_date,
        birth_place: employee.birth_place,
        nationality: employee.nationality,
        citizenship: employee.citizenship,
        marital_status: employee.marital_status,
        home_address: employee.home_address,
        phone: employee.phone && formatPhone(employee.phone),
        sos_phone: employee.sos_phone && formatPhone(employee.sos_phone),
        sos_contact: employee.sos_contact,
        passport_series: employee.passport_series,
        passport_number: employee.passport_number,
        passport_issued_at: employee.passport_issued_at,
        passport_issued_by: employee.passport_issued_by,
        children: employee.children,
        educations: employee.educations,
        languages: employee.languages,
        work_experiences: employee.work_experiences,
    });

    const { data, setData, processing } = form;
    const errors = form.errors as Record<string, string | undefined>;
    /** The first error for a list and its items ("roles", "roles.0", ...). */
    const listError = (key: string) => errors[key] ?? Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1];

    const text = (key: { [K in keyof EmployeeForm]: EmployeeForm[K] extends string ? K : never }[keyof EmployeeForm]) => ({
        value: data[key] as string,
        onChange: (event: React.ChangeEvent<HTMLInputElement>) => setData(key, event.target.value as never),
    });

    const setLanguage = (index: number, patch: Partial<EmployeeForm['languages'][number]>) =>
        setData(
            'languages',
            data.languages.map((language, i) => (i === index ? { ...language, ...patch } : language)),
        );
    // Each language once: a new row takes the first one not picked yet.
    const unusedLanguage = options.languages.find((language) => !data.languages.some((l) => l.id === language.id));

    const setEducation = (index: number, patch: Partial<EmployeeForm['educations'][number]>) =>
        setData(
            'educations',
            data.educations.map((education, i) => (i === index ? { ...education, ...patch } : education)),
        );
    const emptyEducation = { institution: '', faculty: '', specialty: '', started_year: '', graduated_year: '', diploma_number: '' };

    const setJob = (index: number, patch: Partial<EmployeeForm['work_experiences'][number]>) =>
        setData(
            'work_experiences',
            data.work_experiences.map((job, i) => (i === index ? { ...job, ...patch } : job)),
        );
    const emptyJob = { organization: '', position: '', country: '', started_month: '', started_year: '', ended_month: '', ended_year: '' };
    const countrySuggestions = [...new Set(['Таджикистан', ...options.countries])];

    const setChild = (index: number, patch: Partial<EmployeeForm['children'][number]>) =>
        setData(
            'children',
            data.children.map((child, i) => (i === index ? { ...child, ...patch } : child)),
        );

    // Leaving a department also ends leading it.
    const lostHeadships = options.departments.filter((d) => employee.head_of.includes(d.id) && !data.departments.includes(d.id));

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('employees.update', employee.id), { preserveScroll: true });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Сотрудники', href: '/employees' },
        { title: shortName, href: `/employees/${employee.id}` },
        { title: 'Редактирование', href: `/employees/${employee.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Редактирование · ${shortName}`} />

            <form onSubmit={submit} className="flex flex-1 flex-col gap-4 p-3 md:px-5 md:py-4">
                <Card className="flex flex-col gap-4 rounded-xl p-6 sm:flex-row sm:items-center">
                    <PersonAvatar name={`${data.name} ${data.surname}`} className="size-16 text-[22px]" />
                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                        <h1 className="truncate text-2xl leading-tight font-bold tracking-tight">
                            {[data.surname, data.name, data.patronymic].filter(Boolean).join(' ') || 'Без имени'}
                        </h1>
                        <p className="text-muted-foreground text-sm">Редактирование профиля</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('employees.show', employee.id)}>Отмена</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </div>
                </Card>

                <div className="grid items-start gap-4 lg:grid-cols-3">
                    <div className="flex flex-col gap-4">
                        <Section title="Основное">
                            <Fields>
                                <Field label="Фамилия" error={errors.surname}>
                                    {(id) => <Input id={id} required {...text('surname')} />}
                                </Field>
                                <Field label="Имя" error={errors.name}>
                                    {(id) => <Input id={id} required {...text('name')} />}
                                </Field>
                                <Field label="Отчество" error={errors.patronymic}>
                                    {(id) => <Input id={id} {...text('patronymic')} />}
                                </Field>
                                <Field label="Пол" error={errors.sex}>
                                    {(id) => (
                                        <Select value={data.sex} onValueChange={(value) => setData('sex', value as Sex)}>
                                            <SelectTrigger id={id}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {(Object.keys(sexLabels) as Sex[]).map((sex) => (
                                                    <SelectItem key={sex} value={sex}>
                                                        {sexLabels[sex]}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </Field>
                                <Field label="Почта" error={errors.email} wide>
                                    {(id) => <Input id={id} type="email" required autoComplete="off" {...text('email')} />}
                                </Field>
                            </Fields>
                        </Section>

                        <Section title="Личные данные">
                            <Fields>
                                <Field label="Дата рождения" error={errors.birth_date}>
                                    {(id) => <Input id={id} type="date" {...text('birth_date')} />}
                                </Field>
                                <Field label="Место рождения" error={errors.birth_place}>
                                    {(id) => <Input id={id} {...text('birth_place')} />}
                                </Field>
                                <Field label="Национальность" error={errors.nationality}>
                                    {(id) => (
                                        <>
                                            <Input id={id} list={`${id}-list`} {...text('nationality')} />
                                            <datalist id={`${id}-list`}>
                                                {options.nationalities.map((value) => (
                                                    <option key={value} value={value} />
                                                ))}
                                            </datalist>
                                        </>
                                    )}
                                </Field>
                                <Field label="Гражданство" error={errors.citizenship}>
                                    {(id) => (
                                        <>
                                            <Input id={id} list={`${id}-list`} {...text('citizenship')} />
                                            <datalist id={`${id}-list`}>
                                                {options.citizenships.map((value) => (
                                                    <option key={value} value={value} />
                                                ))}
                                            </datalist>
                                        </>
                                    )}
                                </Field>
                                <Field label="Семейное положение" error={errors.marital_status} wide>
                                    {(id) => (
                                        <Select
                                            value={data.marital_status || 'none'}
                                            onValueChange={(value) => setData('marital_status', value === 'none' ? '' : (value as Marital))}
                                        >
                                            <SelectTrigger id={id}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Не указано</SelectItem>
                                                {(Object.keys(maritalLabels[data.sex]) as Marital[]).map((status) => (
                                                    <SelectItem key={status} value={status}>
                                                        {maritalLabels[data.sex][status]}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </Field>
                            </Fields>
                        </Section>
                    </div>

                    <div className="flex flex-col gap-4">
                        <Section title="Работа">
                            <Fields>
                                <Field label="Позиция" error={listError('roles')} wide>
                                    {(id) => (
                                        <MultiSelect
                                            id={id}
                                            options={options.roles.map((role) => ({ value: role.name, label: role.title }))}
                                            value={data.roles}
                                            onChange={(value) => setData('roles', value)}
                                            chipClassName={statusTones.info}
                                        />
                                    )}
                                </Field>
                                <Field label="Должность" error={listError('positions')} wide>
                                    {(id) => (
                                        <MultiSelect
                                            id={id}
                                            options={options.positions.map((position) => ({ value: position.id, label: position.name }))}
                                            value={data.positions}
                                            onChange={(value) => setData('positions', value)}
                                            chipClassName={statusTones.success}
                                        />
                                    )}
                                </Field>
                                <Field label="Отдел" error={listError('departments')} wide>
                                    {(id) => (
                                        <>
                                            <MultiSelect
                                                id={id}
                                                options={options.departments.map((d) => ({ value: d.id, label: d.name, depth: d.depth }))}
                                                value={data.departments}
                                                onChange={(value) => setData('departments', value)}
                                            />
                                            {lostHeadships.length > 0 && (
                                                <p className="text-[13px] text-[#9A4A06] dark:text-[#F8C471]">
                                                    Сотрудник перестанет быть руководителем: {lostHeadships.map((d) => d.name).join(', ')}.
                                                </p>
                                            )}
                                        </>
                                    )}
                                </Field>
                                <Field label="Начало работы" error={errors.hired_at}>
                                    {(id) => <Input id={id} type="date" {...text('hired_at')} />}
                                </Field>
                            </Fields>
                        </Section>

                        <Section title="Языки">
                            {data.languages.length === 0 && <p className="text-muted-foreground text-sm">Не указаны</p>}
                            {data.languages.map((language, index) => (
                                <div key={index} className="flex items-start gap-2">
                                    <div className="grid flex-1 grid-cols-[1fr_9.5rem] gap-2">
                                        <div className="flex flex-col gap-1">
                                            <Select value={String(language.id)} onValueChange={(value) => setLanguage(index, { id: Number(value) })}>
                                                <SelectTrigger aria-label="Язык">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent className="max-h-72">
                                                    {options.languages
                                                        .filter(
                                                            (option) => option.id === language.id || !data.languages.some((l) => l.id === option.id),
                                                        )
                                                        .map((option) => (
                                                            <SelectItem key={option.id} value={String(option.id)}>
                                                                {option.name}
                                                            </SelectItem>
                                                        ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors[`languages.${index}.id`]} />
                                        </div>
                                        <div className="flex flex-col gap-1">
                                            <Select
                                                value={language.level}
                                                onValueChange={(value) => setLanguage(index, { level: value as LanguageLevel })}
                                            >
                                                <SelectTrigger aria-label="Уровень">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {languageLevels.map((level) => (
                                                        <SelectItem key={level} value={level}>
                                                            {languageLevelLabels[level]}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors[`languages.${index}.level`]} />
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="text-muted-foreground shrink-0"
                                        aria-label="Убрать язык"
                                        onClick={() =>
                                            setData(
                                                'languages',
                                                data.languages.filter((_, i) => i !== index),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                className="self-start"
                                disabled={!unusedLanguage}
                                onClick={() =>
                                    unusedLanguage && setData('languages', [...data.languages, { id: unusedLanguage.id, level: 'intermediate' }])
                                }
                            >
                                <Plus />
                                Добавить язык
                            </Button>
                        </Section>

                        <Section title="Контакты">
                            <Fields>
                                <Field label="Телефон" error={errors.phone}>
                                    {(id) => <Input id={id} type="tel" placeholder="+992 90 123 45 67" {...text('phone')} />}
                                </Field>
                                <Field label="Телефон SOS" error={errors.sos_phone}>
                                    {(id) => <Input id={id} type="tel" placeholder="+992 90 123 45 67" {...text('sos_phone')} />}
                                </Field>
                                <Field label="Чей это телефон" error={errors.sos_contact} wide>
                                    {(id) => <Input id={id} placeholder="Мама — Дилором" {...text('sos_contact')} />}
                                </Field>
                                <Field label="Домашний адрес" error={errors.home_address} wide>
                                    {(id) => <Input id={id} {...text('home_address')} />}
                                </Field>
                            </Fields>
                        </Section>
                    </div>

                    <div className="flex flex-col gap-4">
                        <Section title="Паспорт">
                            <Fields>
                                <Field label="Серия" error={errors.passport_series}>
                                    {(id) => <Input id={id} {...text('passport_series')} />}
                                </Field>
                                <Field label="Номер" error={errors.passport_number}>
                                    {(id) => <Input id={id} className="tabular-nums" {...text('passport_number')} />}
                                </Field>
                                <Field label="Дата выдачи" error={errors.passport_issued_at}>
                                    {(id) => <Input id={id} type="date" {...text('passport_issued_at')} />}
                                </Field>
                                <Field label="Кем выдан" error={errors.passport_issued_by} wide>
                                    {(id) => <Input id={id} {...text('passport_issued_by')} />}
                                </Field>
                            </Fields>
                        </Section>

                        <Section title="Дети">
                            {data.children.length === 0 && <p className="text-muted-foreground text-sm">Нет</p>}
                            {data.children.map((child, index) => (
                                <div key={index} className="flex items-start gap-2 border-t pt-4 first-of-type:border-t-0 first-of-type:pt-0">
                                    <div className="grid flex-1 grid-cols-[1fr_9.5rem] gap-2">
                                        <div className="flex flex-col gap-1">
                                            <Input
                                                aria-label="ФИО ребёнка"
                                                placeholder="ФИО"
                                                required
                                                value={child.full_name}
                                                onChange={(event) => setChild(index, { full_name: event.target.value })}
                                            />
                                            <InputError message={errors[`children.${index}.full_name`]} />
                                        </div>
                                        <div className="flex flex-col gap-1">
                                            <Input
                                                aria-label="Дата рождения ребёнка"
                                                type="date"
                                                value={child.birth_date}
                                                onChange={(event) => setChild(index, { birth_date: event.target.value })}
                                            />
                                            <InputError message={errors[`children.${index}.birth_date`]} />
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="text-muted-foreground shrink-0"
                                        aria-label="Убрать ребёнка"
                                        onClick={() =>
                                            setData(
                                                'children',
                                                data.children.filter((_, i) => i !== index),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                className="self-start"
                                onClick={() => setData('children', [...data.children, { full_name: '', birth_date: '' }])}
                            >
                                <Plus />
                                Добавить ребёнка
                            </Button>
                        </Section>
                    </div>

                    <Section title="Образование" className="lg:col-span-3">
                        {data.educations.length === 0 && <p className="text-muted-foreground text-sm">Не указано</p>}
                        {data.educations.map((education, index) => (
                            <div key={index} className="flex items-start gap-2 border-t pt-4 first-of-type:border-t-0 first-of-type:pt-0">
                                <div className="grid flex-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-[2fr_1.5fr_1.5fr_6rem_6rem_9rem]">
                                    <Field label="Учебное заведение" error={errors[`educations.${index}.institution`]}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                required
                                                value={education.institution}
                                                onChange={(e) => setEducation(index, { institution: e.target.value })}
                                            />
                                        )}
                                    </Field>
                                    <Field label="Факультет" error={errors[`educations.${index}.faculty`]}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                required
                                                value={education.faculty}
                                                onChange={(e) => setEducation(index, { faculty: e.target.value })}
                                            />
                                        )}
                                    </Field>
                                    <Field label="Специальность" error={errors[`educations.${index}.specialty`]}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                required
                                                value={education.specialty}
                                                onChange={(e) => setEducation(index, { specialty: e.target.value })}
                                            />
                                        )}
                                    </Field>
                                    <Field label="Поступление" error={errors[`educations.${index}.started_year`]}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                type="number"
                                                inputMode="numeric"
                                                min={1950}
                                                max={new Date().getFullYear()}
                                                placeholder="Год"
                                                required
                                                value={education.started_year}
                                                onChange={(e) => setEducation(index, { started_year: e.target.value })}
                                            />
                                        )}
                                    </Field>
                                    <Field label="Окончание" error={errors[`educations.${index}.graduated_year`]}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                type="number"
                                                inputMode="numeric"
                                                min={1950}
                                                max={new Date().getFullYear() + 10}
                                                placeholder="Учится"
                                                value={education.graduated_year}
                                                onChange={(e) => setEducation(index, { graduated_year: e.target.value })}
                                            />
                                        )}
                                    </Field>
                                    <Field label="№ диплома" error={errors[`educations.${index}.diploma_number`]}>
                                        {(id) => (
                                            <Input
                                                id={id}
                                                value={education.diploma_number}
                                                onChange={(e) => setEducation(index, { diploma_number: e.target.value })}
                                            />
                                        )}
                                    </Field>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-muted-foreground mt-6 shrink-0"
                                    aria-label="Убрать образование"
                                    onClick={() =>
                                        setData(
                                            'educations',
                                            data.educations.filter((_, i) => i !== index),
                                        )
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            className="self-start"
                            onClick={() => setData('educations', [...data.educations, emptyEducation])}
                        >
                            <Plus />
                            Добавить образование
                        </Button>
                    </Section>

                    <Section title="Трудовая деятельность" className="lg:col-span-3">
                        {data.work_experiences.length === 0 && <p className="text-muted-foreground text-sm">Не указана</p>}
                        <datalist id="work-countries">
                            {countrySuggestions.map((country) => (
                                <option key={country} value={country} />
                            ))}
                        </datalist>
                        {data.work_experiences.map((job, index) => {
                            const error = (field: string) => errors[`work_experiences.${index}.${field}`];

                            return (
                                <div key={index} className="flex items-start gap-2 border-t pt-4 first-of-type:border-t-0 first-of-type:pt-0">
                                    <div className="grid flex-1 gap-x-4 gap-y-3 md:grid-cols-3 xl:grid-cols-[2fr_1.5fr_1fr_13rem_13rem]">
                                        <Field label="Организация" error={error('organization')}>
                                            {(id) => (
                                                <Input
                                                    id={id}
                                                    required
                                                    value={job.organization}
                                                    onChange={(e) => setJob(index, { organization: e.target.value })}
                                                />
                                            )}
                                        </Field>
                                        <Field label="Должность" error={error('position')}>
                                            {(id) => (
                                                <Input
                                                    id={id}
                                                    required
                                                    value={job.position}
                                                    onChange={(e) => setJob(index, { position: e.target.value })}
                                                />
                                            )}
                                        </Field>
                                        <Field label="Страна" error={error('country')}>
                                            {(id) => (
                                                <Input
                                                    id={id}
                                                    required
                                                    list="work-countries"
                                                    value={job.country}
                                                    onChange={(e) => setJob(index, { country: e.target.value })}
                                                />
                                            )}
                                        </Field>
                                        <Field label="Вступление" error={error('started_month') ?? error('started_year')}>
                                            {(id) => (
                                                <div className="grid grid-cols-[1fr_5.5rem] gap-2">
                                                    <Select
                                                        value={job.started_month}
                                                        onValueChange={(value) => setJob(index, { started_month: value })}
                                                    >
                                                        <SelectTrigger id={id} aria-label="Месяц вступления">
                                                            <SelectValue placeholder="Месяц" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {monthNames.map((name, m) => (
                                                                <SelectItem key={name} value={String(m + 1)}>
                                                                    {name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <Input
                                                        type="number"
                                                        inputMode="numeric"
                                                        aria-label="Год вступления"
                                                        placeholder="Год"
                                                        min={1950}
                                                        max={new Date().getFullYear()}
                                                        required
                                                        value={job.started_year}
                                                        onChange={(e) => setJob(index, { started_year: e.target.value })}
                                                    />
                                                </div>
                                            )}
                                        </Field>
                                        <Field label="Уход" error={error('ended_month') ?? error('ended_year')}>
                                            {(id) => (
                                                <div className="grid grid-cols-[1fr_5.5rem] gap-2">
                                                    <Select
                                                        value={job.ended_month || 'none'}
                                                        onValueChange={(value) =>
                                                            setJob(
                                                                index,
                                                                value === 'none' ? { ended_month: '', ended_year: '' } : { ended_month: value },
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger id={id} aria-label="Месяц ухода">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">Работает сейчас</SelectItem>
                                                            {monthNames.map((name, m) => (
                                                                <SelectItem key={name} value={String(m + 1)}>
                                                                    {name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <Input
                                                        type="number"
                                                        inputMode="numeric"
                                                        aria-label="Год ухода"
                                                        placeholder="Год"
                                                        min={1950}
                                                        max={new Date().getFullYear()}
                                                        disabled={!job.ended_month}
                                                        required={!!job.ended_month}
                                                        value={job.ended_year}
                                                        onChange={(e) => setJob(index, { ended_year: e.target.value })}
                                                    />
                                                </div>
                                            )}
                                        </Field>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="text-muted-foreground mt-6 shrink-0"
                                        aria-label="Убрать место работы"
                                        onClick={() =>
                                            setData(
                                                'work_experiences',
                                                data.work_experiences.filter((_, i) => i !== index),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            );
                        })}
                        <Button
                            type="button"
                            variant="outline"
                            className="self-start"
                            onClick={() => setData('work_experiences', [...data.work_experiences, emptyJob])}
                        >
                            <Plus />
                            Добавить место работы
                        </Button>
                    </Section>
                </div>
            </form>
        </AppLayout>
    );
}
