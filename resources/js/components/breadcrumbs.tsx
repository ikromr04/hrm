import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Fragment } from 'react';

/**
 * The trail in the header. A phone keeps only the page itself and a tablet its
 * parent as well: the header also holds the menu, the search and two buttons,
 * and a trail that wrapped would push them out of a 56px bar. Below the desktop
 * the crumbs stay on one line and cut themselves short instead.
 */
export function Breadcrumbs({ breadcrumbs }: { breadcrumbs: BreadcrumbItemType[] }) {
    return (
        <>
            {breadcrumbs.length > 0 && (
                <Breadcrumb>
                    <BreadcrumbList className="max-lg:flex-nowrap">
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;
                            // Crumbs further up than the parent show only on a desktop.
                            const fromEnd = breadcrumbs.length - 1 - index;
                            const shown = fromEnd === 0 ? undefined : fromEnd === 1 ? 'max-md:hidden' : 'max-lg:hidden';

                            return (
                                <Fragment key={index}>
                                    <BreadcrumbItem className={cn('max-lg:min-w-0', shown)}>
                                        {isLast ? (
                                            <BreadcrumbPage className="max-lg:truncate">{item.title}</BreadcrumbPage>
                                        ) : (
                                            <BreadcrumbLink href={item.href} className="max-lg:truncate">
                                                {item.title}
                                            </BreadcrumbLink>
                                        )}
                                    </BreadcrumbItem>
                                    {!isLast && <BreadcrumbSeparator className={shown} />}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            )}
        </>
    );
}
