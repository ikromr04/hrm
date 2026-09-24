import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, ExternalLink, X } from 'lucide-react';
import { useEffect, useState } from 'react';

export interface Photo {
    id: number;
    /** The upload itself, opened when the picture is wanted full size. */
    url: string;
    /** The scaled copy the interface shows. */
    preview: string;
}

/**
 * Photographs as a strip of thumbnails. One opens on its own; several open as
 * a slideshow, so a set taken at one inspection can be looked through without
 * leaving the page. The upload is a click away from either.
 */
export function Photos({ photos, className }: { photos: Photo[]; className?: string }) {
    const [open, setOpen] = useState<number | null>(null);

    if (photos.length === 0) return null;

    return (
        <>
            <ul className={cn('flex flex-wrap gap-2', className)}>
                {photos.map((photo, index) => (
                    <li key={photo.id}>
                        <button
                            type="button"
                            onClick={() => setOpen(index)}
                            aria-label={`Фотография ${index + 1} из ${photos.length}`}
                            className="focus-visible:ring-ring block overflow-hidden rounded-lg border focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <img src={photo.preview} alt="" loading="lazy" className="size-16 object-cover transition-opacity hover:opacity-85" />
                        </button>
                    </li>
                ))}
            </ul>

            {open !== null && <Viewer photos={photos} at={open} onMove={setOpen} onClose={() => setOpen(null)} />}
        </>
    );
}

/** The slideshow itself: arrows, Escape, and a way to the original. */
function Viewer({ photos, at, onMove, onClose }: { photos: Photo[]; at: number; onMove: (at: number) => void; onClose: () => void }) {
    const photo = photos[at];
    const step = (by: number) => onMove((at + by + photos.length) % photos.length);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onClose();
            if (event.key === 'ArrowLeft') step(-1);
            if (event.key === 'ArrowRight') step(1);
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    });

    return (
        // Not a Dialog: a picture wants the whole screen and no card around it.
        <div
            role="dialog"
            aria-modal="true"
            aria-label="Просмотр фотографии"
            onClick={onClose}
            className="fixed inset-0 z-50 flex flex-col bg-black/85 p-4 backdrop-blur-sm"
        >
            <div className="flex shrink-0 items-center justify-end gap-2" onClick={(event) => event.stopPropagation()}>
                <Button variant="ghost" className="text-white hover:bg-white/15 hover:text-white" asChild>
                    <a href={photo.url} target="_blank" rel="noreferrer">
                        <ExternalLink />
                        Оригинал
                    </a>
                </Button>
                <Button variant="ghost" size="icon" aria-label="Закрыть" onClick={onClose} className="text-white hover:bg-white/15 hover:text-white">
                    <X />
                </Button>
            </div>

            <div className="flex min-h-0 flex-1 items-center gap-2" onClick={(event) => event.stopPropagation()}>
                {photos.length > 1 && (
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Предыдущая"
                        onClick={() => step(-1)}
                        className="shrink-0 text-white hover:bg-white/15 hover:text-white"
                    >
                        <ChevronLeft />
                    </Button>
                )}

                <img src={photo.preview} alt="" className="mx-auto max-h-full min-h-0 flex-1 object-contain" />

                {photos.length > 1 && (
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Следующая"
                        onClick={() => step(1)}
                        className="shrink-0 text-white hover:bg-white/15 hover:text-white"
                    >
                        <ChevronRight />
                    </Button>
                )}
            </div>

            {photos.length > 1 && (
                <p className="shrink-0 pt-2 text-center text-[13px] text-white/70 tabular-nums">
                    {at + 1} из {photos.length}
                </p>
            )}
        </div>
    );
}
