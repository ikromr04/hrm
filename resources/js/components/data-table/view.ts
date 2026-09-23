import { useEffect, useState } from 'react';
import { type ViewState } from './types';

function load(key: string, fallback: ViewState, known: string[]): ViewState {
    try {
        const saved = JSON.parse(localStorage.getItem(key) ?? 'null') as ViewState | null;
        if (!saved?.hidden || !saved?.pinned) return fallback;

        // A saved view may mention columns that were renamed or removed since.
        const kept = (list: string[]) => list.filter((column) => known.includes(column));

        return { hidden: kept(saved.hidden), pinned: { left: kept(saved.pinned.left), right: kept(saved.pinned.right) } };
    } catch {
        return fallback;
    }
}

function save(key: string, view: ViewState) {
    try {
        localStorage.setItem(key, JSON.stringify(view));
    } catch {
        // Storage can be unavailable (private mode); the view just won't persist.
    }
}

/**
 * Which columns a viewer hid or pinned, remembered between visits. The key
 * carries a version: bump it when the columns change so badly that an old
 * view would be nonsense.
 */
export function useTableView(storageKey: string, columnKeys: string[], fallback: ViewState) {
    const [view, setView] = useState<ViewState>(() => load(storageKey, fallback, columnKeys));

    useEffect(() => save(storageKey, view), [storageKey, view]);

    const pin = (key: string, side: 'left' | 'right' | null) =>
        setView((current) => ({
            ...current,
            pinned: {
                left: side === 'left' ? [...current.pinned.left.filter((k) => k !== key), key] : current.pinned.left.filter((k) => k !== key),
                right: side === 'right' ? [key, ...current.pinned.right.filter((k) => k !== key)] : current.pinned.right.filter((k) => k !== key),
            },
        }));

    const toggleHidden = (key: string, hidden: boolean) =>
        setView((current) => ({
            ...current,
            hidden: hidden ? [...current.hidden, key] : current.hidden.filter((k) => k !== key),
        }));

    return { view, setView, pin, toggleHidden };
}

export function resetView(storageKey: string) {
    try {
        localStorage.removeItem(storageKey);
    } catch {
        // As above: nothing to clear if storage is unavailable.
    }
}
