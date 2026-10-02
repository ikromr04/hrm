import { ErrorPage } from '@/components/error-page';
import { Button } from '@/components/ui/button';
import { RotateCw, Wrench } from 'lucide-react';

/**
 * Closed for a few minutes while an update is put in (`artisan down`).
 *
 * Nothing is broken and nothing is lost, which is the first thing somebody in
 * the middle of a form wants to hear. Every address answers the same way until
 * the update is done, so the page stands outside the shell and offers the one
 * thing that can help: asking again.
 */
export default function Maintenance() {
    return (
        <ErrorPage
            code={503}
            icon={Wrench}
            title="Идёт обновление системы"
            description="Система ненадолго закрыта на обслуживание. Все данные в сохранности — зайдите через несколько минут."
            standalone
            actions={
                <Button onClick={() => window.location.reload()} className="max-md:h-11 max-md:text-[15px]">
                    <RotateCw />
                    Обновить страницу
                </Button>
            }
        />
    );
}
