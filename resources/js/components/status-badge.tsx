import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'react';

export type StatusTone = 'success' | 'warning' | 'info' | 'danger' | 'neutral';

const tones: Record<StatusTone, string> = {
    success: 'bg-[#EEF5DC] text-[#4A6410] dark:bg-[#A8CF45]/15 dark:text-[#C5E27A]',
    warning: 'bg-[#FBEFD9] text-[#9A4A06] dark:bg-[#F5A524]/15 dark:text-[#F8C471]',
    info: 'bg-[#E4ECFD] text-[#1D4ED8] dark:bg-[#3B82F6]/15 dark:text-[#93B4FA]',
    danger: 'bg-[#FDE8E6] text-[#B42318] dark:bg-[#EF4444]/15 dark:text-[#F7A19A]',
    neutral: 'bg-[#F4F4F5] text-[#44474C] dark:bg-white/10 dark:text-neutral-300',
};

export function StatusBadge({ tone = 'neutral', className, ...props }: HTMLAttributes<HTMLSpanElement> & { tone?: StatusTone }) {
    return (
        <span
            className={cn('inline-flex h-[22px] items-center rounded-md px-2 text-xs font-medium whitespace-nowrap', tones[tone], className)}
            {...props}
        />
    );
}
