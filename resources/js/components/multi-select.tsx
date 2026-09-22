import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
import { useState } from 'react';

export interface MultiSelectOption<T extends string | number> {
    value: T;
    label: string;
    /** Indent level, for trees such as departments. */
    depth?: number;
}

/** Choose any number of options from a searchable list; the choice shows as removable chips. */
export function MultiSelect<T extends string | number>({
    id,
    options,
    value,
    onChange,
    placeholder = 'Не выбрано',
    searchPlaceholder = 'Поиск',
    chipClassName,
}: {
    id?: string;
    options: MultiSelectOption<T>[];
    value: T[];
    onChange: (value: T[]) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    chipClassName?: string;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = options.filter((option) => value.includes(option.value));

    const term = query.trim().toLowerCase();
    // While searching, the tree is flattened: indentation would mislead.
    const matches = term ? options.filter((option) => option.label.toLowerCase().includes(term)) : options;

    const toggle = (next: T) => onChange(value.includes(next) ? value.filter((v) => v !== next) : [...value, next]);

    return (
        <div className="flex flex-col gap-2">
            {selected.length > 0 && (
                <ul className="flex flex-wrap gap-1.5">
                    {selected.map((option) => (
                        <li
                            key={option.value}
                            className={cn('bg-muted flex items-center gap-1 rounded-md py-0.5 pr-0.5 pl-2 text-sm', chipClassName)}
                        >
                            {option.label}
                            <button
                                type="button"
                                onClick={() => toggle(option.value)}
                                aria-label={`Убрать: ${option.label}`}
                                className="rounded-sm p-0.5 opacity-60 hover:bg-black/10 hover:opacity-100 dark:hover:bg-white/10"
                            >
                                <X className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <Popover
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);
                    if (!next) setQuery('');
                }}
            >
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="w-full justify-between font-normal"
                    >
                        <span className="text-muted-foreground">{selected.length ? 'Добавить ещё…' : placeholder}</span>
                        <ChevronsUpDown className="text-muted-foreground" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="start" className="w-(--radix-popover-trigger-width) p-0">
                    <label className="flex items-center gap-2 border-b px-3">
                        <Search className="text-muted-foreground size-4 shrink-0" />
                        <span className="sr-only">{searchPlaceholder}</span>
                        <input
                            autoFocus
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder={searchPlaceholder}
                            className="h-9 min-w-0 flex-1 bg-transparent text-sm outline-hidden"
                        />
                    </label>
                    <ul role="listbox" aria-multiselectable="true" className="max-h-64 overflow-y-auto p-1">
                        {matches.map((option) => (
                            <li key={option.value}>
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={value.includes(option.value)}
                                    onClick={() => toggle(option.value)}
                                    className="hover:bg-accent flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm"
                                >
                                    <Check className={cn('size-4 shrink-0', value.includes(option.value) ? 'opacity-100' : 'opacity-0')} />
                                    <span className="truncate" style={term ? undefined : { paddingLeft: (option.depth ?? 0) * 16 }}>
                                        {option.label}
                                    </span>
                                </button>
                            </li>
                        ))}
                        {matches.length === 0 && <li className="text-muted-foreground px-2 py-3 text-center text-sm">Ничего не нашлось</li>}
                    </ul>
                </PopoverContent>
            </Popover>
        </div>
    );
}
