import { plural } from '@/lib/plural';
import { differenceInMonths, differenceInYears, format, parseISO } from 'date-fns';

export type Sex = 'male' | 'female';
export type Marital = 'single' | 'married';

/** Private data, sent only to viewers allowed to see it. */
export interface PrivateDetails {
    birth_date: string | null;
    nationality: string | null;
    citizenship: string | null;
    home_address: string | null;
    phone: string | null;
    sos_phone: string | null;
    marital_status: Marital | null;
    hired_at: string | null;
    children: { full_name: string; birth_date: string | null }[];
}

export const sexLabels: Record<Sex, string> = { male: 'Мужской', female: 'Женский' };

export const maritalLabels: Record<Sex, Record<Marital, string>> = {
    male: { single: 'Не женат', married: 'Женат' },
    female: { single: 'Не замужем', married: 'Замужем' },
};

/** "2021-03-12" -> "12.03.2021" */
export const formatDate = (value: string | null) => (value ? format(parseISO(value), 'dd.MM.yyyy') : null);

export const capitalize = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);

/** +992901243299 -> +992 90 124 32 99 */
export const formatPhone = (phone: string) => phone.replace(/^\+992(\d{2})(\d{3})(\d{2})(\d{2})$/, '+992 $1 $2 $3 $4');

/** "34 года" */
export function age(birthDate: string): string {
    const years = differenceInYears(new Date(), parseISO(birthDate));

    return `${years} ${plural(years, ['год', 'года', 'лет'])}`;
}

/** Time since a date: "5 лет 6 мес.", "3 мес." */
export function tenure(since: string): string {
    const months = differenceInMonths(new Date(), parseISO(since));
    const years = Math.floor(months / 12);
    const rest = months % 12;

    return [years > 0 && `${years} ${plural(years, ['год', 'года', 'лет'])}`, (rest > 0 || years === 0) && `${rest} мес.`].filter(Boolean).join(' ');
}

/** "5 сотрудников" */
export const peopleLabel = (count: number) => `${count} ${plural(count, ['сотрудник', 'сотрудника', 'сотрудников'])}`;

export type LanguageLevel = 'beginner' | 'intermediate' | 'advanced';

/** From least to most fluent. */
export const languageLevels: LanguageLevel[] = ['beginner', 'intermediate', 'advanced'];

export const languageLevelLabels: Record<LanguageLevel, string> = {
    beginner: 'Начальный',
    intermediate: 'Средний',
    advanced: 'Продвинутый',
};

/** A language an employee speaks, public like positions. */
export interface SpokenLanguage {
    id: number;
    name: string;
    level: LanguageLevel;
}
