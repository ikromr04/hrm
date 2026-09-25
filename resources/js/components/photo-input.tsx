import { CameraDialog, useCameraMode } from '@/components/camera-capture';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Camera, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';

/**
 * Pictures a form is about to send: chosen from disk, or taken on the spot.
 * They are held as files until the form is submitted, so the strip below the
 * buttons is what will be uploaded and nothing has happened yet.
 */
export function PhotoInput({
    photos,
    onChange,
    label = 'Фотографии',
    hint,
    error,
    className,
}: {
    photos: File[];
    onChange: (photos: File[]) => void;
    label?: string;
    hint?: string;
    error?: string;
    className?: string;
}) {
    const picker = useRef<HTMLInputElement>(null);
    const camera = useRef<HTMLInputElement>(null);
    // A phone hands over to its camera app, a laptop opens ours, and a machine
    // without a camera is not offered the button at all.
    const mode = useCameraMode();
    const [shooting, setShooting] = useState(false);

    const add = (files: FileList | null) => files && onChange([...photos, ...Array.from(files)]);

    return (
        <div className={cn('grid content-start gap-2', className)}>
            <Label>{label}</Label>

            {photos.length > 0 && (
                <ul className="flex flex-wrap gap-2">
                    {photos.map((photo, index) => (
                        <li key={index} className="relative">
                            <img src={URL.createObjectURL(photo)} alt="" className="size-16 rounded-lg border object-cover" />
                            <button
                                type="button"
                                aria-label={`Убрать снимок ${index + 1}`}
                                onClick={() => onChange(photos.filter((_, other) => other !== index))}
                                className="bg-background absolute -top-1.5 -right-1.5 rounded-full border p-0.5 shadow-sm"
                            >
                                <X className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" size="sm" onClick={() => picker.current?.click()}>
                    <Upload />
                    Выбрать файлы
                </Button>

                {mode !== 'none' && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => (mode === 'native' ? camera.current?.click() : setShooting(true))}
                    >
                        <Camera />
                        Сфотографировать
                    </Button>
                )}
            </div>

            {shooting && <CameraDialog onShot={(photo) => onChange([...photos, photo])} onClose={() => setShooting(false)} />}

            <input
                ref={picker}
                type="file"
                accept="image/*"
                multiple
                hidden
                onChange={(event) => {
                    add(event.target.files);
                    event.target.value = '';
                }}
            />

            {/* `capture` is honoured by phones only; elsewhere the dialog above does the work. */}
            {mode === 'native' && (
                <input
                    ref={camera}
                    type="file"
                    accept="image/*"
                    capture="environment"
                    hidden
                    onChange={(event) => {
                        add(event.target.files);
                        event.target.value = '';
                    }}
                />
            )}

            <InputError message={error} />
            {hint && <p className="text-muted-foreground text-[13px]">{hint}</p>}
        </div>
    );
}
