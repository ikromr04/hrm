import InputError from '@/components/input-error';
import { MultiSelect } from '@/components/multi-select';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { languageLevelLabels, languageLevels, monthNames, sexLabels, type LanguageLevel, type Sex } from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight, LoaderCircle, Plus, Trash2, UserPlus } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';

interface Options {
    roles: { name: string; title: string }[];
    positions: { id: number; name: string }[];
    /** The department tree flattened, parents first. */
    departments: { id: number; name: string; depth: number }[];
    languages: { id: number; name: string }[];
    nationalities: string[];
    citizenships: string[];
    /** Hardware free to hand out, for the last step. */
    stock: { id: number; name: string; inventory_number: string }[];
}

/** Whom the first step created; every later one works on this person. */
type NewEmployee = { id: number; name: string; email: string };

const steps = [
    { title: 'Основные данные', note: 'Кто это' },
    { title: 'Контакты и языки', note: 'Как связаться, чем владеет' },
    { title: 'Паспорт и семья', note: 'Документ и близкие' },
    { title: 'Образование', note: 'Где учился' },
    { title: 'Трудовая деятельность', note: 'Где работал раньше' },
    { title: 'Оборудование', note: 'Что выдаём на руки' },
] as const;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Сотрудники', href: '/employees' },
    { title: 'Новый сотрудник', href: '/employees/create' },
];

/** Errors come back as "records.0.institution"; a field shows its own. */
const at = (errors: Record<string, string | undefined>, key: string) =>
    errors[key] ?? Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1];

/* ------------------------------------------------------------------ pieces */

