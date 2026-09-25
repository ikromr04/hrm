import InputError from '@/components/input-error';
import { PhotoInput } from '@/components/photo-input';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

/** The moves that need something from whoever makes them — all of them. */
export type AskedMove = 'issue' | 'take' | 'write-off';

/** Errors come back as "photos.0"; a field shows its own, whichever it is. */
const at = (errors: Record<string, string | undefined>, key: string) =>
    errors[key] ?? Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1];

/** What the pictures of each move are for, said in the form itself. */
const photoHint: Record<AskedMove, string> = {
    issue: 'В каком виде вещь ушла сотруднику. Снимки останутся в журнале.',
    take: 'Как вещь вернулась. Снимки останутся в журнале.',
    'write-off': 'Чем подтверждается списание. Снимки останутся в журнале.',
};

export const moveLabel: Record<AskedMove, string> = {
    issue: 'Выдать',
    take: 'Принять возврат',
    'write-off': 'Списать',
};

interface MoveUnit {
    id: number;
    name: string;
    inventory_number: string;
}

/**
 * Handing a unit over, taking it back or writing it off. Each move asks only
 * for what it needs, and the answers end up in the unit's history.
 */
export function EquipmentMoveDialog({
    unit,
    kind,
    holders,
    onClose,
}: {
    unit: MoveUnit;
    kind: AskedMove;
    holders: { id: number; name: string }[];
    onClose: () => void;
}) {
    const today = new Date().toISOString().slice(0, 10);
    const [photos, setPhotos] = useState<File[]>([]);

    const form = useForm({
        holder_user_id: '',
        issued_at: today,
        condition_on_return: '',
        written_off_at: today,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        form.transform((data) => {
            if (kind === 'issue') return { holder_user_id: data.holder_user_id, issued_at: data.issued_at, photos };
            if (kind === 'take') return { condition_on_return: data.condition_on_return, photos };

            return { written_off_at: data.written_off_at, photos };
        });

        // Any move may carry pictures, so every one of them goes as multipart.
        form.post(route(`equipment.${kind}`, unit.id), { preserveScroll: true, forceFormData: true, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                {/* noValidate: the server's rules are the real ones. */}
                <form onSubmit={submit} noValidate className="flex flex-col gap-5">
                    <DialogHeader>
                        <DialogTitle>{moveLabel[kind]}</DialogTitle>
                        <DialogDescription>
                            {unit.name} · инв. № {unit.inventory_number}
                        </DialogDescription>
                    </DialogHeader>

                    {kind === 'issue' && (
                        <>
                            <div className="grid content-start gap-2">
                                <Label htmlFor="move-holder">Кому</Label>
                                <SearchableSelect
                                    id="move-holder"
                                    value={form.data.holder_user_id}
                                    onChange={(value) => form.setData('holder_user_id', value)}
                                    options={holders.map((holder) => ({ value: String(holder.id), label: holder.name }))}
                                    placeholder="Выберите сотрудника"
                                    searchPlaceholder="Поиск по фамилии или имени"
                                    empty="Сотрудник не найден"
                                    invalid={!!form.errors.holder_user_id}
                                />
                                <InputError message={form.errors.holder_user_id} />
                            </div>

                            <div className="grid content-start gap-2">
                                <Label htmlFor="move-issued">Дата выдачи</Label>
                                <Input
                                    id="move-issued"
                                    type="date"
                                    max={today}
                                    value={form.data.issued_at}
                                    onChange={(event) => form.setData('issued_at', event.target.value)}
                                    aria-invalid={!!form.errors.issued_at}
                                />
                                <InputError message={form.errors.issued_at} />
                            </div>
                        </>
                    )}

                    {kind === 'take' && (
                        <div className="grid content-start gap-2">
                            <Label htmlFor="move-condition">Состояние при возврате</Label>
                            <Input
                                id="move-condition"
                                value={form.data.condition_on_return}
                                onChange={(event) => form.setData('condition_on_return', event.target.value)}
                                placeholder="Рабочее, без повреждений"
                                aria-invalid={!!form.errors.condition_on_return}
                            />
                            <InputError message={form.errors.condition_on_return} />
                            <p className="text-muted-foreground text-[13px]">Попадёт в историю передач и в блок «Состояние».</p>
                        </div>
                    )}

                    {kind === 'write-off' && (
                        <div className="grid content-start gap-2">
                            <Label htmlFor="move-written-off">Дата списания</Label>
                            <Input
                                id="move-written-off"
                                type="date"
                                max={today}
                                value={form.data.written_off_at}
                                onChange={(event) => form.setData('written_off_at', event.target.value)}
                                aria-invalid={!!form.errors.written_off_at}
                            />
                            <InputError message={form.errors.written_off_at} />
                            <p className="text-muted-foreground text-[13px]">Списанную единицу больше нельзя выдать.</p>
                        </div>
                    )}

                    <PhotoInput photos={photos} onChange={setPhotos} error={at(form.errors, 'photos')} hint={photoHint[kind]} />

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Отмена
                        </Button>
                        <Button type="submit" variant={kind === 'write-off' ? 'destructive' : 'default'} disabled={form.processing}>
                            {form.processing && <LoaderCircle className="animate-spin" />}
                            {moveLabel[kind]}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
