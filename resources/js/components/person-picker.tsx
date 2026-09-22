import { PersonAvatar } from '@/components/person-avatar';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
import { useState } from 'react';

export interface PickablePerson {
    id: number;
    /** "Фамилия Имя" */
    name: string;
    email: string;
}

/** Choose any number of people from a searchable list. */
export function PeoplePicker({
    id,
    people,
    value,
    onChange,
    emptyLabel = 'Никто не выбран',
}: {
    id?: string;
    people: PickablePerson[];
    value: number[];
    onChange: (value: number[]) => void;
    emptyLabel?: string;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = people.filter((person) => value.includes(person.id));

    const term = query.trim().toLowerCase();
    const matches = term ? people.filter((person) => `${person.name} ${person.email}`.toLowerCase().includes(term)) : people;

    const toggle = (personId: number) => onChange(value.includes(personId) ? value.filter((v) => v !== personId) : [...value, personId]);

    return (
        <div className="flex flex-col gap-2">
            {selected.length > 0 && (
                <ul className="flex flex-wrap gap-1.5">
                    {selected.map((person) => (
                        <li key={person.id} className="bg-muted flex items-center gap-1.5 rounded-full py-0.5 pr-1 pl-0.5 text-sm">
                            <PersonAvatar name={person.name} className="size-5 text-[9px]" />
                            {person.name}
                            <button
                                type="button"
                                onClick={() => toggle(person.id)}
                                aria-label={`Убрать: ${person.name}`}
                                className="text-muted-foreground hover:bg-background hover:text-foreground rounded-full p-0.5"
                            >
                                <X className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="w-full justify-between font-normal"
                    >
                        <span className="text-muted-foreground">{selected.length ? 'Добавить ещё…' : emptyLabel}</span>
                        <ChevronsUpDown className="text-muted-foreground" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="start" className="w-(--radix-popover-trigger-width) p-0">
                    <label className="flex items-center gap-2 border-b px-3">
                        <Search className="text-muted-foreground size-4 shrink-0" />
                        <span className="sr-only">Поиск сотрудника</span>
                        <input
                            autoFocus
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="ФИО или почта"
                            className="h-9 min-w-0 flex-1 bg-transparent text-sm outline-hidden"
                        />
                    </label>
                    <ul role="listbox" aria-multiselectable="true" className="max-h-64 overflow-y-auto p-1">
                        {matches.map((person) => (
                            <li key={person.id}>
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={value.includes(person.id)}
                                    onClick={() => toggle(person.id)}
                                    className="hover:bg-accent flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm"
                                >
                                    <Check className={cn('size-4 shrink-0', value.includes(person.id) ? 'opacity-100' : 'opacity-0')} />
                                    <PersonAvatar name={person.name} className="size-6 text-[10px]" />
                                    <span className="flex min-w-0 flex-col">
                                        <span className="truncate">{person.name}</span>
                                        <span className="text-muted-foreground truncate text-xs">{person.email}</span>
                                    </span>
                                </button>
                            </li>
                        ))}
                        {matches.length === 0 && <li className="text-muted-foreground px-2 py-3 text-center text-sm">Никого не нашлось</li>}
                    </ul>
                </PopoverContent>
            </Popover>
        </div>
    );
}

/** Choose one person from a searchable list, or nobody. */
export function PersonPicker({
    id,
    people,
    value,
    onChange,
    emptyLabel = 'Не выбран',
}: {
    id?: string;
    people: PickablePerson[];
    value: number | null;
    onChange: (value: number | null) => void;
    emptyLabel?: string;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = people.find((person) => person.id === value) ?? null;

    const term = query.trim().toLowerCase();
    const matches = term ? people.filter((person) => `${person.name} ${person.email}`.toLowerCase().includes(term)) : people;

    const pick = (next: number | null) => {
        onChange(next);
        setOpen(false);
        setQuery('');
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button id={id} type="button" variant="outline" role="combobox" aria-expanded={open} className="w-full justify-between font-normal">
                    {selected ? (
                        <span className="flex min-w-0 items-center gap-2">
                            <PersonAvatar name={selected.name} className="size-5 text-[9px]" />
                            <span className="truncate">{selected.name}</span>
                        </span>
                    ) : (
                        <span className="text-muted-foreground">{emptyLabel}</span>
                    )}
                    <ChevronsUpDown className="text-muted-foreground" />
                </Button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-(--radix-popover-trigger-width) p-0">
                <label className="flex items-center gap-2 border-b px-3">
                    <Search className="text-muted-foreground size-4 shrink-0" />
                    <span className="sr-only">Поиск сотрудника</span>
                    <input
                        autoFocus
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="ФИО или почта"
                        className="h-9 min-w-0 flex-1 bg-transparent text-sm outline-hidden"
                    />
                </label>
                <ul role="listbox" className="max-h-64 overflow-y-auto p-1">
                    <li>
                        <button
                            type="button"
                            role="option"
                            aria-selected={value === null}
                            onClick={() => pick(null)}
                            className="hover:bg-accent text-muted-foreground flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm"
                        >
                            <Check className={cn('size-4', value === null ? 'opacity-100' : 'opacity-0')} />
                            {emptyLabel}
                        </button>
                    </li>
                    {matches.map((person) => (
                        <li key={person.id}>
                            <button
                                type="button"
                                role="option"
                                aria-selected={value === person.id}
                                onClick={() => pick(person.id)}
                                className="hover:bg-accent flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm"
                            >
                                <Check className={cn('size-4 shrink-0', value === person.id ? 'opacity-100' : 'opacity-0')} />
                                <PersonAvatar name={person.name} className="size-6 text-[10px]" />
                                <span className="flex min-w-0 flex-col">
                                    <span className="truncate">{person.name}</span>
                                    <span className="text-muted-foreground truncate text-xs">{person.email}</span>
                                </span>
                            </button>
                        </li>
                    ))}
                    {matches.length === 0 && <li className="text-muted-foreground px-2 py-3 text-center text-sm">Никого не нашлось</li>}
                </ul>
            </PopoverContent>
        </Popover>
    );
}
