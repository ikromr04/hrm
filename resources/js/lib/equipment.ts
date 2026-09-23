import { type StatusTone } from '@/components/status-badge';
import { Headphones, Laptop, Monitor, Printer, Smartphone, type LucideIcon } from 'lucide-react';

export type EquipmentStatus = 'issued' | 'stock' | 'repair' | 'written_off';

/** The colours the design gives each status; they are the app's own tones. */
export const statusTone: Record<EquipmentStatus, StatusTone> = {
    issued: 'success',
    stock: 'neutral',
    repair: 'warning',
    written_off: 'danger',
};

export const statusLabel: Record<EquipmentStatus, string> = {
    issued: 'Выдано',
    stock: 'На складе',
    repair: 'В ремонте',
    written_off: 'Списано',
};

/** An icon per category, as the design draws them; anything else gets a box. */
export const categoryIcon: Record<string, LucideIcon> = {
    Ноутбуки: Laptop,
    Мониторы: Monitor,
    Телефоны: Smartphone,
    Печать: Printer,
    Периферия: Headphones,
};

/** "9 800 сомони"; nothing at all when the price was never written down. */
export function formatPrice(value: string | number | null): string | null {
    if (value === null || value === '') return null;

    const amount = Number(value);
    if (Number.isNaN(amount)) return null;

    // Whole somoni read better than trailing zeroes on a card, and the
    // non-breaking spaces ru-RU groups thousands with become plain ones.
    const grouped = Math.round(amount).toLocaleString('ru-RU').replace(/\s/g, ' ');

    return `${grouped} сомони`;
}

/** "декабрь 2026": an inventory is due in a month, not on a day. */
export function formatMonth(date: string | null): string | null {
    if (!date) return null;

    const parsed = new Date(date);

    return `${parsed.toLocaleDateString('ru-RU', { month: 'long' })} ${parsed.getFullYear()}`;
}
