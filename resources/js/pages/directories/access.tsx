import {
    CardFieldsButton,
    CardFieldsDialog,
    countCardFields,
    RightsButton,
    RightsDialog,
    type CardFieldGroup,
    type CardFieldsMode,
} from '@/components/card-fields';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import DirectoriesLayout from '@/layouts/directories-layout';
import { plural } from '@/lib/plural';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { Check, Lock, Search } from 'lucide-react';
import { Fragment, useMemo, useState } from 'react';

interface Right {
    key: string;
    title: string;
    hint: string;
}

interface Section {
    key: string;
    title: string;
    rights: Right[];
}

interface RoleRow {
    id: number;
    name: string;
    title: string;
    users_count: number;
    /** An access role: it answers yes to everything, whatever the table says. */
    everything: boolean;
    permissions: string[];
}

/** The one section whose columns are not a plain row of checkboxes. */
const EMPLOYEES = 'employees';

/**
 * Whether the section opens at all. It is never ticked by hand: a position that
 * reads not a single line of a card would find nothing but empty rows there, so
 * the right follows the choice of fields instead of sitting beside it.
 */
const EMPLOYEES_VIEW = 'employees.view';

/** What one does to a colleague rather than to a line of their card. */
const ACTIONS = ['employees.transfer', 'employees.fire', 'employees.delete'];

/** A column of the table: a right to tick, or a door to a window of rights. */
interface Column {
    key: string;
    title: string;
    hint: string;
    width: string;
}

/**
 * The three columns of «Сотрудники». Two of them are not rights but counters:
 * the lines of a card are four dozen rights and are chosen in a window, with the
 * column showing how much of the card is open. The third holds what one does to
 * the person.
 */
const EMPLOYEE_COLUMNS: Column[] = [
    {
        key: 'view',
        title: 'Просмотр',
        hint: 'Какие строки карточки позиция видит — в карточке, в колонках таблицы, в фильтрах и в поиске. Пока не выбрана ни одна строка, раздел «Сотрудники» для позиции закрыт.',
        width: 'w-36',
    },
    {
        key: 'edit',
        title: 'Изменение',
        hint: 'Какие строки позиция может править. Менять можно только то, что видно: строка, закрытая в «Просмотре», недоступна и здесь.',
        width: 'w-36',
    },
    {
        key: 'actions',
        title: 'Действия',
        hint: 'Что делают с самим сотрудником, а не со строкой его карточки: перевод в другой отдел или на другую должность, увольнение и удаление.',
        width: 'w-40',
    },
];

/** What the section spans across the table: its rights, or the three columns of «Сотрудники». */
const columnsOf = (section: Section): Column[] =>
    section.key === EMPLOYEES ? EMPLOYEE_COLUMNS : section.rights.map((right) => ({ ...right, width: 'w-28' }));

/**
 * Who may do what: positions down the side, rights across the top.
 *
 * A tick is saved the moment it is made — a page of three hundred checkboxes
 * with one "Сохранить" at the bottom is a page where half the work is lost to a
 * stray reload. The row is sent whole, so the server never has to guess.
 */
