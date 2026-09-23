import { StatusBadge, type StatusTone } from '@/components/status-badge';
import { languageLevelLabels, languageLevels, type LanguageLevel, type SpokenLanguage } from '@/lib/employee';
import { cn } from '@/lib/utils';

/**
 * A colour per level, so the list can be read at a glance: grey for a beginner,
 * blue in between, the brand green once someone is fluent.
 */
const levelTones: Record<LanguageLevel, StatusTone> = {
    beginner: 'neutral',
    intermediate: 'info',
    advanced: 'success',
};

/** The level spelled out next to its meter, in a colour of its own. */
export function LevelBadge({ level, className }: { level: LanguageLevel; className?: string }) {
    return (
        <StatusBadge tone={levelTones[level]} className={cn('gap-1.5', className)}>
            <LevelMeter level={level} />
            {languageLevelLabels[level]}
        </StatusBadge>
    );
}

/** Three bars, filled up to the level: one for beginner, all three for advanced. */
export function LevelMeter({ level, className }: { level: LanguageLevel; className?: string }) {
    const filled = languageLevels.indexOf(level) + 1;

    return (
        <span aria-hidden="true" className={cn('flex items-end gap-px', className)}>
            {languageLevels.map((step, index) => (
                <span
                    key={step}
                    className={cn('w-[3px] rounded-[1px]', index < filled ? 'bg-current' : 'bg-current opacity-25')}
                    style={{ height: 4 + index * 2 }}
                />
            ))}
        </span>
    );
}

/** "Английский ▂▄▆": the name with a level meter; the level is spelled out for screen readers and on hover. */
export function LanguageBadges({ languages }: { languages: SpokenLanguage[] }) {
    return (
        <div className="flex flex-wrap gap-1 whitespace-normal">
            {languages.map((language) => (
                <StatusBadge
                    key={language.id}
                    tone="neutral"
                    title={`${language.name} — ${languageLevelLabels[language.level].toLowerCase()}`}
                    className="gap-1.5"
                >
                    {language.name}
                    <LevelMeter level={language.level} className="opacity-80" />
                    <span className="sr-only">, {languageLevelLabels[language.level].toLowerCase()}</span>
                </StatusBadge>
            ))}
        </div>
    );
}
