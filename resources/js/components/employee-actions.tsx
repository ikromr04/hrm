import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, router, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { ArrowRightLeft, Ellipsis, LoaderCircle, Pencil, RotateCcw, Trash2, UserX } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

export type EmploymentStatus = 'active' | 'transferred' | 'fired';

export interface ActionTarget {
    id: number;
    name: string;
    surname: string;
    status: EmploymentStatus;
}

type OpenDialog = 'transfer' | 'fire' | 'delete' | null;

/** "⋯" menu for one employee: edit, transfer, fire, restore, delete. */
export function EmployeeActions({ employee, isSelf }: { employee: ActionTarget; isSelf: boolean }) {
    const [dialog, setDialog] = useState<OpenDialog>(null);
    const name = `${employee.surname} ${employee.name}`;

    const restore = () => router.post(route('employees.restore', employee.id), {}, { preserveScroll: true });

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="text-muted-foreground size-8" aria-label={`Действия: ${name}`}>
                        <Ellipsis className="size-5!" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                    <DropdownMenuItem asChild>
                        <Link href={route('employees.edit', employee.id)}>
                            <Pencil />
                            Редактировать
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    {employee.status === 'active' ? (
                        <>
                            <DropdownMenuItem disabled={isSelf} onSelect={() => setDialog('transfer')}>
                                <ArrowRightLeft />
                                Перевести…
                            </DropdownMenuItem>
                            <DropdownMenuItem disabled={isSelf} onSelect={() => setDialog('fire')}>
                                <UserX />
                                Уволить…
                            </DropdownMenuItem>
                        </>
                    ) : (
                        <DropdownMenuItem onSelect={restore}>
                            <RotateCcw />
                            Восстановить
                        </DropdownMenuItem>
                    )}
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        disabled={isSelf}
                        onSelect={() => setDialog('delete')}
                        className="text-[#B42318] focus:text-[#B42318] dark:text-[#F7A19A] [&_svg]:text-current!"
                    >
                        <Trash2 />
                        Удалить…
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            {(dialog === 'transfer' || dialog === 'fire') && (
                <LeaveDialog kind={dialog} employee={employee} name={name} onClose={() => setDialog(null)} />
            )}
            <DeleteDialog open={dialog === 'delete'} employee={employee} name={name} onClose={() => setDialog(null)} />
        </>
    );
}

function LeaveDialog({ kind, employee, name, onClose }: { kind: 'transfer' | 'fire'; employee: ActionTarget; name: string; onClose: () => void }) {
    const transfer = kind === 'transfer';
    const form = useForm({ date: format(new Date(), 'yyyy-MM-dd'), note: '' });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(route(transfer ? 'employees.transfer' : 'employees.fire', employee.id), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={submit} className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>{transfer ? `Перевести: ${name}` : `Уволить: ${name}`}</DialogTitle>
                        <DialogDescription>
                            Сотрудник перейдёт в список «{transfer ? 'Переведённые' : 'Уволенные'}» и больше не сможет войти в систему. Вернуть его
                            можно командой «Восстановить».
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="leave-date">{transfer ? 'Дата перевода' : 'Дата увольнения'}</Label>
                        <Input
                            id="leave-date"
                            type="date"
                            required
                            value={form.data.date}
                            onChange={(event) => form.setData('date', event.target.value)}
                        />
                        <InputError message={form.errors.date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="leave-note">{transfer ? 'Куда переведён' : 'Причина (необязательно)'}</Label>
                        <Input
                            id="leave-note"
                            autoFocus
                            required={transfer}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            placeholder={transfer ? 'Например, Эволет Европа' : 'Например, по собственному желанию'}
                        />
                        <InputError message={form.errors.note} />
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" variant={transfer ? 'default' : 'destructive'} disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            {transfer ? 'Перевести' : 'Уволить'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteDialog({ open, employee, name, onClose }: { open: boolean; employee: ActionTarget; name: string; onClose: () => void }) {
    const [processing, setProcessing] = useState(false);

    const confirm = () =>
        router.delete(route('employees.destroy', employee.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: onClose,
        });

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Удалить сотрудника {name}?</DialogTitle>
                    <DialogDescription asChild>
                        <div className="flex flex-col gap-1.5">
                            <p>Удалятся учётная запись и все данные: личные данные, паспорт, семья, позиции, должности и отделы.</p>
                            <p>
                                Отменить удаление нельзя. Если человек просто ушёл, лучше <b>уволить</b> или <b>перевести</b> его: запись сохранится.
                            </p>
                        </div>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <Button type="button" variant="outline" onClick={onClose}>
                        Отмена
                    </Button>
                    <Button type="button" variant="destructive" onClick={confirm} disabled={processing}>
                        {processing && <LoaderCircle className="animate-spin" />}
                        Удалить навсегда
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
