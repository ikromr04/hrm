import InputError from '@/components/input-error';
import { PhotoInput } from '@/components/photo-input';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus } from 'lucide-react';
import { useRef, useState, type FormEventHandler, type ReactNode } from 'react';

interface Props {
    options: {
        types: { id: number; name: string }[];
        holders: { id: number; name: string }[];
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Оборудование', href: '/equipment' },
    { title: 'Новое оборудование', href: '/equipment/create' },
];

/**
 * Four fields across on a desktop, two on a tablet, one on a phone. The form
 * fills the page, but an input never stretches to the width of a monitor: a
 * serial number in a box half a metre long is unreadable. One flow, no
 * headings — every field here describes the same thing, and rules across the
 * page would only break its rhythm.
 */
const row = 'grid gap-4 sm:grid-cols-2 lg:grid-cols-4';

/** Half a box, which is all a date needs: two of them share one field's place. */
const half = 'grid grid-cols-2 gap-3';

/** A field worth two boxes: a long line of text or a list. */
const wide = 'sm:col-span-2';

function Field({ label, htmlFor, error, full, children }: { label: string; htmlFor: string; error?: string; full?: boolean; children: ReactNode }) {
    return (
        <div className={cn('grid content-start gap-2', full && wide)}>
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

/** Errors come back as "photos.0"; a field shows its own, whichever it is. */
const at = (errors: Record<string, string | undefined>, key: string) =>
    errors[key] ?? Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1];

export default function CreateEquipment({ options }: Props) {
    const today = new Date().toISOString().slice(0, 10);
    const inAYear = new Date();
    inAYear.setFullYear(inAYear.getFullYear() + 1);

    const inventory = useRef<HTMLInputElement>(null);
    /** Which of the two buttons was pressed, read as the form is sent. */
    const batch = useRef(false);
    /** What has been filed without leaving the page, newest last. */
    const [filed, setFiled] = useState<{ id: number; name: string; inventory_number: string }[]>([]);
    const [photos, setPhotos] = useState<File[]>([]);

    // Hardware is usually bought for somebody, so it can be handed over here
    // rather than added first and issued in a second window.
    const [issuing, setIssuing] = useState(false);

    const form = useForm({
        name: '',
        equipment_type_id: '',
        maker: '',
        model: '',
        serial_number: '',
        inventory_number: '',
        processor: '',
        memory: '',
        accessories: '',
        condition: '',
        checked_at: today,
        next_inventory_at: inAYear.toISOString().slice(0, 10),
        holder_user_id: '',
        issued_at: today,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        const again = batch.current;

        form.transform((data) => ({
            ...data,
            // One box, comma by comma: "Блок питания 65 Вт, Сумка".
            accessories: data.accessories
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean),
            photos,
            holder_user_id: issuing ? data.holder_user_id : null,
            issued_at: issuing ? data.issued_at : null,
            another: again,
        }));

        form.post(route('equipment.store'), {
            forceFormData: true,
            // State is kept whichever button was pressed. Inertia keeps it by
            // default on a post, and it has to: a rejected save re-renders the
            // page, and a remounted form would come back empty, with the
            // server's complaints lost along with what had been typed.
            preserveScroll: true,
            onSuccess: (page) => {
                batch.current = false;

                if (!again) return;

                const filed = (page.props as unknown as SharedData).flash?.equipment;

                if (filed) setFiled((before) => [...before, filed]);

                // What the next box shares with this one stays; what is its own
                // is cleared, and the cursor waits on the number from the sticker.
                form.setData((data) => ({ ...data, serial_number: '', inventory_number: '', holder_user_id: '' }));
                setPhotos([]);
                inventory.current?.focus();
            },
            onError: (errors) => {
                batch.current = false;

                // The boxes carry the server's own field names, so the first
                // thing it objected to can be brought into view and focused —
                // otherwise a save refused over a box further up the page looks
                // like a button that does nothing.
                const first = Object.keys(errors)[0];
                const box = first ? document.getElementById(first) : null;

                box?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                box?.focus({ preventScroll: true });
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Новое оборудование" />

            <div className="flex flex-1 flex-col gap-5 p-3 md:px-5 md:py-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-xl font-semibold tracking-tight">Новое оборудование</h1>
                        <p className="text-muted-foreground text-sm">Единица встаёт на баланс — её можно сразу выдать сотруднику.</p>
                    </div>

                    <Button variant="ghost" asChild>
                        <Link href={route('equipment.index')}>Отмена</Link>
                    </Button>
                </div>

                <Card className="w-full rounded-xl p-6">
                    {/* noValidate: the server's rules are the real ones. */}
                    <form onSubmit={submit} noValidate className="flex flex-col gap-6">
                        <div className={row}>
                            <Field label="Наименование" htmlFor="name" error={form.errors.name} full>
                                <Input
                                    id="name"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    placeholder="Ноутбук Dell Latitude 5440"
                                    aria-invalid={!!form.errors.name}
                                />
                            </Field>

                            <Field label="Категория" htmlFor="equipment_type_id" error={form.errors.equipment_type_id}>
                                <SearchableSelect
                                    id="equipment_type_id"
                                    value={form.data.equipment_type_id}
                                    onChange={(value) => form.setData('equipment_type_id', value)}
                                    options={options.types.map((type) => ({ value: String(type.id), label: type.name }))}
                                    placeholder="Выберите категорию"
                                    searchPlaceholder="Поиск категории"
                                    empty="Категория не найдена"
                                    invalid={!!form.errors.equipment_type_id}
                                />
                            </Field>

                            <Field label="Производитель" htmlFor="maker" error={form.errors.maker}>
                                <Input
                                    id="maker"
                                    value={form.data.maker}
                                    onChange={(event) => form.setData('maker', event.target.value)}
                                    placeholder="Dell"
                                    aria-invalid={!!form.errors.maker}
                                />
                            </Field>

                            <Field label="Модель" htmlFor="model" error={form.errors.model}>
                                <Input
                                    id="model"
                                    value={form.data.model}
                                    onChange={(event) => form.setData('model', event.target.value)}
                                    placeholder="Latitude 5440"
                                    aria-invalid={!!form.errors.model}
                                />
                            </Field>

                            <Field label="Серийный номер" htmlFor="serial_number" error={form.errors.serial_number}>
                                <Input
                                    id="serial_number"
                                    value={form.data.serial_number}
                                    onChange={(event) => form.setData('serial_number', event.target.value)}
                                    placeholder="7K2L9P3"
                                    aria-invalid={!!form.errors.serial_number}
                                />
                            </Field>

                            <Field label="Инвентарный номер" htmlFor="inventory_number" error={form.errors.inventory_number}>
                                <Input
                                    ref={inventory}
                                    id="inventory_number"
                                    value={form.data.inventory_number}
                                    onChange={(event) => form.setData('inventory_number', event.target.value)}
                                    placeholder="EV-0421"
                                    aria-invalid={!!form.errors.inventory_number}
                                />
                            </Field>

                            <Field label="Процессор" htmlFor="processor" error={form.errors.processor}>
                                <Input
                                    id="processor"
                                    value={form.data.processor}
                                    onChange={(event) => form.setData('processor', event.target.value)}
                                    placeholder="Intel Core i5-1335U"
                                    aria-invalid={!!form.errors.processor}
                                />
                            </Field>

                            <Field label="Память / диск" htmlFor="memory" error={form.errors.memory}>
                                <Input
                                    id="memory"
                                    value={form.data.memory}
                                    onChange={(event) => form.setData('memory', event.target.value)}
                                    placeholder="16 ГБ / SSD 512 ГБ"
                                    aria-invalid={!!form.errors.memory}
                                />
                            </Field>

                            {/* Two dates in the space of one field: a date box needs no more. */}
                            <div className={half}>
                                <Field label="Последняя проверка" htmlFor="checked_at" error={form.errors.checked_at}>
                                    <Input
                                        id="checked_at"
                                        type="date"
                                        max={today}
                                        value={form.data.checked_at}
                                        onChange={(event) => form.setData('checked_at', event.target.value)}
                                        aria-invalid={!!form.errors.checked_at}
                                    />
                                </Field>

                                <Field label="След. инвентаризация" htmlFor="next_inventory_at" error={form.errors.next_inventory_at}>
                                    <Input
                                        id="next_inventory_at"
                                        type="date"
                                        value={form.data.next_inventory_at}
                                        onChange={(event) => form.setData('next_inventory_at', event.target.value)}
                                        aria-invalid={!!form.errors.next_inventory_at}
                                    />
                                </Field>
                            </div>

                            <Field label="Комплектация" htmlFor="accessories" error={form.errors.accessories} full>
                                <Input
                                    id="accessories"
                                    value={form.data.accessories}
                                    onChange={(event) => form.setData('accessories', event.target.value)}
                                    placeholder="Блок питания 65 Вт, Сумка, Мышь Logitech M185"
                                    aria-invalid={!!form.errors.accessories}
                                />
                                <p className="text-muted-foreground text-[13px]">Через запятую.</p>
                            </Field>

                            <Field label="Текущее состояние" htmlFor="condition" error={form.errors.condition} full>
                                <Input
                                    id="condition"
                                    value={form.data.condition}
                                    onChange={(event) => form.setData('condition', event.target.value)}
                                    placeholder="Новое, в упаковке"
                                    aria-invalid={!!form.errors.condition}
                                />
                            </Field>

                            <PhotoInput
                                photos={photos}
                                onChange={setPhotos}
                                error={at(form.errors, 'photos')}
                                hint="Останутся в журнале как вид при поступлении."
                                className="col-span-full"
                            />
                        </div>

                        {/* Not a property of the unit but something done with it, so it stands apart. */}
                        <div className={cn(row, 'border-t pt-6')}>
                            <div className="col-span-full flex items-center gap-2.5">
                                <Checkbox id="issue" checked={issuing} onCheckedChange={(on) => setIssuing(on === true)} />
                                <Label htmlFor="issue" className="font-normal">
                                    Сразу выдать сотруднику
                                </Label>
                            </div>

                            {issuing && (
                                <>
                                    <Field label="Кому" htmlFor="holder_user_id" error={form.errors.holder_user_id}>
                                        <SearchableSelect
                                            id="holder_user_id"
                                            value={form.data.holder_user_id}
                                            onChange={(value) => form.setData('holder_user_id', value)}
                                            options={options.holders.map((holder) => ({ value: String(holder.id), label: holder.name }))}
                                            placeholder="Выберите сотрудника"
                                            searchPlaceholder="Поиск по фамилии"
                                            empty="Сотрудник не найден"
                                            invalid={!!form.errors.holder_user_id}
                                        />
                                    </Field>

                                    <Field label="Дата выдачи" htmlFor="issued_at" error={form.errors.issued_at}>
                                        <Input
                                            id="issued_at"
                                            type="date"
                                            className="max-w-[11.5rem]"
                                            max={today}
                                            value={form.data.issued_at}
                                            onChange={(event) => form.setData('issued_at', event.target.value)}
                                            aria-invalid={!!form.errors.issued_at}
                                        />
                                    </Field>
                                </>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center justify-end gap-2 border-t pt-6">
                            {filed.length > 0 && (
                                <p className="text-muted-foreground mr-auto text-sm">
                                    Добавлено {filed.length}, последнее —{' '}
                                    <Link
                                        href={route('equipment.show', filed[filed.length - 1].id)}
                                        className="text-brand-strong font-medium hover:underline dark:text-[#C5E27A]"
                                    >
                                        {filed[filed.length - 1].name}
                                    </Link>{' '}
                                    (инв. № {filed[filed.length - 1].inventory_number})
                                </p>
                            )}

                            {Object.keys(form.errors).length > 0 && (
                                <p className="mr-auto text-sm text-red-600 dark:text-red-400">
                                    Не сохранено: проверьте {Object.keys(form.errors).length === 1 ? 'поле' : 'поля'} выше.
                                </p>
                            )}

                            <Button type="button" variant="outline" asChild>
                                <Link href={route('equipment.index')}>Отмена</Link>
                            </Button>
                            <Button type="submit" variant="outline" disabled={form.processing} onClick={() => (batch.current = true)}>
                                <Plus />
                                Сохранить и добавить ещё
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <LoaderCircle className="animate-spin" />}
                                Сохранить
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
