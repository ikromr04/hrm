import EvoletLogo, { EvoletMark } from '@/components/evolet-logo';

// Wrapped in a span: sidebar menu buttons force direct <svg> children to 16px.
export default function AppLogo() {
    return (
        <span className="flex w-full items-center gap-2.5">
            <EvoletMark className="hidden size-6 group-data-[collapsible=icon]:block" />
            <EvoletLogo className="h-8 w-32 group-data-[collapsible=icon]:hidden dark:hidden" />
            <EvoletLogo tone="light" className="hidden h-8 w-32 dark:block dark:group-data-[collapsible=icon]:hidden" />
            <span className="border-sidebar-border bg-background text-muted-foreground ml-auto rounded-md border px-1.5 py-0.5 text-[11px] font-semibold tracking-wider group-data-[collapsible=icon]:hidden">
                HRM
            </span>
        </span>
    );
}