function Field({ label, error, children, className }: { label: string; error?: string; children: ReactNode; className?: string }) {
    return (
        <div className={cn('grid content-start gap-2', className)}>
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

/**
 * A step that keeps a list of records — degrees, jobs. Rows are added and
 * removed here and filed in one request when the step is left.
 */
function Records<T>({
    items,
    empty,
    addLabel,
    onAdd,
    onRemove,
    children,
}: {
    items: T[];
    empty: string;
    addLabel: string;
    onAdd: () => void;
    onRemove: (index: number) => void;
    children: (item: T, index: number) => ReactNode;
}) {
    return (
        <div className="flex flex-col gap-4">
            {items.length === 0 && <p className="text-muted-foreground text-sm">{empty}</p>}

            {items.map((item, index) => (
                <div key={index} className="rounded-lg border p-4 pt-3">
                    <div className="mb-3 flex items-center justify-between">
                        <span className="text-muted-foreground text-[13px] font-medium">Запись {index + 1}</span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="text-muted-foreground -mr-2 size-7"
                            aria-label={`Убрать запись ${index + 1}`}
                            onClick={() => onRemove(index)}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </div>
                    {children(item, index)}
                </div>
            ))}

            <Button type="button" variant="outline" size="sm" className="self-start" onClick={onAdd}>
                <Plus />
                {addLabel}
            </Button>
        </div>
    );
}

/* ------------------------------------------------------------- record types */

type Education = {
    institution: string;
    faculty: string;
    specialty: string;
    started_year: string;
    graduated_year: string;
    diploma_number: string;
};

const blankEducation: Education = { institution: '', faculty: '', specialty: '', started_year: '', graduated_year: '', diploma_number: '' };

type Job = {
    organization: string;
    position: string;
    country: string;
    started_month: string;
    started_year: string;
    ended_month: string;
    ended_year: string;
};

const blankJob: Job = { organization: '', position: '', country: '', started_month: '', started_year: '', ended_month: '', ended_year: '' };

type Child = { full_name: string; birth_date: string };

type SpokenLanguage = { id: string; level: LanguageLevel };

/* -------------------------------------------------------------------- page */

/**
 * A new colleague, step by step: who they are, how to reach them, their
 * papers, their studies, their previous jobs and the hardware they are given.
 *
 * The first step creates them; every step after it fills part of the profile
 * in and may be left empty, which is how it is skipped — whatever is missed
 * here is edited later on the profile, card by card. It is a page rather than
 * a dialog so that half-finished work cannot be lost to a stray key.
 */
export default function CreateEmployee({ options }: { options: Options }) {
    const today = new Date().toISOString().slice(0, 10);
    const [step, setStep] = useState(0);
    const [employee, setEmployee] = useState<NewEmployee | null>(null);

    const main = useForm({
        surname: '',
        name: '',
        patronymic: '',
        sex: 'male' as Sex,
        birth_date: '',
        birth_place: '',
        citizenship: '',
        nationality: '',
        home_address: '',
        email: '',
        hired_at: today,
        roles: [] as string[],
        positions: [] as number[],
        departments: [] as number[],
        continue: true,
    });

    const contacts = useForm({ email: '', phone: '', sos_phone: '', sos_contact: '' });
    const languages = useForm({ languages: [] as SpokenLanguage[] });
    const passport = useForm({ passport_series: '', passport_number: '', passport_issued_at: '', passport_issued_by: '' });
    const family = useForm({
        marital_status: '',
        spouse_name: '',
        spouse_birth_date: '',
        has_children: '' as '' | 'yes' | 'no',
        children: [] as Child[],
    });
    const educations = useForm({ records: [] as Education[] });
    const jobs = useForm({ records: [] as Job[] });
    const equipment = useForm({ equipment: [] as number[], issued_at: today });

    const forms = [main, contacts, languages, passport, family, educations, jobs, equipment];
    const busy = forms.some((form) => form.processing);

    // Leaving with a half-filled step would throw the work away, so the browser
    // asks first. Once the last step is done there is nothing left to lose.
    const dirty = forms.some((form) => form.isDirty) && step < steps.length;
    useEffect(() => {
        if (!dirty) return;

        const warn = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', warn);

        return () => window.removeEventListener('beforeunload', warn);
    }, [dirty]);

    const toProfile = () => {
        if (employee) router.visit(route('employees.show', employee.id));
        else router.visit(route('employees.index'));
    };

    /**
     * Back to a blank first step, for whoever is filing a whole intake at
     * once. The colleague just finished is saved and left behind.
     */
    const startOver = () => {
        forms.forEach((form) => {
            form.clearErrors();
            form.reset();
        });

        setEmployee(null);
        setStep(0);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    /**
     * Where a step leads once it is filed: on to the next one, off to the new
     * colleague's profile, or back to a blank form for the one after them.
     */
    type After = 'next' | 'profile' | 'again';

    const land = (after: After, to: number) => {
        if (after === 'again') startOver();
        else if (after === 'profile') toProfile();
        else setStep(to);
    };

    /** Filing a step keeps the page where it is and its state intact. */
    const go = (after: After, to: number) => ({ preserveScroll: true, preserveState: true, onSuccess: () => land(after, to) }) as const;

    /**
     * Each step files what it holds, then hands over. Stopping after any one
     * of them is allowed: the colleague is on the books from the first step,
     * and whatever was skipped is filled in later from their profile.
     */
    const next = (after: After = 'next') => {
        if (step === 0) {
            main.post(route('employees.store'), {
                preserveScroll: true,
                preserveState: true,
                // Who was just created rides back on the page, flashed by the
                // controller: the later steps all work on that person.
                onSuccess: (fresh) => {
                    const created = (fresh.props as unknown as SharedData).flash?.employee as NewEmployee | undefined;
                    if (!created) return;

                    setEmployee(created);
                    contacts.setData('email', created.email);

                    // "Again" has nothing to go back to yet, so it starts over
                    // from the person just filed rather than from this form.
                    if (after === 'again') startOver();
                    else if (after === 'profile') router.visit(route('employees.show', created.id));
                    else setStep(1);
                },
            });

            return;
        }

        if (!employee) return;

        if (step === 1) {
            // Contacts and languages are two cards, so two requests; the step
            // is only left once both have gone through.
            contacts.put(route('employees.contacts', employee.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    languages.transform((data) => ({
                        languages: data.languages
                            .filter((spoken) => spoken.id !== '')
                            .map((spoken) => ({ id: Number(spoken.id), level: spoken.level })),
                    }));

                    languages.put(route('employees.languages', employee.id), go(after, 2));
                },
            });

            return;
        }

        if (step === 2) {
            passport.put(route('employees.passport', employee.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    family.transform((data) => ({
                        marital_status: data.marital_status || null,
                        spouse_name: data.spouse_name,
                        spouse_birth_date: data.spouse_birth_date || null,
                        has_children: data.has_children === '' ? null : data.has_children === 'yes',
                        children: data.children.filter((child) => child.full_name.trim() !== ''),
                    }));

                    family.put(route('employees.family', employee.id), go(after, 3));
                },
            });

            return;
        }

        if (step === 3) {
            educations.post(route('employees.educations.many', employee.id), go(after, 4));

            return;
        }

        if (step === 4) {
            jobs.post(route('employees.experiences.many', employee.id), go(after, 5));

            return;
        }

        // The last step: "next" has nowhere further to go, so it finishes.
        equipment.post(route('employees.equipment.store', employee.id), go(after === 'next' ? 'profile' : after, step));
    };

    const setRecord = <T,>(form: { data: { records: T[] }; setData: (key: 'records', value: T[]) => void }, index: number, patch: Partial<T>) =>
        form.setData(
            'records',
            form.data.records.map((record, position) => (position === index ? { ...record, ...patch } : record)),
        );

    const dropRecord = <T,>(form: { data: { records: T[] }; setData: (key: 'records', value: T[]) => void }, index: number) =>
        form.setData(
            'records',
            form.data.records.filter((_, position) => position !== index),
        );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Новый сотрудник" />

            <div className="flex flex-1 flex-col gap-5 p-3 md:px-5 md:py-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-xl font-semibold tracking-tight">Новый сотрудник</h1>
                        <p className="text-muted-foreground text-sm">
                            {employee
                                ? `${employee.name} · шаг ${step + 1} из ${steps.length}`
                                : 'Пароль сгенерируется сам и придёт сотруднику на почту.'}
                        </p>
                    </div>

                    <Button variant="ghost" onClick={toProfile}>
                        {employee ? 'Закончить позже' : 'Отмена'}
                    </Button>
                </div>

                <div className="grid gap-5 lg:grid-cols-[18rem_1fr] lg:items-start">
                    {/* The road ahead: what is done, where you are, what is left. */}
                    <Card className="flex flex-col gap-1 rounded-xl p-3">
                        <ol className="flex flex-col gap-0.5">
                            {steps.map((item, index) => {
                                const done = index < step;
                                const current = index === step;
                                // Going back is allowed; skipping ahead is not.
                                const reachable = done && employee !== null;

                                return (
                                    <li key={item.title}>
                                        <button
                                            type="button"
                                            disabled={!reachable && !current}
                                            aria-current={current ? 'step' : undefined}
                                            onClick={() => reachable && setStep(index)}
                                            className={cn(
                                                'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors',
                                                current && 'bg-muted',
                                                reachable && 'hover:bg-accent',
                                                !reachable && !current && 'cursor-default',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                                    done
                                                        ? 'bg-brand-soft text-brand-strong dark:bg-white/10 dark:text-[#C5E27A]'
                                                        : current
                                                          ? 'bg-foreground text-background'
                                                          : 'bg-muted text-muted-foreground',
                                                )}
                                            >
                                                {done ? <Check className="size-3.5" /> : index + 1}
                                            </span>
                                            <span className="flex min-w-0 flex-col">
                                                <span className={cn('truncate text-sm', current ? 'font-semibold' : 'font-medium')}>
                                                    {item.title}
                                                </span>
                                                <span className="text-muted-foreground truncate text-[13px]">{item.note}</span>
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ol>
                    </Card>

                    <Card className="rounded-xl p-6">
                        {/* noValidate: the server's rules are the real ones. */}
                        <form
                            noValidate
                            onSubmit={(event) => {
                                event.preventDefault();
                                next(step === steps.length - 1 ? 'profile' : 'next');
                            }}
                            className="flex flex-col gap-6"
                        >
                            <div className="flex flex-col gap-1">
                                <h2 className="text-base font-semibold">{steps[step].title}</h2>
                                {step > 0 && <p className="text-muted-foreground text-[13px]">Шаг необязательный — его можно пропустить.</p>}
                            </div>

                            {step === 0 && (
                                <div className="flex flex-col gap-4">
                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <Field label="Фамилия" error={main.errors.surname}>
                                            <Input
                                                value={main.data.surname}
                                                onChange={(event) => main.setData('surname', event.target.value)}
                                                aria-invalid={!!main.errors.surname}
                                            />
                                        </Field>
                                        <Field label="Имя" error={main.errors.name}>
                                            <Input
                                                value={main.data.name}
                                                onChange={(event) => main.setData('name', event.target.value)}
                                                aria-invalid={!!main.errors.name}
                                            />
                                        </Field>
                                        <Field label="Отчество" error={main.errors.patronymic}>
                                            <Input
                                                value={main.data.patronymic}
                                                onChange={(event) => main.setData('patronymic', event.target.value)}
                                                aria-invalid={!!main.errors.patronymic}
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <Field label="Пол" error={main.errors.sex}>
                                            <SearchableSelect
                                                value={main.data.sex}
                                                onChange={(value) => main.setData('sex', value as Sex)}
                                                options={[
                                                    { value: 'male', label: sexLabels.male },
                                                    { value: 'female', label: sexLabels.female },
                                                ]}
                                                invalid={!!main.errors.sex}
                                            />
                                        </Field>
                                        <Field label="Дата рождения" error={main.errors.birth_date}>
                                            <Input
                                                type="date"
                                                max={today}
                                                value={main.data.birth_date}
                                                onChange={(event) => main.setData('birth_date', event.target.value)}
                                                aria-invalid={!!main.errors.birth_date}
                                            />
                                        </Field>
                                        <Field label="Место рождения" error={main.errors.birth_place}>
                                            <Input
                                                value={main.data.birth_place}
                                                onChange={(event) => main.setData('birth_place', event.target.value)}
                                                placeholder="г. Худжанд"
                                                aria-invalid={!!main.errors.birth_place}
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <Field label="Гражданство" error={main.errors.citizenship}>
                                            <Input
                                                list="citizenships"
                                                value={main.data.citizenship}
                                                onChange={(event) => main.setData('citizenship', event.target.value)}
                                                aria-invalid={!!main.errors.citizenship}
                                            />
                                            <datalist id="citizenships">
                                                {options.citizenships.map((value) => (
                                                    <option key={value} value={value} />
                                                ))}
                                            </datalist>
                                        </Field>
                                        <Field label="Национальность" error={main.errors.nationality}>
                                            <Input
                                                list="nationalities"
                                                value={main.data.nationality}
                                                onChange={(event) => main.setData('nationality', event.target.value)}
                                                aria-invalid={!!main.errors.nationality}
                                            />
                                            <datalist id="nationalities">
                                                {options.nationalities.map((value) => (
                                                    <option key={value} value={value} />
                                                ))}
                                            </datalist>
                                        </Field>
                                        <Field label="Начало работы" error={main.errors.hired_at}>
                                            <Input
                                                type="date"
                                                max={today}
                                                value={main.data.hired_at}
                                                onChange={(event) => main.setData('hired_at', event.target.value)}
                                                aria-invalid={!!main.errors.hired_at}
                                            />
                                        </Field>
                                    </div>

                                    <Field label="Домашний адрес" error={main.errors.home_address}>
                                        <Input
                                            value={main.data.home_address}
                                            onChange={(event) => main.setData('home_address', event.target.value)}
                                            aria-invalid={!!main.errors.home_address}
                                        />
                                    </Field>

                                    <Field label="E-mail" error={main.errors.email}>
                                        <Input
                                            type="email"
                                            value={main.data.email}
                                            onChange={(event) => main.setData('email', event.target.value)}
                                            placeholder="name@evolet.tj"
                                            aria-invalid={!!main.errors.email}
                                        />
                                        <p className="text-muted-foreground text-[13px]">
                                            С этим адресом сотрудник входит в систему; туда же придёт пароль.
                                        </p>
                                    </Field>

                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <Field label="Позиция" error={at(main.errors, 'roles')}>
                                            <MultiSelect
                                                options={options.roles.map((role) => ({ value: role.name, label: role.title }))}
                                                value={main.data.roles}
                                                onChange={(value) => main.setData('roles', value)}
                                            />
                                        </Field>
                                        <Field label="Должность" error={at(main.errors, 'positions')}>
                                            <MultiSelect
                                                options={options.positions.map((position) => ({ value: position.id, label: position.name }))}
                                                value={main.data.positions}
                                                onChange={(value) => main.setData('positions', value)}
                                            />
                                        </Field>
                                        <Field label="Отдел" error={at(main.errors, 'departments')}>
                                            <MultiSelect
                                                options={options.departments.map((department) => ({
                                                    value: department.id,
                                                    label: department.name,
                                                    depth: department.depth,
                                                }))}
                                                value={main.data.departments}
                                                onChange={(value) => main.setData('departments', value)}
                                            />
                                        </Field>
                                    </div>
                                </div>
                            )}

                            {step === 1 && (
                                <div className="flex flex-col gap-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field label="E-mail" error={contacts.errors.email}>
                                            <Input
                                                type="email"
                                                value={contacts.data.email}
                                                onChange={(event) => contacts.setData('email', event.target.value)}
                                                aria-invalid={!!contacts.errors.email}
                                            />
                                        </Field>
                                        <Field label="Телефон" error={contacts.errors.phone}>
                                            <Input
                                                value={contacts.data.phone}
                                                onChange={(event) => contacts.setData('phone', event.target.value)}
                                                placeholder="+992 90 123 45 67"
                                                aria-invalid={!!contacts.errors.phone}
                                            />
                                        </Field>
                                        <Field label="Телефон SOS" error={contacts.errors.sos_phone}>
                                            <Input
                                                value={contacts.data.sos_phone}
                                                onChange={(event) => contacts.setData('sos_phone', event.target.value)}
                                                placeholder="+992 90 765 43 21"
                                                aria-invalid={!!contacts.errors.sos_phone}
                                            />
                                        </Field>
                                        <Field label="Чей это номер" error={contacts.errors.sos_contact}>
                                            <Input
                                                value={contacts.data.sos_contact}
                                                onChange={(event) => contacts.setData('sos_contact', event.target.value)}
                                                placeholder="Супруга, Нигина"
                                                aria-invalid={!!contacts.errors.sos_contact}
                                            />
                                        </Field>
                                    </div>

                                    <div className="flex flex-col gap-3 border-t pt-6">
                                        <Label>Знание языков</Label>
                                        <InputError message={at(languages.errors, 'languages')} />

                                        {languages.data.languages.length === 0 && (
                                            <p className="text-muted-foreground text-sm">Если сведений нет, шаг можно пропустить.</p>
                                        )}

                                        {languages.data.languages.map((spoken, index) => (
                                            <div key={index} className="flex items-center gap-2">
                                                <SearchableSelect
                                                    className="flex-1"
                                                    value={spoken.id}
                                                    onChange={(value) =>
                                                        languages.setData(
                                                            'languages',
                                                            languages.data.languages.map((item, position) =>
                                                                position === index ? { ...item, id: value } : item,
                                                            ),
                                                        )
                                                    }
                                                    options={options.languages.map((language) => ({
                                                        value: String(language.id),
                                                        label: language.name,
                                                    }))}
                                                    placeholder="Язык"
                                                    searchPlaceholder="Поиск языка"
                                                    empty="Язык не найден"
                                                />
                                                <SearchableSelect
                                                    className="w-48"
                                                    value={spoken.level}
                                                    onChange={(value) =>
                                                        languages.setData(
                                                            'languages',
                                                            languages.data.languages.map((item, position) =>
                                                                position === index ? { ...item, level: value as LanguageLevel } : item,
                                                            ),
                                                        )
                                                    }
                                                    options={languageLevels.map((level) => ({ value: level, label: languageLevelLabels[level] }))}
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-muted-foreground size-9 shrink-0"
                                                    aria-label={`Убрать язык ${index + 1}`}
                                                    onClick={() =>
                                                        languages.setData(
                                                            'languages',
                                                            languages.data.languages.filter((_, position) => position !== index),
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        ))}

                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="self-start"
                                            onClick={() =>
                                                languages.setData('languages', [...languages.data.languages, { id: '', level: 'intermediate' }])
                                            }
                                        >
                                            <Plus />
                                            Добавить язык
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {step === 2 && (
                                <div className="flex flex-col gap-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field label="Серия паспорта" error={passport.errors.passport_series}>
                                            <Input
                                                value={passport.data.passport_series}
                                                onChange={(event) => passport.setData('passport_series', event.target.value)}
                                                placeholder="A"
                                                aria-invalid={!!passport.errors.passport_series}
                                            />
                                        </Field>
                                        <Field label="Номер паспорта" error={passport.errors.passport_number}>
                                            <Input
                                                value={passport.data.passport_number}
                                                onChange={(event) => passport.setData('passport_number', event.target.value)}
                                                placeholder="01234567"
                                                aria-invalid={!!passport.errors.passport_number}
                                            />
                                        </Field>
                                        <Field label="Дата выдачи" error={passport.errors.passport_issued_at}>
                                            <Input
                                                type="date"
                                                max={today}
                                                value={passport.data.passport_issued_at}
                                                onChange={(event) => passport.setData('passport_issued_at', event.target.value)}
                                                aria-invalid={!!passport.errors.passport_issued_at}
                                            />
                                        </Field>
                                        <Field label="Кем выдан" error={passport.errors.passport_issued_by}>
                                            <Input
                                                value={passport.data.passport_issued_by}
                                                onChange={(event) => passport.setData('passport_issued_by', event.target.value)}
                                                aria-invalid={!!passport.errors.passport_issued_by}
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid gap-4 border-t pt-6 sm:grid-cols-2">
                                        <Field label="Семейное положение" error={family.errors.marital_status}>
                                            <SearchableSelect
                                                value={family.data.marital_status}
                                                onChange={(value) => family.setData('marital_status', value)}
                                                options={[
                                                    { value: 'single', label: 'Не женат / не замужем' },
                                                    { value: 'married', label: 'Женат / замужем' },
                                                ]}
                                                placeholder="Не указано"
                                                invalid={!!family.errors.marital_status}
                                            />
                                        </Field>
                                        <Field label="Дети">
                                            <SearchableSelect
                                                value={family.data.has_children}
                                                onChange={(value) => family.setData('has_children', value as 'yes' | 'no')}
                                                options={[
                                                    { value: 'yes', label: 'Есть' },
                                                    { value: 'no', label: 'Нет' },
                                                ]}
                                                placeholder="Не указано"
                                            />
                                        </Field>

                                        {family.data.marital_status === 'married' && (
                                            <>
                                                <Field label="ФИО супруга" error={family.errors.spouse_name}>
                                                    <Input
                                                        value={family.data.spouse_name}
                                                        onChange={(event) => family.setData('spouse_name', event.target.value)}
                                                        aria-invalid={!!family.errors.spouse_name}
                                                    />
                                                </Field>
                                                <Field label="Дата рождения супруга" error={family.errors.spouse_birth_date}>
                                                    <Input
                                                        type="date"
                                                        max={today}
                                                        value={family.data.spouse_birth_date}
                                                        onChange={(event) => family.setData('spouse_birth_date', event.target.value)}
                                                        aria-invalid={!!family.errors.spouse_birth_date}
                                                    />
                                                </Field>
                                            </>
                                        )}
                                    </div>

                                    {family.data.has_children === 'yes' && (
                                        <div className="flex flex-col gap-3">
                                            {family.data.children.map((child, index) => (
                                                <div key={index} className="flex items-end gap-2">
                                                    <Field
                                                        label="ФИО ребёнка"
                                                        className="flex-1"
                                                        error={at(family.errors, `children.${index}.full_name`)}
                                                    >
                                                        <Input
                                                            value={child.full_name}
                                                            onChange={(event) =>
                                                                family.setData(
                                                                    'children',
                                                                    family.data.children.map((item, position) =>
                                                                        position === index ? { ...item, full_name: event.target.value } : item,
                                                                    ),
                                                                )
                                                            }
                                                        />
                                                    </Field>
                                                    <Field label="Дата рождения" error={at(family.errors, `children.${index}.birth_date`)}>
                                                        <Input
                                                            type="date"
                                                            max={today}
                                                            value={child.birth_date}
                                                            onChange={(event) =>
                                                                family.setData(
                                                                    'children',
                                                                    family.data.children.map((item, position) =>
                                                                        position === index ? { ...item, birth_date: event.target.value } : item,
                                                                    ),
                                                                )
                                                            }
                                                        />
                                                    </Field>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-muted-foreground mb-[2px] size-9 shrink-0"
                                                        aria-label={`Убрать ребёнка ${index + 1}`}
                                                        onClick={() =>
                                                            family.setData(
                                                                'children',
                                                                family.data.children.filter((_, position) => position !== index),
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
                                            ))}

                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="self-start"
                                                onClick={() =>
                                                    family.setData('children', [...family.data.children, { full_name: '', birth_date: '' }])
                                                }
                                            >
                                                <Plus />
                                                Добавить ребёнка
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {step === 3 && (
                                <Records
                                    items={educations.data.records}
                                    empty="Если сведений нет, шаг можно пропустить."
                                    addLabel="Добавить образование"
                                    onAdd={() => educations.setData('records', [...educations.data.records, { ...blankEducation }])}
                                    onRemove={(index) => dropRecord(educations, index)}
                                >
                                    {(record, index) => (
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <Field
                                                label="Учебное заведение"
                                                className="sm:col-span-2"
                                                error={at(educations.errors, `records.${index}.institution`)}
                                            >
                                                <Input
                                                    value={record.institution}
                                                    onChange={(event) => setRecord(educations, index, { institution: event.target.value })}
                                                />
                                            </Field>
                                            <Field label="Факультет" error={at(educations.errors, `records.${index}.faculty`)}>
                                                <Input
                                                    value={record.faculty}
                                                    onChange={(event) => setRecord(educations, index, { faculty: event.target.value })}
                                                />
                                            </Field>
                                            <Field label="Специальность" error={at(educations.errors, `records.${index}.specialty`)}>
                                                <Input
                                                    value={record.specialty}
                                                    onChange={(event) => setRecord(educations, index, { specialty: event.target.value })}
                                                />
                                            </Field>
                                            <Field label="Год поступления" error={at(educations.errors, `records.${index}.started_year`)}>
                                                <Input
                                                    type="number"
                                                    value={record.started_year}
                                                    onChange={(event) => setRecord(educations, index, { started_year: event.target.value })}
                                                />
                                            </Field>
                                            <Field label="Год окончания" error={at(educations.errors, `records.${index}.graduated_year`)}>
                                                <Input
                                                    type="number"
                                                    value={record.graduated_year}
                                                    onChange={(event) => setRecord(educations, index, { graduated_year: event.target.value })}
                                                />
                                            </Field>
                                            <Field
                                                label="Номер диплома"
                                                className="sm:col-span-2"
                                                error={at(educations.errors, `records.${index}.diploma_number`)}
                                            >
                                                <Input
                                                    value={record.diploma_number}
                                                    onChange={(event) => setRecord(educations, index, { diploma_number: event.target.value })}
                                                />
                                            </Field>
                                        </div>
                                    )}
                                </Records>
                            )}

                            {step === 4 && (
                                <Records
                                    items={jobs.data.records}
                                    empty="Если сведений нет, шаг можно пропустить."
                                    addLabel="Добавить место работы"
                                    onAdd={() => jobs.setData('records', [...jobs.data.records, { ...blankJob }])}
                                    onRemove={(index) => dropRecord(jobs, index)}
                                >
                                    {(record, index) => (
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <Field
                                                label="Организация"
                                                className="sm:col-span-2"
                                                error={at(jobs.errors, `records.${index}.organization`)}
                                            >
                                                <Input
                                                    value={record.organization}
                                                    onChange={(event) => setRecord(jobs, index, { organization: event.target.value })}
                                                />
                                            </Field>
                                            <Field label="Должность" error={at(jobs.errors, `records.${index}.position`)}>
                                                <Input
                                                    value={record.position}
                                                    onChange={(event) => setRecord(jobs, index, { position: event.target.value })}
                                                />
                                            </Field>
                                            <Field label="Страна" error={at(jobs.errors, `records.${index}.country`)}>
                                                <Input
                                                    value={record.country}
                                                    onChange={(event) => setRecord(jobs, index, { country: event.target.value })}
                                                />
                                            </Field>

                                            <Field label="Вступление" error={at(jobs.errors, `records.${index}.started_year`)}>
                                                <div className="flex gap-2">
                                                    <SearchableSelect
                                                        className="flex-1"
                                                        value={record.started_month}
                                                        onChange={(value) => setRecord(jobs, index, { started_month: value })}
                                                        options={monthNames.map((month, position) => ({
                                                            value: String(position + 1),
                                                            label: month,
                                                        }))}
                                                        placeholder="Месяц"
                                                    />
                                                    <Input
                                                        type="number"
                                                        className="w-24"
                                                        placeholder="Год"
                                                        value={record.started_year}
                                                        onChange={(event) => setRecord(jobs, index, { started_year: event.target.value })}
                                                    />
                                                </div>
                                            </Field>

                                            <Field label="Уход" error={at(jobs.errors, `records.${index}.ended_year`)}>
                                                <div className="flex gap-2">
                                                    <SearchableSelect
                                                        className="flex-1"
                                                        value={record.ended_month}
                                                        onChange={(value) => setRecord(jobs, index, { ended_month: value })}
                                                        options={monthNames.map((month, position) => ({
                                                            value: String(position + 1),
                                                            label: month,
                                                        }))}
                                                        placeholder="Месяц"
                                                    />
                                                    <Input
                                                        type="number"
                                                        className="w-24"
                                                        placeholder="Год"
                                                        value={record.ended_year}
                                                        onChange={(event) => setRecord(jobs, index, { ended_year: event.target.value })}
                                                    />
                                                </div>
                                            </Field>
                                        </div>
                                    )}
                                </Records>
                            )}

                            {step === 5 && (
                                <div className="flex flex-col gap-4">
                                    <Field label="Что выдаём" error={at(equipment.errors, 'equipment')}>
                                        <MultiSelect
                                            options={options.stock.map((unit) => ({
                                                value: unit.id,
                                                label: `${unit.name} · ${unit.inventory_number}`,
                                            }))}
                                            value={equipment.data.equipment}
                                            onChange={(value) => equipment.setData('equipment', value)}
                                            placeholder="Ничего не выбрано"
                                            searchPlaceholder="Поиск по названию или номеру"
                                        />
                                        <p className="text-muted-foreground text-[13px]">Только то, что свободно и никому не выдано.</p>
                                    </Field>

                                    <Field label="Дата выдачи" error={equipment.errors.issued_at}>
                                        <Input
                                            type="date"
                                            max={today}
                                            value={equipment.data.issued_at}
                                            onChange={(event) => equipment.setData('issued_at', event.target.value)}
                                            aria-invalid={!!equipment.errors.issued_at}
                                        />
                                    </Field>
                                </div>
                            )}

                            <div className="flex flex-wrap items-center justify-between gap-2 border-t pt-5">
                                <Button type="button" variant="outline" disabled={step === 0 || busy} onClick={() => setStep(step - 1)}>
                                    <ChevronLeft />
                                    Назад
                                </Button>

                                <div className="flex flex-wrap gap-2">
                                    {/*
                                     * Filing a whole intake: save this step and start the next
                                     * colleague straight away. From the first step it is the quick
                                     * way in — name, e-mail, done, next person.
                                     */}
                                    <Button type="button" variant="outline" disabled={busy} onClick={() => next('again')}>
                                        <UserPlus />
                                        {step === 0 ? 'Создать и добавить ещё' : 'Сохранить и добавить ещё'}
                                    </Button>

                                    <Button type="submit" disabled={busy}>
                                        {busy && <LoaderCircle className="animate-spin" />}
                                        {step === steps.length - 1 ? 'Готово' : step === 0 ? 'Создать и продолжить' : 'Далее'}
                                        {step < steps.length - 1 && <ChevronRight />}
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
