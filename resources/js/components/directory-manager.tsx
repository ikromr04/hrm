import InputError from '@/components/input-error';
import { PeoplePicker, type PickablePerson } from '@/components/person-picker';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { plural } from '@/lib/plural';
import { cn } from '@/lib/utils';
import { Link, router, useForm } from '@inertiajs/react';
import { LoaderCircle, Lock, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { type FormEventHandler, useMemo, useState } from 'react';

export interface DirectoryItem {
    id: number;
    label: string;
    /** Working employees who hold it; for departments, heads included. */
    users_count: number;
    /** Departments only: working employees here and in sub-departments, each counted once. */
    total_count?: number;
    /** System records that can be renamed but not deleted. */
    protected?: boolean;
    /** Departments only: the tree is built from these. */
    parent_id?: number | null;
    /** Departments only: who leads it; there can be several. */
    heads?: { id: number; name: string }[];
    /** Departments only: working members who do not lead it. */
    member_ids?: number[];
}

interface Labels {
    /** "Добавить позицию" */
    add: string;
    /** "Новая позиция" */
    create: string;
    /** "Изменить позицию" */
    edit: string;
    /** Accusative, for "Удалить позицию «…»?" */
    accusative: string;
}

interface DirectoryManagerProps {
    items: DirectoryItem[];
    /** Server field that holds the label. */
    field: 'title' | 'name';
    /** Route name prefix, e.g. "directories.roles". */
    route: string;
    labels: Labels;
    /** Link to the employee list filtered by this record. */
    employeesUrl: (item: DirectoryItem) => string;
    /** Show and edit the parent/child structure (departments). */
    tree?: boolean;
    /** When given, each record has heads and members chosen from these people (departments). */
    people?: PickablePerson[];
}

type Row = DirectoryItem & { depth: number };

/** Items in tree order (parents first, children indented), or as given when flat. */
function orderRows(items: DirectoryItem[], tree: boolean): Row[] {
    if (!tree) return items.map((item) => ({ ...item, depth: 0 }));

    const ids = new Set(items.map((item) => item.id));
    const walk = (parentId: number | null, depth: number, seen: Set<number>): Row[] =>
        items
            .filter((item) => (item.parent_id ?? null) === parentId || (parentId === null && item.parent_id != null && !ids.has(item.parent_id)))
            .filter((item) => !seen.has(item.id))
            .flatMap((item) => {
                seen.add(item.id);
                return [{ ...item, depth }, ...walk(item.id, depth + 1, seen)];
            });

    return walk(null, 0, new Set());
}

function descendantIds(items: DirectoryItem[], id: number): Set<number> {
    const result = new Set([id]);
    let level = [id];

    while (level.length) {
        level = items.filter((item) => item.parent_id != null && level.includes(item.parent_id) && !result.has(item.id)).map((item) => item.id);
        level.forEach((child) => result.add(child));
    }

    return result;
}

export function DirectoryManager({ items, field, route: routeName, labels, employeesUrl, tree = false, people }: DirectoryManagerProps) {
    const [query, setQuery] = useState('');
    const [editing, setEditing] = useState<DirectoryItem | 'new' | null>(null);
    const [deleting, setDeleting] = useState<DirectoryItem | null>(null);

    const rows = useMemo(() => orderRows(items, tree), [items, tree]);
    const visible = query.trim() ? rows.filter((row) => row.label.toLowerCase().includes(query.trim().toLowerCase())) : rows;

    return (
        <>
            <div className="-mb-2 flex flex-wrap items-center gap-2">
                <label className="border-input bg-background text-muted-foreground focus-within:ring-ring flex h-8 min-w-48 flex-1 items-center gap-2 rounded-md border px-3 shadow-xs focus-within:ring-2">
                    <Search className="size-4 shrink-0" />
                    <span className="sr-only">Поиск</span>
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Поиск по названию"
                        className="text-foreground min-w-0 flex-1 bg-transparent text-sm outline-hidden"
                    />
                </label>
                <Button className="h-8" onClick={() => setEditing('new')}>
                    <Plus />
                    {labels.add}
                </Button>
            </div>

            <Card className="flex flex-col gap-0 overflow-hidden rounded-xl p-0 md:min-h-0 md:flex-1">
                <div className="overflow-auto md:min-h-0 md:flex-1">
                    <table className="w-full border-collapse text-sm">
                        <thead className="bg-sidebar sticky top-0 z-10 shadow-[0_1px_0_var(--border)]">
                            <tr className="text-muted-foreground text-left text-[13px]">
                                <th scope="col" className="px-6 py-3 font-semibold">
                                    Название
                                </th>
                                {people && (
                                    <th scope="col" className="w-72 px-4 py-3 font-semibold">
                                        Руководители
                                    </th>
                                )}
                                <th scope="col" className="w-40 px-4 py-3 font-semibold">
                                    Сотрудников
                                </th>
                                <th scope="col" className="w-28 py-3 pr-6 pl-4">
                                    <span className="sr-only">Действия</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {visible.map((row) => (
                                <tr key={row.id} className="hover:bg-muted/40 border-t">
                                    <td className="px-6 py-2.5">
                                        <span className="flex items-center gap-2" style={{ paddingLeft: query ? 0 : row.depth * 24 }}>
                                            {tree && row.depth > 0 && !query && <span className="text-muted-foreground">└</span>}
                                            <span className={cn(tree && row.depth === 0 && 'font-semibold')}>{row.label}</span>
                                            {row.protected && (
                                                <Lock className="text-muted-foreground size-3.5" aria-label="Системная запись: удалить нельзя" />
                                            )}
                                        </span>
                                    </td>
                                    {people && (
                                        <td className="px-4 py-2.5">
                                            {row.heads?.length ? (
                                                <span className="flex flex-col gap-0.5">
                                                    {row.heads.map((head) => (
                                                        <Link
                                                            key={head.id}
                                                            href={route('employees.show', head.id)}
                                                            className="hover:text-brand-strong hover:underline dark:hover:text-[#C5E27A]"
                                                        >
                                                            {head.name}
                                                        </Link>
                                                    ))}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">Не назначен</span>
                                            )}
                                        </td>
                                    )}
                                    <td className="px-4 py-2.5 tabular-nums">
                                        {(row.total_count ?? row.users_count) > 0 ? (
                                            <Link href={employeesUrl(row)} className="text-brand-strong hover:underline dark:text-[#C5E27A]">
                                                {row.total_count ?? row.users_count}
                                            </Link>
                                        ) : (
                                            <span className="text-muted-foreground">0</span>
                                        )}
                                        {row.total_count !== undefined && row.total_count !== row.users_count && (
                                            <span className="text-muted-foreground" title="Числятся в самом отделе, без подотделов">
                                                {' '}
                                                · {row.users_count} напрямую
                                            </span>
                                        )}
                                    </td>
                                    <td className="py-1.5 pr-6 pl-4">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                aria-label={`Изменить: ${row.label}`}
                                                onClick={() => setEditing(row)}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-[#B42318] hover:text-[#B42318] dark:text-[#F7A19A]"
                                                aria-label={`Удалить: ${row.label}`}
                                                disabled={row.protected}
                                                onClick={() => setDeleting(row)}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}

                            {visible.length === 0 && (
                                <tr className="border-t">
                                    <td colSpan={people ? 4 : 3} className="text-muted-foreground px-6 py-12 text-center">
                                        {items.length === 0 ? 'Пока пусто.' : 'Ничего не найдено.'}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            {editing && (
                <EditorDialog
                    key={editing === 'new' ? 'new' : editing.id}
                    item={editing === 'new' ? null : editing}
                    items={items}
                    field={field}
                    routeName={routeName}
                    labels={labels}
                    tree={tree}
                    people={people}
                    onClose={() => setEditing(null)}
                />
            )}

            <DeleteDialog item={deleting} routeName={routeName} labels={labels} tree={tree} items={items} onClose={() => setDeleting(null)} />
        </>
    );
}

function EditorDialog({
    item,
    items,
    field,
    routeName,
    labels,
    tree,
    people,
    onClose,
}: {
    item: DirectoryItem | null;
    items: DirectoryItem[];
    field: 'title' | 'name';
    routeName: string;
    labels: Labels;
    tree: boolean;
    people?: PickablePerson[];
    onClose: () => void;
}) {
    const form = useForm<{ label: string; parent_id: number | null; head_ids: number[]; member_ids: number[] }>({
        label: item?.label ?? '',
        parent_id: item?.parent_id ?? null,
        head_ids: item?.heads?.map((head) => head.id) ?? [],
        member_ids: item?.member_ids ?? [],
    });

    // A head is a member too, listed once: new heads leave the member list,
    // former heads stay in the department as ordinary members.
    const setHeads = (ids: number[]) => {
        const former = form.data.head_ids.filter((id) => !ids.includes(id));
        form.setData((data) => ({ ...data, head_ids: ids, member_ids: [...data.member_ids.filter((id) => !ids.includes(id)), ...former] }));
    };
    const members = useMemo(() => people?.filter((person) => !form.data.head_ids.includes(person.id)) ?? [], [people, form.data.head_ids]);

    // A department cannot move under itself or its own sub-departments.
    const blocked = useMemo(() => (item && tree ? descendantIds(items, item.id) : new Set<number>()), [item, items, tree]);
    const parents = useMemo(() => orderRows(items, true).filter((row) => !blocked.has(row.id)), [items, blocked]);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        form.transform((data) => ({
            [field]: data.label.trim(),
            ...(tree ? { parent_id: data.parent_id } : {}),
            ...(people ? { head_ids: data.head_ids, member_ids: data.member_ids } : {}),
        }));
        const options = { preserveScroll: true, onSuccess: onClose };

        if (item) form.put(route(`${routeName}.update`, item.id), options);
        else form.post(route(`${routeName}.store`), options);
    };

    const errors = form.errors as Record<string, string | undefined>;
    /** The first error for a list and its items ("head_ids", "head_ids.0", ...). */
    const listError = (key: string) => errors[key] ?? Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1];

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <form onSubmit={submit} className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>{item ? labels.edit : labels.create}</DialogTitle>
                        <DialogDescription className="sr-only">Заполните поля и сохраните.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="directory-label">Название</Label>
                        <Input
                            id="directory-label"
                            autoFocus
                            value={form.data.label}
                            onChange={(event) => form.setData('label', event.target.value)}
                        />
                        <InputError message={errors[field]} />
                    </div>

                    {tree && (
                        <div className="grid gap-2">
                            <Label htmlFor="directory-parent">Входит в</Label>
                            <Select
                                value={form.data.parent_id === null ? 'root' : String(form.data.parent_id)}
                                onValueChange={(value) => form.setData('parent_id', value === 'root' ? null : Number(value))}
                            >
                                <SelectTrigger id="directory-parent">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent className="max-h-80">
                                    <SelectItem value="root">— Верхний уровень —</SelectItem>
                                    {parents.map((row) => (
                                        <SelectItem key={row.id} value={String(row.id)}>
                                            <span style={{ paddingLeft: row.depth * 16 }}>{row.label}</span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.parent_id} />
                        </div>
                    )}

                    {people && (
                        <div className="grid gap-2">
                            <Label htmlFor="directory-head">Руководители</Label>
                            <PeoplePicker
                                id="directory-head"
                                people={people}
                                value={form.data.head_ids}
                                onChange={setHeads}
                                emptyLabel="Не назначены"
                            />
                            <InputError message={listError('head_ids')} />
                        </div>
                    )}

                    {people && (
                        <div className="grid gap-2">
                            <Label htmlFor="directory-members">
                                Сотрудники
                                {form.data.member_ids.length > 0 && (
                                    <span className="text-muted-foreground font-normal"> · {form.data.member_ids.length}</span>
                                )}
                            </Label>
                            <PeoplePicker
                                id="directory-members"
                                people={members}
                                value={form.data.member_ids}
                                onChange={(ids) => form.setData('member_ids', ids)}
                                emptyLabel="Никого нет"
                            />
                            <InputError message={listError('member_ids')} />
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing || form.data.label.trim() === ''}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteDialog({
    item,
    items,
    routeName,
    labels,
    tree,
    onClose,
}: {
    item: DirectoryItem | null;
    items: DirectoryItem[];
    routeName: string;
    labels: Labels;
    tree: boolean;
    onClose: () => void;
}) {
    const [processing, setProcessing] = useState(false);
    const children = item && tree ? items.filter((other) => other.parent_id === item.id).length : 0;

    const confirm = () => {
        if (!item) return;

        router.delete(route(`${routeName}.destroy`, item.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: onClose,
        });
    };

    const affected = item?.users_count ?? 0;

    return (
        <Dialog open={item !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        Удалить {labels.accusative} «{item?.label}»?
                    </DialogTitle>
                    <DialogDescription asChild>
                        <div className="flex flex-col gap-1.5">
                            {affected > 0 ? (
                                <p>
                                    Запись снимется у {affected} {plural(affected, ['сотрудника', 'сотрудников', 'сотрудников'])}. Самих сотрудников
                                    это не затронет.
                                </p>
                            ) : (
                                <p>Сотрудников с этой записью нет.</p>
                            )}
                            {children > 0 && <p>Вложенные отделы ({children}) переместятся на уровень выше.</p>}
                            <p>Отменить удаление нельзя.</p>
                        </div>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <Button type="button" variant="outline" onClick={onClose}>
                        Отмена
                    </Button>
                    <Button type="button" variant="destructive" onClick={confirm} disabled={processing}>
                        {processing && <LoaderCircle className="animate-spin" />}
                        Удалить
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
