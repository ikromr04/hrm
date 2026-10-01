import { ErrorPage } from '@/components/error-page';
import { Compass } from 'lucide-react';

/** A wrong address: nothing here, and nothing to be done about it but leave. */
export default function NotFound() {
    return (
        <ErrorPage
            code={404}
            icon={Compass}
            title="Страница не найдена"
            description="Такого адреса нет. Возможно, страницу удалили, а ссылка осталась — или в адресе опечатка."
            note={
                // A phone has no keyboard shortcut to offer; its search is the icon in the top bar.
                <p className="text-muted-foreground text-[13px] max-md:hidden">
                    Или найдите нужное через поиск — <kbd className="border-border rounded border px-1.5 py-0.5 font-sans text-xs">Ctrl</kbd>{' '}
                    <kbd className="border-border rounded border px-1.5 py-0.5 font-sans text-xs">K</kbd>
                </p>
            }
        />
    );
}
