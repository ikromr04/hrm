import InputError from '@/components/input-error';
import { LevelBadge } from '@/components/language-badges';
import { MultiSelect } from '@/components/multi-select';
import { PersonAvatar } from '@/components/person-avatar';
import { SosPhone } from '@/components/phones';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import {
    age,
    capitalize,
    formatDate,
    formatPhone,
    languageLevels,
    maritalLabels,
    monthNames,
    monthsSpan,
    sexLabels,
    tenure,
    type Education,
    type Equipment,
    type LanguageLevel,
    type Marital,
    type PrivateDetails,
    type Sex,
    type SpokenLanguage,
    type WorkExperience,
} from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Construction, LoaderCircle, Lock, Mail, Pencil, Phone, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState, type FormEventHandler, type ReactNode } from 'react';

interface ProfilePrivate extends PrivateDetails {
    educations: (Education & { id: number })[];
    /** The latest first. */
    work_experiences: (WorkExperience & { id: number })[];
    /** Grouped by kind, which is resolved to its directory name here. */
    equipment: (Equipment & { id: number; type: string | null })[];
    birth_place: string | null;
    passport: { series: string | null; number: string | null; issued_at: string | null; issued_by: string | null };
}

interface Employee {
    id: number;
    name: string;
    surname: string;
    patronymic: string | null;
    avatar: string | null;
    sex: Sex;
    email: string;
    status: 'active' | 'transferred' | 'fired';
    status_changed_at: string | null;
    /** Where they were transferred or why they were let go; managers only. */
    status_note: string | null;
    /** Access roles, shown as "Позиция". */
    roles: string[];
    /** Positions, shown as "Должность"; an employee can hold several. */
    positions: string[];
    departments: { id: number; name: string; path: string; is_head: boolean }[];
    /** The best known first. */
    languages: SpokenLanguage[];
    /** Null when the viewer may not see this person's private data. */
    private: ProfilePrivate | null;
}

/**
 * Sections of the profile. Education, experience and equipment hold private
 * data, so they are offered only to viewers who may see it; the last four have
 * no data behind them yet.
 */
const TABS = [
    { key: 'profile', title: 'Профиль' },
    { key: 'education', title: 'Образование', private: true },
    { key: 'experience', title: 'Трудовая деятельность', private: true },
    { key: 'equipment', title: 'Оборудование', private: true },
    { key: 'vacation', title: 'Отпуск', soon: true },
    { key: 'pir', title: 'ПИР', soon: true },
    { key: 'kpi', title: 'KPI', soon: true },
    { key: 'attendance', title: 'Посещаемость', soon: true },
] as const;

type TabKey = (typeof TABS)[number]['key'];

/** The open section rides in the URL hash, so a tab can be linked and survives a reload. */
function useTab(available: readonly TabKey[]): [TabKey, (key: TabKey) => void] {
    const fromHash = () => {
        const key = window.location.hash.replace('#', '') as TabKey;

        return available.includes(key) ? key : 'profile';
    };
    const [tab, setTab] = useState<TabKey>(fromHash);

    // Back and forward move between tabs, like between pages.
    useEffect(() => {
        const onHashChange = () => setTab(fromHash());
        window.addEventListener('hashchange', onHashChange);

        return () => window.removeEventListener('hashchange', onHashChange);
    });

    return [
        tab,
        (key: TabKey) => {
            setTab(key);
            window.history.replaceState(null, '', key === 'profile' ? window.location.pathname : `#${key}`);
        },
    ];
}

function Tabs({ tabs, active, onChange }: { tabs: readonly { key: TabKey; title: string }[]; active: TabKey; onChange: (key: TabKey) => void }) {
    return (
        <nav aria-label="Разделы профиля" className="mt-3 flex flex-wrap gap-x-6 gap-y-1">
            {tabs.map((tab) => (
                <button
                    key={tab.key}
                    type="button"
                    onClick={() => onChange(tab.key)}
                    aria-current={tab.key === active ? 'page' : undefined}
                    className={cn(
                        'shrink-0 border-b-2 px-1 pb-2.5 text-sm transition-colors',
                        tab.key === active
                            ? 'border-brand text-foreground font-semibold'
                            : 'text-muted-foreground hover:text-foreground border-transparent font-medium',
                    )}
                >
                    {tab.title}
                </button>
            ))}
        </nav>
    );
}

interface EditOptions {
    nationalities: string[];
    citizenships: string[];
    /** Access roles, chosen by name. */
    roles: { name: string; title: string }[];
    positions: { id: number; name: string }[];
    /** Flattened tree; `depth` indents the children. */
    departments: { id: number; name: string; depth: number }[];
    languages: { id: number; name: string }[];
}

/** What the employee currently holds, as the dialog addresses it. */
interface Assigned {
    roles: string[];
    positions: number[];
    departments: number[];
}

