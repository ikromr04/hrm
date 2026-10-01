import { CategoryFieldInputs } from '@/components/category-field-inputs';
import InputError from '@/components/input-error';
import { PhotoInput } from '@/components/photo-input';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { blankValues, type CategoryOption, type FieldValues } from '@/lib/equipment-fields';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus } from 'lucide-react';
import { useMemo, useRef, useState, type FormEventHandler, type ReactNode } from 'react';

interface Props {
    options: {
        /** Each category with what its units are described by, and whether they come with anything. */
        types: CategoryOption[];
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
const row = 'grid gap-4 md:grid-cols-2 lg:grid-cols-4';

/**
 * Half a box, which is all a date needs: two of them share one field's place.
 * The narrowest phones stack them, as two dates side by side would not fit.
 */
const half = 'grid gap-3 md:grid-cols-2';

/** A field worth two boxes: a long line of text or a list. */
const wide = 'md:col-span-2';

function Field({ label, htmlFor, error, full, children }: { label: string; htmlFor: string; error?: string; full?: boolean; children: ReactNode }) {
    return (
        <div className={cn('grid min-w-0 content-start gap-2 [&_input]:min-w-0', full && wide)}>
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
    /** What is written in the chosen category's fields, by field id. */
    const [values, setValues] = useState<FieldValues>({});

    // Hardware is usually bought for somebody, so it can be handed over here
    // rather than added first and issued in a second window.
    const [issuing, setIssuing] = useState(false);

    const form = useForm({
        name: '',
        equipment_type_id: '',
        inventory_number: '',
        accessories: '',
        condition: '',
        checked_at: today,
        next_inventory_at: inAYear.toISOString().slice(0, 10),
        holder_user_id: '',
        issued_at: today,
    });

    const category = useMemo(
        () => options.types.find((type) => String(type.id) === form.data.equipment_type_id),
        [options.types, form.data.equipment_type_id],
    );
    const fields = category?.fields ?? [];

    // Another category asks other things, so what was typed for the last one is
    // not carried over — it would only be saved into fields nobody chose.
    const pickType = (id: string) => {
        form.setData('equipment_type_id', id);
        setValues(blankValues(options.types.find((type) => String(type.id) === id)?.fields ?? []));
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        const again = batch.current;

        form.transform((data) => ({
            ...data,
            fields: values,
            // One box, comma by comma: "Блок питания 65 Вт, Сумка". A category
            // whose units come with nothing sends nothing.
            accessories: category?.has_accessories
                ? data.accessories
                      .split(',')
                      .map((item) => item.trim())
                      .filter(Boolean)
                : [],
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

            <div className="flex flex-1 flex-col gap-5 p-3 max-md:gap-3 md:px-5 md:py-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        {/* The phone's top bar already carries the page's name. */}
                        <h1 className="text-xl font-semibold tracking-tight max-md:sr-only">Новое оборудование</h1>
                        <p className="text-muted-foreground text-sm">Единица встаёт на баланс — её можно сразу выдать сотруднику.</p>
                    </div>

                    <Button variant="ghost" className="max-md:hidden" asChild>
                        <Link href={route('equipment.index')}>Отмена</Link>
                    </Button>
                </div>

                <Card className="w-full rounded-xl p-4 max-md:rounded-2xl max-md:border-0 max-md:p-4! max-md:shadow-none sm:p-6">
                    {/* noValidate: the server's rules are the real ones. */}
                    {/* On a phone every box is 44px tall, a comfortable target for a thumb. */}
                    <form onSubmit={submit} noValidate className="flex flex-col gap-6 max-md:[&_[role=combobox]]:h-11 max-md:[&_input]:h-11">
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
                                    onChange={pickType}
                                    options={options.types.map((type) => ({ value: String(type.id), label: type.name }))}
                                    placeholder="Выберите категорию"
                                    searchPlaceholder="Поиск категории"
                                    empty="Категория не найдена"
                                    invalid={!!form.errors.equipment_type_id}
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

                            {/* Whatever this category asks about: a processor for
                                a laptop, a diagonal for a monitor, nothing at all
                                for a category that has no fields of its own. */}
                            <CategoryFieldInputs
                                fields={fields}
                                values={values}
                                onChange={(id, value) => setValues((held) => ({ ...held, [id]: value }))}
                                error={(key) => at(form.errors as Record<string, string | undefined>, key)}
                            />

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

                            {category?.has_accessories && (
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
                            )}

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

                        {/* A phone keeps the buttons in a bar that rides just above the tab
                            bar, so saving is never a scroll away; «Отмена» is the back arrow there. */}
                        <div className="max-md:bg-background/90 flex flex-col gap-2 border-t pt-6 max-md:sticky max-md:bottom-[calc(3.5rem+env(safe-area-inset-bottom))] max-md:z-20 max-md:-mx-4 max-md:-mb-4 max-md:flex-row max-md:flex-wrap max-md:items-center max-md:rounded-b-2xl max-md:px-4 max-md:py-3 max-md:backdrop-blur-xl sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                            {filed.length > 0 && (
                                <p className="text-muted-foreground mr-auto text-sm max-md:basis-full">
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
                                <p className="mr-auto text-sm text-red-600 max-md:basis-full dark:text-red-400">
                                    Не сохранено: проверьте {Object.keys(form.errors).length === 1 ? 'поле' : 'поля'} выше.
                                </p>
                            )}

                            <Button type="button" variant="outline" className="max-md:hidden" asChild>
                                <Link href={route('equipment.index')}>Отмена</Link>
                            </Button>
                            {/* Shorter on a phone, where two buttons share a 320px line. */}
                            <Button
                                type="submit"
                                variant="outline"
                                aria-label="Сохранить и добавить ещё"
                                className="max-md:h-11 max-md:min-w-0 max-md:flex-[1.4] max-md:px-3"
                                disabled={form.processing}
                                onClick={() => (batch.current = true)}
                            >
                                <Plus className="max-md:hidden" />
                                <span className="md:hidden">Сохранить и ещё</span>
                                <span className="max-md:hidden">Сохранить и добавить ещё</span>
                            </Button>
                            <Button type="submit" className="max-md:h-11 max-md:flex-1" disabled={form.processing}>
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
