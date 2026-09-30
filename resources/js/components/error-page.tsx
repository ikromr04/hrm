import EvoletLogo from '@/components/evolet-logo';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

/**
 * What a page says when it cannot be the page that was asked for.
 *
 * Said plainly and without drama: a number, a line about what happened, and the
 * two things one actually wants — back where they came from, or to the start.
 * Signed in, it sits inside the usual shell, so the sidebar and the search are
 * still there and this is a detour rather than a dead end.
 */
export function ErrorPage({
    code,
    title,
    description,
    icon: Icon,
    note,
}: {
    code: number;
    title: string;
    description: string;
    icon: LucideIcon;
    /** A line under the buttons, where one is worth saying. */
    note?: ReactNode;
}) {
    const { auth } = usePage<SharedData>().props;

    const body = (
        <div className="flex flex-1 items-center justify-center p-6">
            <div className="flex w-full max-w-md flex-col items-center gap-6 text-center">
                <span className="bg-muted text-muted-foreground flex size-16 items-center justify-center rounded-2xl">
                    <Icon className="size-8" />
                </span>

                <div className="flex flex-col items-center gap-2">
                    {/* The number is the whole explanation, so it is allowed to be large. */}
                    <span className="text-brand-strong text-5xl font-bold tracking-tight tabular-nums dark:text-[#C5E27A]">{code}</span>
                    <h1 className="text-xl font-semibold tracking-tight">{title}</h1>
                    <p className="text-muted-foreground text-sm leading-relaxed text-balance">{description}</p>
                </div>

                <div className="flex flex-wrap items-center justify-center gap-2">
                    <Button variant="outline" onClick={() => window.history.back()}>
                        <ArrowLeft />
                        Назад
                    </Button>
                    <Button asChild>
                        <Link href={auth.user ? '/' : route('login')}>{auth.user ? 'На главную' : 'Войти'}</Link>
                    </Button>
                </div>

                {auth.user && note}
            </div>
        </div>
    );

    return (
        <>
            <Head title={title} />
            {auth.user ? <AppLayout fitViewport>{body}</AppLayout> : <Guest>{body}</Guest>}
        </>
    );
}

/** Without an account there is no shell to sit in, so the page brings its own. */
function Guest({ children }: { children: ReactNode }) {
    return (
        <div className="bg-background flex min-h-dvh flex-col gap-4 p-6 sm:p-10">
            <Link href={route('login')} className="flex items-center gap-2.5 self-center">
                <EvoletLogo className="h-8 w-32 dark:hidden" />
                <EvoletLogo tone="light" className="hidden h-8 w-32 dark:block" />
                <span className="border-border bg-background text-muted-foreground rounded-md border px-1.5 py-0.5 text-[11px] font-semibold tracking-wider">
                    HRM
                </span>
            </Link>

            <main className="flex flex-1 items-center justify-center">{children}</main>

            <p className="text-muted-foreground text-center text-xs">© {new Date().getFullYear()} Evolet</p>
        </div>
    );
}
