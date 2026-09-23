import { LevelMeter } from '@/components/language-badges';
import { PersonAvatar } from '@/components/person-avatar';
import { SosPhone } from '@/components/phones';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    age,
    capitalize,
    formatDate,
    formatPhone,
    languageLevelLabels,
    maritalLabels,
    monthNames,
    monthsSpan,
    sexLabels,
    tenure,
    type Education,
    type PrivateDetails,
    type Sex,
    type SpokenLanguage,
    type WorkExperience,
} from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Lock, Mail, Pencil, Phone } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';

interface ProfilePrivate extends PrivateDetails {
    educations: (Education & { id: number })[];
    /** The latest first. */
    work_experiences: (WorkExperience & { id: number })[];
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

function Section({ title, children, className }: { title: string; children: ReactNode; className?: string }) {
    return (
        <Card className={cn('flex flex-col gap-4 rounded-xl px-6 py-5', className)}>
            <h2 className="text-base font-semibold">{title}</h2>
            {children}
        </Card>
    );
}

function Fields({ children }: { children: ReactNode }) {
    return <dl className="grid grid-cols-2 gap-x-4 gap-y-4">{children}</dl>;
}

function Field({ label, children, wide }: { label: string; children: ReactNode; wide?: boolean }) {
    return (
        <div className={cn('flex min-w-0 flex-col gap-1', wide && 'col-span-2')}>
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

function Languages({ items }: { items: SpokenLanguage[] }) {
    return (
        <ul className="flex flex-col gap-1">
            {items.map((language) => (
                <li key={language.id} className="flex items-center gap-2">
                    {language.name}
                    <LevelMeter level={language.level} className="text-muted-foreground" />
                    <span className="text-muted-foreground font-normal">{languageLevelLabels[language.level]}</span>
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

type Neighbour = { id: number; name: string } | null;

/** Arrows to the previous and next colleague in the list; ← and → keys do the same. */
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

    const arrow = (to: Neighbour, label: string, Icon: typeof ChevronLeft) => (
        <Button
            variant="outline"
            size="icon"
            className="size-9"
            disabled={!to}
            aria-label={to ? `${label}: ${to.name}` : label}
            title={to?.name}
            asChild={!!to}
        >
            {to ? (
                <Link href={route('employees.show', to.id)} prefetch>
                    <Icon />
                </Link>
            ) : (
                <Icon />
            )}
        </Button>
    );

    return (
        <div className="flex gap-1">
            {arrow(prev, 'Предыдущий сотрудник', ChevronLeft)}
            {arrow(next, 'Следующий сотрудник', ChevronRight)}
        </div>
    );
}

export default function EmployeeProfile({ employee, neighbours }: { employee: Employee; neighbours: { prev: Neighbour; next: Neighbour } }) {
    const shortName = `${employee.surname} ${employee.name}`;
    const fullName = [employee.surname, employee.name, employee.patronymic].filter(Boolean).join(' ');
    const details = employee.private;
    const { auth } = usePage<SharedData>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Сотрудники', href: '/employees' },
        { title: shortName, href: `/employees/${employee.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={shortName} />

            <div className="flex flex-1 flex-col gap-4 p-3 md:px-5 md:py-4">
                <Card className="flex flex-col gap-5 rounded-xl p-6 sm:flex-row sm:items-center">
                    {employee.avatar ? (
                        <img src={employee.avatar} alt="" className="size-[88px] shrink-0 rounded-full object-cover" />
                    ) : (
                        <PersonAvatar name={`${employee.name} ${employee.surname}`} className="size-[88px] text-[29px]" />
                    )}

                    <div className="flex min-w-0 flex-1 flex-col gap-2">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-[28px] leading-tight font-bold tracking-tight">{fullName}</h1>
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
                            {employee.positions.map((title) => (
                                <StatusBadge key={title} tone="success">
                                    {title}
                                </StatusBadge>
                            ))}
                        </div>

                        <div className="text-muted-foreground flex flex-wrap gap-x-5 gap-y-1 text-sm">
                            <a
                                href={`mailto:${employee.email}`}
                                className="text-brand-strong flex items-center gap-1.5 hover:underline dark:text-[#C5E27A]"
                            >
                                <Mail className="size-4" />
                                {employee.email}
                            </a>
                            {details?.phone && (
                                <a href={`tel:${details.phone}`} className="flex items-center gap-1.5 tabular-nums hover:underline">
                                    <Phone className="size-4" />
                                    {formatPhone(details.phone)}
                                </a>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2 self-start sm:self-center">
                        {auth.can.manageEmployees && (
                            <Button variant="outline" asChild>
                                <Link href={route('employees.edit', employee.id)}>
                                    <Pencil />
                                    Редактировать
                                </Link>
                            </Button>
                        )}
                        <Neighbours prev={neighbours.prev} next={neighbours.next} />
                    </div>
                </Card>

                {details ? (
                    <div className="grid items-start gap-4 lg:grid-cols-3">
                        <div className="flex flex-col gap-4">
                            <Section title="Личные данные">
                                <Fields>
                                    <Field label="Дата рождения">
                                        {details.birth_date && (
                                            <>
                                                {formatDate(details.birth_date)}
                                                <span className="text-muted-foreground font-normal"> · {age(details.birth_date)}</span>
                                            </>
                                        )}
                                    </Field>
                                    <Field label="Место рождения">{details.birth_place}</Field>
                                    <Field label="Пол">{sexLabels[employee.sex]}</Field>
                                    <Field label="Национальность">{details.nationality && capitalize(details.nationality)}</Field>
                                    <Field label="Гражданство">{details.citizenship}</Field>
                                    <Field label="Семейное положение">
                                        {details.marital_status && maritalLabels[employee.sex][details.marital_status]}
                                    </Field>
                                </Fields>
                            </Section>

                            <Section title="Образование">
                                {details.educations.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Не указано</p>
                                ) : (
                                    <Educations items={details.educations} />
                                )}
                            </Section>

                            <Section title="Трудовая деятельность">
                                {details.work_experiences.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Не указана</p>
                                ) : (
                                    <WorkExperiences items={details.work_experiences} />
                                )}
                            </Section>
                        </div>

                        <div className="flex flex-col gap-4">
                            <Section title="Контакты">
                                <Fields>
                                    <Field label="Телефон">
                                        {details.phone && (
                                            <a href={`tel:${details.phone}`} className="tabular-nums hover:underline">
                                                {formatPhone(details.phone)}
                                            </a>
                                        )}
                                    </Field>
                                    <Field label="Телефон SOS">
                                        {details.sos_phone && <SosPhone phone={details.sos_phone} contact={details.sos_contact} />}
                                    </Field>
                                    <Field label="Домашний адрес" wide>
                                        {details.home_address}
                                    </Field>
                                </Fields>
                            </Section>

                            <Section title="Паспорт">
                                <Fields>
                                    <Field label="Серия и номер">
                                        {(details.passport.series || details.passport.number) && (
                                            <span className="tabular-nums">
                                                {[details.passport.series, details.passport.number].filter(Boolean).join(' ')}
                                            </span>
                                        )}
                                    </Field>
                                    <Field label="Дата выдачи">{formatDate(details.passport.issued_at)}</Field>
                                    <Field label="Кем выдан" wide>
                                        {details.passport.issued_by}
                                    </Field>
                                </Fields>
                            </Section>
                        </div>

                        <div className="flex flex-col gap-4">
                            <Section title="Работа">
                                <Fields>
                                    <Field label={employee.roles.length > 1 ? 'Позиции' : 'Позиция'} wide>
                                        {employee.roles.length > 0 ? employee.roles.join(', ') : null}
                                    </Field>
                                    <Field label={employee.positions.length > 1 ? 'Должности' : 'Должность'} wide>
                                        {employee.positions.length > 0 ? employee.positions.join(', ') : null}
                                    </Field>
                                    <Field label={employee.departments.length > 1 ? 'Отделы' : 'Отдел'} wide>
                                        {employee.departments.length > 0 ? <Departments items={employee.departments} /> : null}
                                    </Field>
                                    <Field label="Языки" wide>
                                        {employee.languages.length > 0 ? <Languages items={employee.languages} /> : null}
                                    </Field>
                                    <Field label="Начало работы">{formatDate(details.hired_at)}</Field>
                                    <Field label="Стаж в компании">{details.hired_at && tenure(details.hired_at)}</Field>
                                </Fields>
                            </Section>

                            <Section title="Дети">
                                {details.children.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Нет</p>
                                ) : (
                                    <ul className="flex flex-col">
                                        {details.children.map((child) => (
                                            <li
                                                key={child.full_name + child.birth_date}
                                                className="flex items-center gap-3 border-t py-3 first:border-t-0 first:pt-0"
                                            >
                                                <PersonAvatar name={child.full_name} className="size-8" />
                                                <div className="flex flex-col">
                                                    <span className="text-sm font-medium">{child.full_name}</span>
                                                    {child.birth_date && (
                                                        <span className="text-muted-foreground text-[13px]">
                                                            {formatDate(child.birth_date)} · {age(child.birth_date)}
                                                        </span>
                                                    )}
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </Section>
                        </div>
                    </div>
                ) : (
                    <div className="grid items-start gap-4 lg:grid-cols-3">
                        <Section title="Основное">
                            <Fields>
                                <Field label={employee.roles.length > 1 ? 'Позиции' : 'Позиция'} wide>
                                    {employee.roles.length > 0 ? employee.roles.join(', ') : null}
                                </Field>
                                <Field label={employee.positions.length > 1 ? 'Должности' : 'Должность'} wide>
                                    {employee.positions.length > 0 ? employee.positions.join(', ') : null}
                                </Field>
                                <Field label={employee.departments.length > 1 ? 'Отделы' : 'Отдел'} wide>
                                    {employee.departments.length > 0 ? <Departments items={employee.departments} /> : null}
                                </Field>
                                <Field label="Языки" wide>
                                    {employee.languages.length > 0 ? <Languages items={employee.languages} /> : null}
                                </Field>
                                <Field label="Пол">{sexLabels[employee.sex]}</Field>
                            </Fields>
                        </Section>

                        <Card className="text-muted-foreground flex items-start gap-3 rounded-xl px-6 py-5 text-sm lg:col-span-2">
                            <Lock className="mt-0.5 size-5 shrink-0" />
                            <p>
                                Личные данные, контакты, паспорт и семья закрыты. Их видят только сам сотрудник, его руководитель, HR и администратор.
                            </p>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
