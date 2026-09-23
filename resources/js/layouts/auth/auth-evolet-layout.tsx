import EvoletLogo from '@/components/evolet-logo';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthEvoletLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="bg-background min-h-dvh">
            <div className="flex min-h-dvh flex-col gap-4 p-6 sm:p-10">
                <Link href={route('home')} className="flex items-center gap-2.5 self-center">
                    <EvoletLogo className="h-8 w-32 dark:hidden" />
                    <EvoletLogo tone="light" className="hidden h-8 w-32 dark:block" />
                    <span className="border-border bg-background text-muted-foreground rounded-md border px-1.5 py-0.5 text-[11px] font-semibold tracking-wider">
                        HRM
                    </span>
                </Link>

                <main className="flex flex-1 items-center justify-center py-8">
                    <div className="flex w-full max-w-[360px] flex-col gap-6">
                        <div className="flex flex-col items-center gap-2 text-center">
                            <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                            <p className="text-muted-foreground text-sm leading-relaxed text-balance">{description}</p>
                        </div>
                        {children}
                    </div>
                </main>

                <p className="text-muted-foreground text-center text-xs">© {new Date().getFullYear()} Evolet</p>
            </div>
        </div>
    );
}
