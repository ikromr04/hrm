import EvoletLogo from '@/components/evolet-logo';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

const MODULES = ['Сотрудники', 'Оргструктура', 'Оборудование', 'ПИР', 'KPI'];

export default function AuthEvoletLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="bg-background grid min-h-dvh lg:grid-cols-2">
            <div className="flex flex-col gap-4 p-6 sm:p-10">
                <Link href={route('home')} className="flex items-center gap-2.5 self-start">
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

            <aside className="bg-brand-ink relative m-2 hidden flex-col overflow-hidden rounded-[14px] p-12 text-white lg:flex">
                <svg
                    viewBox="0 0 105 93"
                    fill="#A8CF45"
                    fillRule="evenodd"
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-[90px] -right-[140px] h-[531px] w-[600px]"
                >
                    <path d="M57.967 58.643 88.17 6.327H9.25L12.9.004 99.128 0l-37.51 64.97-3.65-6.323z" />
                    <path d="M31.408 27.372 61.86 80.114l39.463-68.352 3.65 6.322-43.108 74.68L24.11 27.371h7.301z" />
                    <path d="M71.688 19.983H10.956l39.463 68.352h-7.302L0 13.66h75.338l-3.646 6.323z" />
                </svg>

                <div className="relative mt-auto flex flex-col gap-6">
                    <EvoletLogo tone="light" className="h-16 w-64" />
                    <h2 className="max-w-[520px] text-4xl leading-tight font-semibold tracking-tight">Сильная команда — здоровое будущее</h2>
                    <p className="max-w-[460px] text-base leading-relaxed text-[#ECEDEE]">
                        Всё о нашей команде — в одном месте: от первого рабочего дня до отпуска и карьерного роста.
                    </p>
                    <ul className="flex flex-wrap gap-2">
                        {MODULES.map((module) => (
                            <li key={module} className="inline-flex h-6 items-center rounded-md border border-[#5F6267] px-2.5 text-xs font-medium">
                                {module}
                            </li>
                        ))}
                    </ul>
                </div>
            </aside>
        </div>
    );
}
