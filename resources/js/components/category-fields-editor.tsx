import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type CategoryField, type FieldType, type FieldTypeOption } from '@/lib/equipment-fields';
import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-react';

/**
 * What units of a category are described by, edited in the category's own
 * dialog: a line per field with its name, what it holds and whether it must be
 * filled in.
 *
 * Dropping a field takes with it whatever the units had written in it, so the
 * line says so plainly instead of asking a second time in a dialog of its own.
 */
export function CategoryFieldsEditor({
    fields,
    types,
    onChange,
    error,
}: {
    fields: CategoryField[];
    types: FieldTypeOption[];
    onChange: (fields: CategoryField[]) => void;
    error?: (key: string) => string | undefined;
}) {
    const replace = (index: number, field: Partial<CategoryField>) =>
        onChange(fields.map((held, at) => (at === index ? { ...held, ...field } : held)));

    const add = () => onChange([...fields, { name: '', type: 'text', options: [], required: false }]);
    const remove = (index: number) => onChange(fields.filter((_, at) => at !== index));

    const move = (index: number, to: number) => {
        if (to < 0 || to >= fields.length) return;

        const next = [...fields];
        [next[index], next[to]] = [next[to], next[index]];
        onChange(next);
    };

    return (
        <div className="grid content-start gap-2">
            <Label>Поля единиц</Label>
            <p className="text-muted-foreground text-[13px]">
                Их увидят на карточке и в форме добавления техники этой категории. Порядок здесь — порядок на карточке.
            </p>

            <ul className="grid gap-3">
                {fields.map((field, index) => (
                    <li key={field.id ?? `new-${index}`} className="border-border grid gap-2 rounded-lg border p-3">
                        <div className="flex items-start gap-2">
                            <div className="grid flex-1 content-start gap-2">
                                <Input
                                    value={field.name}
                                    placeholder="Название поля"
                                    aria-label={`Название поля ${index + 1}`}
                                    onChange={(event) => replace(index, { name: event.target.value })}
                                    aria-invalid={!!error?.(`fields.${index}.name`)}
                                />
                                <InputError message={error?.(`fields.${index}.name`)} />
                            </div>

                            <div className="grid w-40 content-start gap-2">
                                <Select value={field.type} onValueChange={(type) => replace(index, { type: type as FieldType })}>
                                    <SelectTrigger aria-label={`Тип поля ${index + 1}`}>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((type) => (
                                            <SelectItem key={type.key} value={type.key}>
                                                {type.title}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={error?.(`fields.${index}.type`)} />
                            </div>

                            <div className="flex shrink-0 gap-0.5">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-muted-foreground size-9"
                                    aria-label={`Выше: ${field.name || 'новое поле'}`}
                                    disabled={index === 0}
                                    onClick={() => move(index, index - 1)}
                                >
                                    <ChevronUp className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-muted-foreground size-9"
                                    aria-label={`Ниже: ${field.name || 'новое поле'}`}
                                    disabled={index === fields.length - 1}
                                    onClick={() => move(index, index + 1)}
                                >
                                    <ChevronDown className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-9 text-[#B42318] hover:text-[#B42318] dark:text-[#F7A19A]"
                                    aria-label={`Убрать поле: ${field.name || 'новое поле'}`}
                                    onClick={() => remove(index)}
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </div>

                        {field.type === 'select' && (
                            <div className="grid content-start gap-2">
                                <Input
                                    value={field.options.join(', ')}
                                    placeholder="Варианты через запятую"
                                    aria-label={`Варианты поля ${index + 1}`}
                                    onChange={(event) =>
                                        replace(index, { options: event.target.value.split(',').map((option) => option.trimStart()) })
                                    }
                                    aria-invalid={!!error?.(`fields.${index}.options`)}
                                />
                                <InputError message={error?.(`fields.${index}.options`)} />
                            </div>
                        )}

                        <label className="text-muted-foreground flex items-center gap-2 text-[13px]">
                            <Checkbox checked={field.required} onCheckedChange={() => replace(index, { required: !field.required })} />
                            Обязательное
                        </label>

                        {field.id !== undefined && (
                            <p className="text-muted-foreground text-[13px]">Если убрать это поле, значения, записанные в него у единиц, исчезнут.</p>
                        )}
                    </li>
                ))}
            </ul>

            <Button type="button" variant="outline" className="h-8 justify-self-start" onClick={add}>
                <Plus />
                Добавить поле
            </Button>
            <InputError message={error?.('fields')} />
        </div>
    );
}
