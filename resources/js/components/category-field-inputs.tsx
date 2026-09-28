import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type CategoryField, type FieldValues } from '@/lib/equipment-fields';
import { cn } from '@/lib/utils';

/**
 * The fields the chosen category asks about, as inputs.
 *
 * Which fields these are is decided in the directory, so a monitor is never
 * asked for a processor. Each one is drawn by what it holds: a list offers its
 * choices, a date opens a date picker, a yes-or-no is a tick box.
 */
export function CategoryFieldInputs({
    fields,
    values,
    onChange,
    error,
    className,
}: {
    fields: CategoryField[];
    values: FieldValues;
    onChange: (id: number, value: string | boolean) => void;
    /** The error for "fields.<id>", as the server keys them. */
    error?: (key: string) => string | undefined;
    /** Applied to every field, so a page can place them in its own grid. */
    className?: string;
}) {
    return (
        <>
            {fields.map((field) => {
                const id = field.id!;
                const key = `fields.${id}`;
                const message = error?.(key);
                const held = values[id];
                const input = `category-field-${id}`;

                return (
                    <div key={id} className={cn('grid content-start gap-2', className)}>
                        {field.type === 'boolean' ? (
                            // The label belongs next to the box, not above it.
                            <label htmlFor={input} className="flex items-center gap-2 pt-1 text-sm">
                                <Checkbox id={input} checked={held === true} onCheckedChange={(next) => onChange(id, next === true)} />
                                {field.name}
                                {field.required && <span aria-hidden>*</span>}
                            </label>
                        ) : (
                            <>
                                <Label htmlFor={input}>
                                    {field.name}
                                    {field.required && <span aria-hidden> *</span>}
                                </Label>

                                {field.type === 'select' ? (
                                    <Select value={String(held ?? '')} onValueChange={(next) => onChange(id, next)}>
                                        <SelectTrigger id={input} aria-invalid={!!message}>
                                            <SelectValue placeholder="Не выбрано" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {field.options.map((option) => (
                                                <SelectItem key={option} value={option}>
                                                    {option}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <Input
                                        id={input}
                                        type={field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : 'text'}
                                        step={field.type === 'number' ? 'any' : undefined}
                                        value={String(held ?? '')}
                                        onChange={(event) => onChange(id, event.target.value)}
                                        aria-invalid={!!message}
                                    />
                                )}
                            </>
                        )}

                        <InputError message={message} />
                    </div>
                );
            })}
        </>
    );
}
