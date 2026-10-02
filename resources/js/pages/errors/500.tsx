import { ErrorPage } from '@/components/error-page';
import { TriangleAlert } from 'lucide-react';

/**
 * Something of ours broke.
 *
 * Whoever sees this did nothing wrong and can fix nothing, so it says only what
 * is useful to them: it is not their doing, it has been written down, and the
 * rest of the system is still where it was.
 */
export default function ServerError() {
    return (
        <ErrorPage
            code={500}
            icon={TriangleAlert}
            title="Что-то пошло не так"
            description="Ошибка на стороне системы, а не в ваших действиях. Она уже записана в журнал. Попробуйте ещё раз чуть позже."
            note={
                <p className="text-muted-foreground text-[13px]">
                    Если ошибка повторяется, сообщите системному администратору, что вы делали в этот момент.
                </p>
            }
        />
    );
}