export default function AccessPage({ sections, roles, fields }: { sections: Section[]; roles: RoleRow[]; fields: CardFieldGroup[] }) {
    const [query, setQuery] = useState('');
    // Whose card fields are being chosen, and which half of them. The position is
    // kept by id rather than as the row it was opened from, so the window shows
    // what has just been saved instead of the copy that is now stale.
    const [picking, setPicking] = useState<{ id: number; mode: CardFieldsMode | 'actions' } | null>(null);
    // What the table shows while a save is in flight, so a tick answers at once.
    const [pending, setPending] = useState<Record<number, string[]>>({});

    const rights = useMemo(() => sections.flatMap((section) => section.rights), [sections]);
    // The actions keep the names and hints the server gives them; only where they
    // sit in the table is decided here.
    const actions = useMemo(
        () => sections.find((section) => section.key === EMPLOYEES)?.rights.filter((right) => ACTIONS.includes(right.key)) ?? [],
        [sections],
    );
    const columnCount = useMemo(() => sections.reduce((total, section) => total + columnsOf(section).length, 0), [sections]);

    const term = query.trim().toLowerCase();
    const visible = term ? roles.filter((role) => role.title.toLowerCase().includes(term)) : roles;

    const held = (role: RoleRow) => pending[role.id] ?? role.permissions;
    const picked = picking ? (roles.find((role) => role.id === picking.id) ?? null) : null;

    const save = (role: RoleRow, permissions: string[]) => {
        setPending((current) => ({ ...current, [role.id]: permissions }));

        router.put(
            route('directories.access.update', role.id),
            { permissions },
            {
                preserveScroll: true,
                // The row is settled once the page comes back with it.
                onFinish: () => setPending((current) => Object.fromEntries(Object.entries(current).filter(([id]) => Number(id) !== role.id))),
            },
        );
    };

    const toggle = (role: RoleRow, key: string) => {
        const current = held(role);

        save(role, current.includes(key) ? current.filter((name) => name !== key) : [...current, key]);
    };

    /**
     * The view window also answers whether the section opens: the first line
     * chosen brings "employees.view" with it, the last one taken away removes it
     * again. A position with no readable line has nothing to open.
     */
    const saveFields = (role: RoleRow, mode: CardFieldsMode, permissions: string[]) => {
        if (mode === 'edit') {
            save(role, permissions);

            return;
        }

        const rest = permissions.filter((name) => name !== EMPLOYEES_VIEW);

        save(role, countCardFields(fields, rest, 'view').chosen > 0 ? [...rest, EMPLOYEES_VIEW] : rest);
    };

    return (
        <DirectoriesLayout title="Доступы">
            <div className="-mb-2 flex flex-wrap items-center gap-2">
                <label className="border-input bg-background text-muted-foreground focus-within:ring-ring flex h-8 min-w-48 flex-1 items-center gap-2 rounded-md border px-3 shadow-xs focus-within:ring-2">
                    <Search className="size-4 shrink-0" />
                    <span className="sr-only">Поиск</span>
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Поиск по позиции"
                        className="text-foreground min-w-0 flex-1 bg-transparent text-sm outline-hidden"
                    />
                </label>
                <p className="text-muted-foreground text-sm">
                    {rights.length} {plural(rights.length, ['доступ', 'доступа', 'доступов'])} · изменения сохраняются сразу
                </p>
            </div>

            <Card className="flex flex-col gap-0 overflow-hidden rounded-xl p-0 md:min-h-0 md:flex-1">
                <div className="overflow-auto md:min-h-0 md:flex-1">
                    <table className="w-full border-collapse text-sm">
                        <thead className="bg-sidebar sticky top-0 z-20 shadow-[0_1px_0_var(--border)]">
                            <tr className="text-muted-foreground text-left text-[13px]">
                                <th
                                    scope="col"
                                    rowSpan={2}
                                    className="bg-sidebar sticky left-0 z-30 min-w-56 px-6 py-3 font-semibold shadow-[1px_0_0_var(--border)]"
                                >
                                    Позиция
                                </th>
                                {sections.map((section) => (
                                    <th
                                        key={section.key}
                                        scope="colgroup"
                                        colSpan={columnsOf(section).length}
                                        className="border-l px-4 pt-3 pb-1 text-center font-semibold"
                                    >
                                        {section.title}
                                    </th>
                                ))}
                            </tr>
                            <tr className="text-muted-foreground text-left text-[12px]">
                                {sections.flatMap((section) =>
                                    columnsOf(section).map((column, index) => (
                                        <th
                                            key={column.key}
                                            scope="col"
                                            className={cn('px-2 pb-2.5 align-bottom font-medium', column.width, index === 0 && 'border-l')}
                                        >
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <span className="mx-auto block max-w-24 cursor-help text-center leading-tight">
                                                        {column.title}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent className="max-w-64">{column.hint}</TooltipContent>
                                            </Tooltip>
                                        </th>
                                    )),
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {visible.map((role) => (
                                <tr key={role.id} className="hover:bg-muted/40 group border-t">
                                    <th
                                        scope="row"
                                        className="bg-background group-hover:bg-muted/40 sticky left-0 z-10 px-6 py-2.5 text-left font-normal shadow-[1px_0_0_var(--border)]"
                                    >
                                        <span className="flex items-center gap-2">
                                            <span>{role.title}</span>
                                            {role.everything && (
                                                <Lock className="text-muted-foreground size-3.5" aria-label="Все доступы: изменить нельзя" />
                                            )}
                                            <span className="text-muted-foreground text-xs tabular-nums">{role.users_count}</span>
                                        </span>
                                    </th>

                                    {sections.map((section) =>
                                        section.key === EMPLOYEES ? (
                                            <Fragment key={section.key}>
                                                {(['view', 'edit'] as CardFieldsMode[]).map((mode, index) => (
                                                    <td key={mode} className={cn('px-2 py-2.5 text-center', index === 0 && 'border-l')}>
                                                        {role.everything ? (
                                                            <span className="text-muted-foreground text-[13px]">все</span>
                                                        ) : (
                                                            <CardFieldsButton
                                                                mode={mode}
                                                                groups={fields}
                                                                held={held(role)}
                                                                onOpen={() => setPicking({ id: role.id, mode })}
                                                            />
                                                        )}
                                                    </td>
                                                ))}

                                                <td className="px-2 py-2.5 text-center">
                                                    {role.everything ? (
                                                        <span className="text-muted-foreground text-[13px]">все</span>
                                                    ) : (
                                                        // Behind the same counter as the two columns beside it:
                                                        // three loose boxes in a cell read as a different kind
                                                        // of answer, and the row should read as one.
                                                        <RightsButton
                                                            chosen={actions.filter((right) => held(role).includes(right.key)).length}
                                                            total={actions.length}
                                                            title="Действия с сотрудником"
                                                            onOpen={() => setPicking({ id: role.id, mode: 'actions' })}
                                                        />
                                                    )}
                                                </td>
                                            </Fragment>
                                        ) : (
                                            <Fragment key={section.key}>
                                                {section.rights.map((right, index) => (
                                                    <td key={right.key} className={cn('px-2 py-2.5 text-center', index === 0 && 'border-l')}>
                                                        {role.everything ? (
                                                            <Check
                                                                className="text-muted-foreground mx-auto size-4"
                                                                aria-label={`${role.title}: ${right.title} — есть всегда`}
                                                            />
                                                        ) : (
                                                            <Checkbox
                                                                checked={held(role).includes(right.key)}
                                                                onCheckedChange={() => toggle(role, right.key)}
                                                                aria-label={`${role.title}: ${section.title} — ${right.title}`}
                                                                className="mx-auto"
                                                            />
                                                        )}
                                                    </td>
                                                ))}
                                            </Fragment>
                                        ),
                                    )}
                                </tr>
                            ))}

                            {visible.length === 0 && (
                                <tr className="border-t">
                                    <td colSpan={columnCount + 1} className="text-muted-foreground px-6 py-12 text-center">
                                        Ничего не найдено.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            <p className="text-muted-foreground text-sm">
                Администратор и системный администратор проходят любую проверку, поэтому их строки отмечены целиком. Отдельному сотруднику доступ
                можно выдать или снять в его карточке.
            </p>

            {picking && picked && picking.mode === 'actions' && (
                <RightsDialog
                    title={`Действия с сотрудником: ${picked.title}`}
                    description="Что эта позиция делает с самим сотрудником, а не со строкой его карточки. Восстановить уволенного может тот, кто может уволить."
                    rights={actions}
                    held={held(picked)}
                    onChange={(permissions) => save(picked, permissions)}
                    onClose={() => setPicking(null)}
                />
            )}

            {picking && picked && picking.mode !== 'actions' && (
                <CardFieldsDialog
                    mode={picking.mode}
                    subject={picked.title}
                    groups={fields}
                    held={held(picked)}
                    onChange={(permissions) => saveFields(picked, picking.mode as CardFieldsMode, permissions)}
                    onClose={() => setPicking(null)}
                />
            )}
        </DirectoriesLayout>
    );
}
