import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';
import { Moon, Sun } from 'lucide-react';

/** Switches between light and dark, starting from whatever the page currently shows. */
export function ThemeToggle() {
    const { updateAppearance } = useAppearance();

    const toggle = () => {
        const isDark = document.documentElement.classList.contains('dark');
        updateAppearance(isDark ? 'light' : 'dark');
    };

    return (
        <Button variant="outline" size="icon" className="size-9" aria-label="Сменить тему" onClick={toggle}>
            <Sun className="dark:hidden" />
            <Moon className="hidden dark:block" />
        </Button>
    );
}
