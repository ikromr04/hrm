import { categoryIcon } from '@/lib/equipment';
import { cn } from '@/lib/utils';
import { Package, type LucideIcon } from 'lucide-react';

const tones = {
    brand: 'bg-[#EEF5DC] text-[#4A6410] dark:bg-[#A8CF45]/15 dark:text-[#C5E27A]',
    warning: 'bg-[#FBEFD9] text-[#9A4A06] dark:bg-[#F5A524]/15 dark:text-[#F8C471]',
    neutral: 'bg-[#F4F4F5] text-[#44474C] dark:bg-white/10 dark:text-neutral-300',
};

/** The tinted square an icon sits in, in the tiles and beside every name. */
export function IconChip({
    icon: Icon,
    tone = 'neutral',
    size = 36,
    iconSize = 18,
}: {
    icon: LucideIcon;
    tone?: keyof typeof tones;
    size?: number;
    iconSize?: number;
}) {
    return (
        <span
            aria-hidden="true"
            style={{ width: size, height: size }}
            className={cn('flex shrink-0 items-center justify-center rounded-lg', tones[tone])}
        >
            <Icon style={{ width: iconSize, height: iconSize }} />
        </span>
    );
}

/** The chip for a unit, picked from its category. */
export function CategoryChip({ type, size, iconSize }: { type: string | null; size?: number; iconSize?: number }) {
    return <IconChip icon={(type && categoryIcon[type]) || Package} size={size} iconSize={iconSize} />;
}