/** The "Основные данные" card in a form; sex sits on the user, the rest on the details. */
function PersonalDialog({
    employee,
    details,
    options,
    assigned,
    onClose,
}: {
    employee: Employee;
    details: ProfilePrivate;
    options: EditOptions;
    assigned: Assigned;
    onClose: () => void;
}) {
    const form = useForm({
        surname: employee.surname,
        name: employee.name,
        patronymic: employee.patronymic ?? '',
        sex: employee.sex,
        birth_date: details.birth_date ?? '',
        birth_place: details.birth_place ?? '',
        citizenship: details.citizenship ?? '',
        nationality: details.nationality ?? '',
        home_address: details.home_address ?? '',
        roles: assigned.roles,
        positions: assigned.positions,
        departments: assigned.departments,
    });

    /** The first error for a list and its items ("roles", "roles.0", ...). */
    const listError = (key: string) => {
        const errors = form.errors as Record<string, string | undefined>;

        return errors[key] ?? Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1];
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('employees.personal', employee.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                {/* noValidate: the browser's own bubbles would pre-empt the server, whose
                    rules are the real ones; its messages show under each field instead. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Основные данные</DialogTitle>
                        <DialogDescription className="sr-only">Измените поля и сохраните.</DialogDescription>
                    </DialogHeader>

                    <datalist id="personal-nationalities">
                        {options.nationalities.map((value) => (
                            <option key={value} value={value} />
                        ))}
                    </datalist>
                    <datalist id="personal-citizenships">
                        {options.citizenships.map((value) => (
                            <option key={value} value={value} />
                        ))}
                    </datalist>

                    <div className="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="personal-surname">Фамилия</Label>
                            <Input
                                id="personal-surname"
                                required
                                value={form.data.surname}
                                onChange={(e) => form.setData('surname', e.target.value)}
                                aria-invalid={!!form.errors.surname}
                            />
                            <InputError message={form.errors.surname} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="personal-name">Имя</Label>
                            <Input
                                id="personal-name"
                                required
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                aria-invalid={!!form.errors.name}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="personal-patronymic">Отчество</Label>
                            <Input
                                id="personal-patronymic"
                                value={form.data.patronymic}
                                onChange={(e) => form.setData('patronymic', e.target.value)}
                                aria-invalid={!!form.errors.patronymic}
                            />
                            <InputError message={form.errors.patronymic} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="personal-birth-date">Дата рождения</Label>
                            <Input
                                id="personal-birth-date"
                                type="date"
                                max={new Date().toISOString().slice(0, 10)}
                                value={form.data.birth_date}
                                onChange={(e) => form.setData('birth_date', e.target.value)}
                                aria-invalid={!!form.errors.birth_date}
                            />
                            <InputError message={form.errors.birth_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="personal-sex">Пол</Label>
                            <Select value={form.data.sex} onValueChange={(value) => form.setData('sex', value as Sex)}>
                                <SelectTrigger id="personal-sex">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="male">{sexLabels.male}</SelectItem>
                                    <SelectItem value="female">{sexLabels.female}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.sex} />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="personal-birth-place">Место рождения</Label>
                            <Input
                                id="personal-birth-place"
                                value={form.data.birth_place}
                                onChange={(e) => form.setData('birth_place', e.target.value)}
                                aria-invalid={!!form.errors.birth_place}
                            />
                            <InputError message={form.errors.birth_place} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="personal-nationality">Национальность</Label>
                            <Input
                                id="personal-nationality"
                                list="personal-nationalities"
                                value={form.data.nationality}
                                onChange={(e) => form.setData('nationality', e.target.value)}
                                aria-invalid={!!form.errors.nationality}
                            />
                            <InputError message={form.errors.nationality} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="personal-citizenship">Гражданство</Label>
                            <Input
                                id="personal-citizenship"
                                list="personal-citizenships"
                                value={form.data.citizenship}
                                onChange={(e) => form.setData('citizenship', e.target.value)}
                                aria-invalid={!!form.errors.citizenship}
                            />
                            <InputError message={form.errors.citizenship} />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="personal-home-address">Домашний адрес</Label>
                            <Input
                                id="personal-home-address"
                                value={form.data.home_address}
                                onChange={(e) => form.setData('home_address', e.target.value)}
                                aria-invalid={!!form.errors.home_address}
                            />
                            <InputError message={form.errors.home_address} />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="personal-roles">Позиция</Label>
                            <MultiSelect
                                id="personal-roles"
                                options={options.roles.map((role) => ({ value: role.name, label: role.title }))}
                                value={form.data.roles}
                                onChange={(value) => form.setData('roles', value)}
                            />
                            <InputError message={listError('roles')} />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="personal-positions">Должность</Label>
                            <MultiSelect
                                id="personal-positions"
                                options={options.positions.map((position) => ({ value: position.id, label: position.name }))}
                                value={form.data.positions}
                                onChange={(value) => form.setData('positions', value)}
                            />
                            <InputError message={listError('positions')} />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="personal-departments">Отдел</Label>
                            <MultiSelect
                                id="personal-departments"
                                options={options.departments.map((department) => ({
                                    value: department.id,
                                    label: department.name,
                                    depth: department.depth,
                                }))}
                                value={form.data.departments}
                                onChange={(value) => form.setData('departments', value)}
                            />
                            <InputError message={listError('departments')} />
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Contacts are dialled and written to, so they carry the brand colour and an
 * icon rather than looking like the plain text of the fields around them.
 */
const contactLink = 'text-brand-strong flex items-center gap-1.5 hover:underline dark:text-[#C5E27A]';

/** The spouse of a man is "Супруга", of a woman "Супруг". */
const spouseLabel = (sex: Sex) => (sex === 'male' ? 'Супруга' : 'Супруг');

/** The "Паспорт" card in a form; every field may stay empty. */
function PassportDialog({ employee, details, onClose }: { employee: Employee; details: ProfilePrivate; onClose: () => void }) {
    const form = useForm({
        passport_series: details.passport.series ?? '',
        passport_number: details.passport.number ?? '',
        passport_issued_at: details.passport.issued_at ?? '',
        passport_issued_by: details.passport.issued_by ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('employees.passport', employee.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                {/* noValidate: see PersonalDialog — the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Паспорт</DialogTitle>
                        <DialogDescription className="sr-only">Измените поля и сохраните.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="passport-series">Серия</Label>
                            <Input
                                id="passport-series"
                                value={form.data.passport_series}
                                onChange={(e) => form.setData('passport_series', e.target.value)}
                                aria-invalid={!!form.errors.passport_series}
                            />
                            <InputError message={form.errors.passport_series} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="passport-number">Номер</Label>
                            <Input
                                id="passport-number"
                                value={form.data.passport_number}
                                onChange={(e) => form.setData('passport_number', e.target.value)}
                                aria-invalid={!!form.errors.passport_number}
                            />
                            <InputError message={form.errors.passport_number} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="passport-issued-at">Дата выдачи</Label>
                            <Input
                                id="passport-issued-at"
                                type="date"
                                max={new Date().toISOString().slice(0, 10)}
                                value={form.data.passport_issued_at}
                                onChange={(e) => form.setData('passport_issued_at', e.target.value)}
                                aria-invalid={!!form.errors.passport_issued_at}
                            />
                            <InputError message={form.errors.passport_issued_at} />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="passport-issued-by">Кем выдан</Label>
                            <Input
                                id="passport-issued-by"
                                value={form.data.passport_issued_by}
                                onChange={(e) => form.setData('passport_issued_by', e.target.value)}
                                aria-invalid={!!form.errors.passport_issued_by}
                            />
                            <InputError message={form.errors.passport_issued_by} />
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** The "Контакты" card in a form; the server normalises the phones to E.164. */
function ContactsDialog({ employee, details, onClose }: { employee: Employee; details: ProfilePrivate; onClose: () => void }) {
    const form = useForm({
        email: employee.email,
        phone: details.phone ?? '',
        sos_phone: details.sos_phone ?? '',
        sos_contact: details.sos_contact ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('employees.contacts', employee.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                {/* noValidate: see PersonalDialog — the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Контакты</DialogTitle>
                        <DialogDescription className="sr-only">Измените поля и сохраните.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="contacts-email">Электронная почта</Label>
                            <Input
                                id="contacts-email"
                                type="email"
                                autoComplete="off"
                                placeholder="name@evolet.tj"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                aria-invalid={!!form.errors.email}
                            />
                            <InputError message={form.errors.email} />
                            <p className="text-muted-foreground text-[13px]">С этим адресом сотрудник входит в систему.</p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="contacts-phone">Телефон</Label>
                            <Input
                                id="contacts-phone"
                                type="tel"
                                inputMode="tel"
                                placeholder="90 123 45 67"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                                aria-invalid={!!form.errors.phone}
                            />
                            <InputError message={form.errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="contacts-sos-phone">Телефон SOS</Label>
                            <Input
                                id="contacts-sos-phone"
                                type="tel"
                                inputMode="tel"
                                placeholder="90 123 45 67"
                                value={form.data.sos_phone}
                                onChange={(e) => form.setData('sos_phone', e.target.value)}
                                aria-invalid={!!form.errors.sos_phone}
                            />
                            <InputError message={form.errors.sos_phone} />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="contacts-sos-contact">Чей это номер</Label>
                            <Input
                                id="contacts-sos-contact"
                                placeholder="Мама — Дилором"
                                value={form.data.sos_contact}
                                onChange={(e) => form.setData('sos_contact', e.target.value)}
                                aria-invalid={!!form.errors.sos_contact}
                            />
                            <InputError message={form.errors.sos_contact} />
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** The "Знание языков" card in a form: a level per language, each language once. */
function LanguagesDialog({ employee, options, onClose }: { employee: Employee; options: EditOptions; onClose: () => void }) {
    const form = useForm({
        languages: employee.languages.map((language) => ({ id: language.id, level: language.level })),
    });

    const errors = form.errors as Record<string, string | undefined>;

    const setLanguage = (index: number, patch: Partial<{ id: number; level: LanguageLevel }>) =>
        form.setData(
            'languages',
            form.data.languages.map((language, i) => (i === index ? { ...language, ...patch } : language)),
        );

    // Each language once: a new row takes the first one not picked yet.
    const unused = options.languages.find((option) => !form.data.languages.some((l) => l.id === option.id));

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('employees.languages', employee.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                {/* noValidate: see PersonalDialog — the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Знание языков</DialogTitle>
                        <DialogDescription className="sr-only">Измените поля и сохраните.</DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col gap-3">
                        {form.data.languages.length === 0 && <p className="text-muted-foreground text-sm">Не указаны</p>}

                        {form.data.languages.map((language, index) => (
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
                                                        (option) => option.id === language.id || !form.data.languages.some((l) => l.id === option.id),
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
                                                {/* The same badge the profile shows, so the colour is picked, not guessed. */}
                                                {languageLevels.map((level) => (
                                                    <SelectItem key={level} value={level}>
                                                        <LevelBadge level={level} />
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
                                        form.setData(
                                            'languages',
                                            form.data.languages.filter((_, i) => i !== index),
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
                            disabled={!unused}
                            onClick={() => unused && form.setData('languages', [...form.data.languages, { id: unused.id, level: 'intermediate' }])}
                        >
                            <Plus />
                            Добавить язык
                        </Button>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** The "Семья" card in a form: marital status, the spouse, and any number of children. */
function FamilyDialog({ employee, details, onClose }: { employee: Employee; details: ProfilePrivate; onClose: () => void }) {
    const form = useForm({
        marital_status: details.marital_status ?? '',
        spouse_name: details.spouse_name ?? '',
        spouse_birth_date: details.spouse_birth_date ?? '',
        has_children: details.has_children,
        children: details.children.map((child) => ({ full_name: child.full_name, birth_date: child.birth_date ?? '' })),
    });

    /** Ticked, the card states there are none; unticked with no rows, it stays unanswered. */
    const noChildren = form.data.has_children === false;

    const today = new Date().toISOString().slice(0, 10);
    const errors = form.errors as Record<string, string | undefined>;

    const setChild = (index: number, patch: Partial<{ full_name: string; birth_date: string }>) =>
        form.setData(
            'children',
            form.data.children.map((child, i) => (i === index ? { ...child, ...patch } : child)),
        );

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.put(route('employees.family', employee.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                {/* noValidate: see PersonalDialog — the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>Семья</DialogTitle>
                        <DialogDescription className="sr-only">Измените поля и сохраните.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="family-marital">Семейное положение</Label>
                            <Select
                                value={form.data.marital_status || 'none'}
                                onValueChange={(value) => form.setData('marital_status', value === 'none' ? '' : (value as Marital))}
                            >
                                <SelectTrigger id="family-marital">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Не указано</SelectItem>
                                    <SelectItem value="single">{maritalLabels[employee.sex].single}</SelectItem>
                                    <SelectItem value="married">{maritalLabels[employee.sex].married}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.marital_status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="family-spouse-name">{spouseLabel(employee.sex)}</Label>
                            <Input
                                id="family-spouse-name"
                                placeholder="ФИО"
                                value={form.data.spouse_name}
                                onChange={(e) => form.setData('spouse_name', e.target.value)}
                                aria-invalid={!!form.errors.spouse_name}
                            />
                            <InputError message={form.errors.spouse_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="family-spouse-birth-date">Дата рождения</Label>
                            <Input
                                id="family-spouse-birth-date"
                                type="date"
                                max={today}
                                value={form.data.spouse_birth_date}
                                onChange={(e) => form.setData('spouse_birth_date', e.target.value)}
                                aria-invalid={!!form.errors.spouse_birth_date}
                            />
                            <InputError message={form.errors.spouse_birth_date} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 border-t pt-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h3 className="text-sm font-semibold">Дети</h3>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="family-no-children"
                                    checked={noChildren}
                                    // Ticking it drops any rows, so the flag and the list never contradict each other.
                                    onCheckedChange={(checked) =>
                                        form.setData((data) => ({
                                            ...data,
                                            has_children: checked === true ? false : null,
                                            children: checked === true ? [] : data.children,
                                        }))
                                    }
                                />
                                <Label htmlFor="family-no-children" className="font-normal">
                                    Детей нет
                                </Label>
                            </div>
                        </div>

                        {!noChildren && form.data.children.length === 0 && <p className="text-muted-foreground text-sm">Не указаны</p>}

                        {!noChildren &&
                            form.data.children.map((child, index) => (
                                <div key={index} className="flex items-start gap-2">
                                    <div className="grid flex-1 gap-x-4 gap-y-2 sm:grid-cols-[1fr_10rem]">
                                        <div className="grid gap-2">
                                            <Label htmlFor={`family-child-${index}`} className="sr-only">
                                                ФИО ребёнка
                                            </Label>
                                            <Input
                                                id={`family-child-${index}`}
                                                placeholder="ФИО"
                                                value={child.full_name}
                                                onChange={(e) => setChild(index, { full_name: e.target.value })}
                                                aria-invalid={!!errors[`children.${index}.full_name`]}
                                            />
                                            <InputError message={errors[`children.${index}.full_name`]} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor={`family-child-${index}-birth`} className="sr-only">
                                                Дата рождения ребёнка
                                            </Label>
                                            <Input
                                                id={`family-child-${index}-birth`}
                                                type="date"
                                                max={today}
                                                value={child.birth_date}
                                                onChange={(e) => setChild(index, { birth_date: e.target.value })}
                                                aria-invalid={!!errors[`children.${index}.birth_date`]}
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
                                            form.setData(
                                                'children',
                                                form.data.children.filter((_, i) => i !== index),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            ))}

                        {!noChildren && (
                            <Button
                                type="button"
                                variant="outline"
                                className="self-start"
                                onClick={() => form.setData('children', [...form.data.children, { full_name: '', birth_date: '' }])}
                            >
                                <Plus />
                                Добавить ребёнка
                            </Button>
                        )}
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Stands in for a section that has no data behind it yet. */
function Soon({ title }: { title: string }) {
    return (
        <Card className="text-muted-foreground flex items-start gap-3 rounded-xl px-6 py-5 text-sm">
            <Construction className="mt-0.5 size-5 shrink-0" />
            <p>Раздел «{title}» пока в разработке.</p>
        </Card>
    );
}

/**
 * A block of the profile. Given an `action`, the title turns into a header
 * strip ruled off from the body, with the action on the right.
 */
function Section({ title, children, className, action }: { title: string; children: ReactNode; className?: string; action?: ReactNode }) {
    return (
        <Card className={cn('flex flex-col gap-4 rounded-xl px-6 py-5', className)}>
            {action ? (
                // Flush to the card's edges, so the strip reads as its header.
                <div className="bg-muted/60 -mx-6 -mt-5 flex items-center justify-between gap-3 rounded-t-xl border-b px-6 py-2">
                    <h2 className="text-base font-semibold">{title}</h2>
                    {action}
                </div>
            ) : (
                <h2 className="text-base font-semibold">{title}</h2>
            )}
            {children}
        </Card>
    );
}

/**
 * Fields in newspaper columns: they read top to bottom, up to three across.
 * Narrow cards in the sidebar pass `columns={1}`, where three would be
 * unreadable.
 *
 * A multi-column layout has no row gap, so the spacing lives on each field.
 * Columns end at different heights, so the gap below stays on the last one
 * too — trimming it would leave the card lopsided.
 */
function Fields({ children, columns }: { children: ReactNode; columns?: 1 }) {
    return <dl className={cn('gap-x-6', columns === 1 ? 'columns-1' : 'columns-1 sm:columns-2 lg:columns-3')}>{children}</dl>;
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="mb-4 flex min-w-0 break-inside-avoid flex-col gap-1">
            <dt className="text-muted-foreground text-[13px]">{label}</dt>
            <dd className="text-sm font-medium break-words">{children ?? <span className="text-muted-foreground font-normal">—</span>}</dd>
        </div>
    );
}

function Departments({ items }: { items: Employee['departments'] }) {
    return (
        <ul className="flex flex-col gap-1">
            {items.map((department) => (
                <li key={department.id}>
                    <Link href={route('departments.show', department.id)} className="hover:underline">
                        {department.path}
                    </Link>
                    {department.is_head && <span className="text-brand-strong font-semibold dark:text-[#C5E27A]"> · руководитель</span>}
                </li>
            ))}
        </ul>
    );
}

/** One language per row: the name on the left, the level as a badge on the right. */
function Languages({ items }: { items: SpokenLanguage[] }) {
    return (
        <ul className="flex flex-col">
            {items.map((language) => (
                <li key={language.id} className="flex items-center justify-between gap-3 border-t py-2.5 first:border-t-0 first:pt-0 last:pb-0">
                    <span className="truncate text-sm font-medium">{language.name}</span>
                    <LevelBadge level={language.level} />
                </li>
            ))}
        </ul>
    );
}

/** "2010–2015", or "2019 — учится" for someone still enrolled. */
const studyYears = (education: Education) =>
    education.graduated_year ? `${education.started_year}–${education.graduated_year}` : `${education.started_year} — учится`;

function Educations({ items }: { items: ProfilePrivate['educations'] }) {
    return (
        <ul className="flex flex-col">
            {items.map((education) => (
                <li key={education.id} className="flex flex-col gap-0.5 border-t py-3 first:border-t-0 first:pt-0 last:pb-0">
                    <span className="text-sm font-medium">{education.institution}</span>
                    <span className="text-sm">
                        {education.faculty} · {education.specialty}
                    </span>
                    <span className="text-muted-foreground text-[13px] tabular-nums">
                        {studyYears(education)}
                        {education.diploma_number && ` · диплом № ${education.diploma_number}`}
                    </span>
                </li>
            ))}
        </ul>
    );
}

/** "Март 2018 — Июнь 2021 · 3 года 3 мес." */
function workPeriod(job: WorkExperience): string {
    const now = new Date();
    const [endYear, endMonth] = job.ended_year && job.ended_month ? [job.ended_year, job.ended_month] : [now.getFullYear(), now.getMonth() + 1];
    const end = job.ended_year && job.ended_month ? `${monthNames[job.ended_month - 1]} ${job.ended_year}` : 'по настоящее время';

    return `${monthNames[job.started_month - 1]} ${job.started_year} — ${end} · ${monthsSpan(job.started_year, job.started_month, endYear, endMonth)}`;
}

function WorkExperiences({ items }: { items: ProfilePrivate['work_experiences'] }) {
    return (
        <ul className="flex flex-col">
            {items.map((job) => (
                <li key={job.id} className="flex flex-col gap-0.5 border-t py-3 first:border-t-0 first:pt-0 last:pb-0">
                    <span className="text-sm font-medium">{job.position}</span>
                    <span className="text-sm">
                        {job.organization} · {job.country}
                    </span>
                    <span className="text-muted-foreground text-[13px] tabular-nums">{workPeriod(job)}</span>
                </li>
            ))}
        </ul>
    );
}

function EquipmentList({ items }: { items: ProfilePrivate['equipment'] }) {
    return (
        <ul className="flex flex-col">
            {items.map((unit) => (
                <li key={unit.id} className="flex flex-col gap-0.5 border-t py-3 first:border-t-0 first:pt-0 last:pb-0">
                    <span className="text-sm font-medium">{unit.type ?? 'Без вида'}</span>
                    {unit.description && <span className="text-sm">{unit.description}</span>}
                    <span className="text-muted-foreground text-[13px] tabular-nums">Инв. № {unit.inventory_number}</span>
                </li>
            ))}
        </ul>
    );
}

type Neighbour = { id: number; name: string } | null;

/** Links to the previous and next colleague in the list; ← and → keys do the same. */
function Neighbours({ prev, next }: { prev: Neighbour; next: Neighbour }) {
    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.ctrlKey || event.altKey || event.metaKey || event.shiftKey) return;
            const target = event.target as HTMLElement;
            if (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
            const to = event.key === 'ArrowLeft' ? prev : event.key === 'ArrowRight' ? next : null;
            if (to) router.visit(route('employees.show', to.id));
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [prev, next]);

    const arrow = (to: Neighbour, label: string, Icon: typeof ChevronLeft) => {
        // The arrow leads the label going back and trails it going forward.
        const back = Icon === ChevronLeft;

        return (
            <Button variant="outline" disabled={!to} aria-label={to ? `${label}: ${to.name}` : label} title={to?.name} asChild={!!to}>
                {to ? (
                    <Link href={route('employees.show', to.id)} prefetch>
                        {back && <Icon />}
                        {label}
                        {!back && <Icon />}
                    </Link>
                ) : (
                    <>
                        {back && <Icon />}
                        {label}
                        {!back && <Icon />}
                    </>
                )}
            </Button>
        );
    };

    return (
        <div className="flex gap-1">
            {arrow(prev, 'Предыдущий', ChevronLeft)}
            {arrow(next, 'Следующий', ChevronRight)}
        </div>
    );
}

export default function EmployeeProfile({
    employee,
    neighbours,
    canEdit,
    options,
    assigned,
}: {
    employee: Employee;
    neighbours: { prev: Neighbour; next: Neighbour };
    canEdit: boolean;
    /** Choices for the edit dialogs; null for viewers who may not edit. */
    options: EditOptions | null;
    assigned: Assigned | null;
}) {
    const [editing, setEditing] = useState<'personal' | 'passport' | 'contacts' | 'languages' | 'family' | null>(null);
    const shortName = `${employee.surname} ${employee.name}`;
    const fullName = [employee.surname, employee.name, employee.patronymic].filter(Boolean).join(' ');
    const details = employee.private;

    const tabs = TABS.filter((tab) => !('private' in tab && tab.private) || details);
    const [tab, setTab] = useTab(tabs.map((t) => t.key));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Сотрудники', href: '/employees' },
        { title: shortName, href: `/employees/${employee.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={shortName} />

            <div className="flex flex-1 flex-col gap-4 p-3 md:px-5 md:py-4">
                <div className="flex flex-col gap-5 px-1 pt-1 sm:flex-row sm:items-end">
                    {employee.avatar ? (
                        <img src={employee.avatar} alt="" className="size-28 shrink-0 rounded-full object-cover" />
                    ) : (
                        <PersonAvatar name={`${employee.name} ${employee.surname}`} className="size-28 text-4xl" />
                    )}

                    <div className="flex min-w-0 flex-1 flex-col gap-2">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-xl font-semibold tracking-tight">{fullName}</h1>
                            {employee.status !== 'active' && (
                                <StatusBadge tone={employee.status === 'fired' ? 'danger' : 'warning'} title={employee.status_note ?? undefined}>
                                    {[
                                        employee.status === 'fired'
                                            ? employee.sex === 'female'
                                                ? 'Уволена'
                                                : 'Уволен'
                                            : employee.sex === 'female'
                                              ? 'Переведена'
                                              : 'Переведён',
                                        formatDate(employee.status_changed_at),
                                        employee.status_note &&
                                            (employee.status === 'transferred' ? `→ ${employee.status_note}` : `· ${employee.status_note}`),
                                    ]
                                        .filter(Boolean)
                                        .join(' ')}
                                </StatusBadge>
                            )}
                        </div>

                        {employee.positions.length > 0 && (
                            <div className="flex flex-wrap gap-2">
                                {employee.positions.map((title) => (
                                    <StatusBadge key={title} tone="success">
                                        {title}
                                    </StatusBadge>
                                ))}
                            </div>
                        )}

                        <div className="text-muted-foreground flex flex-wrap gap-x-5 gap-y-1 text-sm">
                            <a href={`mailto:${employee.email}`} className={contactLink}>
                                <Mail className="size-4" />
                                {employee.email}
                            </a>
                            {details?.phone && (
                                <a href={`tel:${details.phone}`} className={cn(contactLink, 'tabular-nums')}>
                                    <Phone className="size-4" />
                                    {formatPhone(details.phone)}
                                </a>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2 self-start sm:self-end">
                        <Neighbours prev={neighbours.prev} next={neighbours.next} />
                    </div>
                </div>

                <Tabs tabs={tabs} active={tab} onChange={setTab} />

                {tab !== 'profile' && TABS.find((t) => t.key === tab && 'soon' in t) && <Soon title={TABS.find((t) => t.key === tab)!.title} />}

                {tab === 'education' && details && (
                    <Section title="Образование">
                        {details.educations.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Не указано</p>
                        ) : (
                            <Educations items={details.educations} />
                        )}
                    </Section>
                )}

                {tab === 'experience' && details && (
                    <Section title="Трудовая деятельность">
                        {details.work_experiences.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Не указана</p>
                        ) : (
                            <WorkExperiences items={details.work_experiences} />
                        )}
                    </Section>
                )}

                {tab === 'equipment' && details && (
                    <Section title="Оборудование">
                        {details.equipment.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Не выдано</p>
                        ) : (
                            <EquipmentList items={details.equipment} />
                        )}
                    </Section>
                )}

                {tab === 'profile' &&
                    (details ? (
                        <div className="grid items-start gap-4 lg:grid-cols-[1fr_20rem]">
                            <div className="flex flex-col gap-4">
                                <Section
                                    title="Основные данные"
                                    action={
                                        canEdit && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground -mr-2 size-7"
                                                aria-label="Редактировать основные данные"
                                                onClick={() => setEditing('personal')}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                        )
                                    }
                                >
                                    <Fields>
                                        <Field label="Фамилия">{employee.surname}</Field>
                                        <Field label="Имя">{employee.name}</Field>
                                        <Field label="Отчество">{employee.patronymic}</Field>
                                        <Field label="Пол">{sexLabels[employee.sex]}</Field>
                                        <Field label="Дата рождения">
                                            {details.birth_date && (
                                                <>
                                                    {formatDate(details.birth_date)}
                                                    <span className="text-muted-foreground font-normal"> · {age(details.birth_date)}</span>
                                                </>
                                            )}
                                        </Field>
                                        <Field label="Место рождения">{details.birth_place}</Field>
                                        <Field label="Гражданство">{details.citizenship}</Field>
                                        <Field label="Национальность">{details.nationality && capitalize(details.nationality)}</Field>
                                        <Field label="Домашний адрес">{details.home_address}</Field>
                                        <Field label={employee.roles.length > 1 ? 'Позиции' : 'Позиция'}>
                                            {employee.roles.length > 0 ? employee.roles.join(', ') : null}
                                        </Field>
                                        <Field label={employee.positions.length > 1 ? 'Должности' : 'Должность'}>
                                            {employee.positions.length > 0 ? employee.positions.join(', ') : null}
                                        </Field>
                                        <Field label={employee.departments.length > 1 ? 'Отделы' : 'Отдел'}>
                                            {employee.departments.length > 0 ? <Departments items={employee.departments} /> : null}
                                        </Field>
                                    </Fields>
                                </Section>

                                <Section
                                    title="Паспорт"
                                    action={
                                        canEdit && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground -mr-2 size-7"
                                                aria-label="Редактировать паспорт"
                                                onClick={() => setEditing('passport')}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                        )
                                    }
                                >
                                    <Fields>
                                        <Field label="Серия и номер">
                                            {(details.passport.series || details.passport.number) && (
                                                <span className="tabular-nums">
                                                    {[details.passport.series, details.passport.number].filter(Boolean).join(' ')}
                                                </span>
                                            )}
                                        </Field>
                                        <Field label="Дата выдачи">{formatDate(details.passport.issued_at)}</Field>
                                        <Field label="Кем выдан">{details.passport.issued_by}</Field>
                                    </Fields>
                                </Section>

                                <Section
                                    title="Семья"
                                    action={
                                        canEdit && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground -mr-2 size-7"
                                                aria-label="Редактировать семью"
                                                onClick={() => setEditing('family')}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                        )
                                    }
                                >
                                    <Fields>
                                        <Field label="Семейное положение">
                                            {details.marital_status && maritalLabels[employee.sex][details.marital_status]}
                                        </Field>
                                        <Field label={spouseLabel(employee.sex)}>
                                            {details.spouse_name && (
                                                <>
                                                    {details.spouse_name}
                                                    {details.spouse_birth_date && (
                                                        <span className="text-muted-foreground font-normal">
                                                            {' · '}
                                                            {formatDate(details.spouse_birth_date)} · {age(details.spouse_birth_date)}
                                                        </span>
                                                    )}
                                                </>
                                            )}
                                        </Field>
                                    </Fields>

                                    <h3 className="text-muted-foreground text-[13px]">Дети</h3>
                                    {details.children.length === 0 ? (
                                        // A dash while nobody has filled this in; "Нет" only once HR says so.
                                        <p className="text-muted-foreground text-sm">{details.has_children === false ? 'Нет' : '—'}</p>
                                    ) : (
                                        <ul className="flex flex-col">
                                            {details.children.map((child) => (
                                                <li
                                                    key={child.full_name + child.birth_date}
                                                    className="flex flex-col gap-0.5 border-t py-3 first:border-t-0 first:pt-0 last:pb-0"
                                                >
                                                    <span className="text-sm font-medium">{child.full_name}</span>
                                                    {child.birth_date && (
                                                        <span className="text-muted-foreground text-[13px]">
                                                            {formatDate(child.birth_date)} · {age(child.birth_date)}
                                                        </span>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </Section>
                            </div>

                            <aside className="flex flex-col gap-4">
                                <Section title="Работа">
                                    <Fields columns={1}>
                                        <Field label="Начало работы">{formatDate(details.hired_at)}</Field>
                                        <Field label="Стаж в компании">{details.hired_at && tenure(details.hired_at)}</Field>
                                    </Fields>
                                </Section>

                                <Section
                                    title="Знание языков"
                                    action={
                                        canEdit && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground -mr-2 size-7"
                                                aria-label="Редактировать знание языков"
                                                onClick={() => setEditing('languages')}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                        )
                                    }
                                >
                                    {employee.languages.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">Не указаны</p>
                                    ) : (
                                        <Languages items={employee.languages} />
                                    )}
                                </Section>

                                <Section
                                    title="Контакты"
                                    action={
                                        canEdit && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground -mr-2 size-7"
                                                aria-label="Редактировать контакты"
                                                onClick={() => setEditing('contacts')}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                        )
                                    }
                                >
                                    <Fields columns={1}>
                                        <Field label="Электронная почта">
                                            <a href={`mailto:${employee.email}`} className={contactLink}>
                                                <Mail className="size-4 shrink-0" />
                                                <span className="truncate">{employee.email}</span>
                                            </a>
                                        </Field>
                                        <Field label="Телефон">
                                            {details.phone && (
                                                <a href={`tel:${details.phone}`} className={cn(contactLink, 'tabular-nums')}>
                                                    <Phone className="size-4 shrink-0" />
                                                    {formatPhone(details.phone)}
                                                </a>
                                            )}
                                        </Field>
                                        <Field label="Телефон SOS">
                                            {details.sos_phone && <SosPhone phone={details.sos_phone} contact={details.sos_contact} />}
                                        </Field>
                                    </Fields>
                                </Section>
                            </aside>
                        </div>
                    ) : (
                        <div className="grid items-start gap-4 lg:grid-cols-[1fr_20rem]">
                            <div className="flex flex-col gap-4">
                                <Section title="Основное">
                                    <Fields>
                                        <Field label={employee.roles.length > 1 ? 'Позиции' : 'Позиция'}>
                                            {employee.roles.length > 0 ? employee.roles.join(', ') : null}
                                        </Field>
                                        <Field label={employee.positions.length > 1 ? 'Должности' : 'Должность'}>
                                            {employee.positions.length > 0 ? employee.positions.join(', ') : null}
                                        </Field>
                                        <Field label={employee.departments.length > 1 ? 'Отделы' : 'Отдел'}>
                                            {employee.departments.length > 0 ? <Departments items={employee.departments} /> : null}
                                        </Field>
                                        <Field label="Пол">{sexLabels[employee.sex]}</Field>
                                    </Fields>
                                </Section>

                                <Card className="text-muted-foreground flex items-start gap-3 rounded-xl px-6 py-5 text-sm">
                                    <Lock className="mt-0.5 size-5 shrink-0" />
                                    <p>
                                        Личные данные, контакты, паспорт и семья закрыты. Их видят только сам сотрудник, его руководитель, HR и
                                        администратор.
                                    </p>
                                </Card>
                            </div>

                            {/* Hire date, phones and the rest are private, so a colleague's sidebar
                            holds languages alone — those are public. */}
                            <aside className="flex flex-col gap-4">
                                <Section
                                    title="Знание языков"
                                    action={
                                        canEdit && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground -mr-2 size-7"
                                                aria-label="Редактировать знание языков"
                                                onClick={() => setEditing('languages')}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                        )
                                    }
                                >
                                    {employee.languages.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">Не указаны</p>
                                    ) : (
                                        <Languages items={employee.languages} />
                                    )}
                                </Section>
                            </aside>
                        </div>
                    ))}
            </div>

            {editing === 'personal' && details && options && assigned && (
                <PersonalDialog employee={employee} details={details} options={options} assigned={assigned} onClose={() => setEditing(null)} />
            )}

            {editing === 'passport' && details && <PassportDialog employee={employee} details={details} onClose={() => setEditing(null)} />}

            {editing === 'contacts' && details && <ContactsDialog employee={employee} details={details} onClose={() => setEditing(null)} />}

            {/* Languages are public, so this one needs no private details. */}
            {editing === 'languages' && options && <LanguagesDialog employee={employee} options={options} onClose={() => setEditing(null)} />}

            {editing === 'family' && details && <FamilyDialog employee={employee} details={details} onClose={() => setEditing(null)} />}
        </AppLayout>
    );
}
