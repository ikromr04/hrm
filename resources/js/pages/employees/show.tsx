import { PersonAvatar } from '@/components/person-avatar';
import { SosPhone } from '@/components/phones';
import { StatusBadge } from '@/components/status-badge';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { age, capitalize, formatDate, formatPhone, maritalLabels, sexLabels, tenure, type PrivateDetails, type Sex } from '@/lib/employee';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Lock, Mail, Phone } from 'lucide-react';
import { type ReactNode } from 'react';

interface ProfilePrivate extends PrivateDetails {
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
    /** Position titles; an employee can hold several. */
    roles: string[];
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

export default function EmployeeProfile({ employee }: { employee: Employee }) {
    const shortName = `${employee.surname} ${employee.name}`;
    const fullName = [employee.surname, employee.name, employee.patronymic].filter(Boolean).join(' ');
    const details = employee.private;

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

                    <div className="flex min-w-0 flex-col gap-2">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-[28px] leading-tight font-bold tracking-tight">{fullName}</h1>
                            {employee.roles.map((role) => (
                                <StatusBadge key={role} tone="success">
                                    {role}
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
                </Card>

                {details ? (
                    <div className="grid items-start gap-4 lg:grid-cols-3">
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
                                    <Field label="Телефон SOS">{details.sos_phone && <SosPhone phone={details.sos_phone} />}</Field>
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
