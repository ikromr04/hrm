import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Учётная запись',
        href: '/settings/profile',
    },
];

/**
 * The address a person signs in with, and the only thing about themselves they
 * settle here: the name, the position and the rest of the card are filed by HR.
 *
 * A new address is asked for rather than set. A letter goes to the address
 * asked for, and the account keeps the one it has until that letter is
 * answered — a typo would otherwise lock somebody out of the system.
 */
export default function Profile({ pendingEmail, status }: { pendingEmail: string | null; status?: string }) {
    const { auth } = usePage<SharedData>().props;
    // Everything in the shell is behind the door, so there is somebody here.
    const user = auth.user!;

    const { data, setData, patch, errors, processing } = useForm({
        email: pendingEmail ?? user.email,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Учётная запись" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Учётная запись" description="Адрес, с которым вы входите в систему" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid max-w-md content-start gap-2">
                            <Label htmlFor="email">Электронная почта</Label>

                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                required
                                autoComplete="username"
                                placeholder="name@evolet.tj"
                                aria-invalid={!!errors.email}
                            />

                            <InputError message={errors.email} />

                            <p className="text-muted-foreground text-[13px]">
                                Сейчас вход по адресу <span className="font-medium">{user.email}</span>.
                            </p>
                        </div>

                        {status === 'email-changed' && (
                            <p className="text-brand-strong text-sm font-medium dark:text-[#C5E27A]">Адрес подтверждён — теперь входите по нему.</p>
                        )}

                        {pendingEmail !== null && (
                            <div className="max-w-md space-y-2 rounded-lg border p-4">
                                <p className="text-sm">
                                    Ждём подтверждения адреса <span className="font-medium">{pendingEmail}</span>.
                                </p>
                                <p className="text-muted-foreground text-[13px]">
                                    {status === 'email-confirmation-sent'
                                        ? 'Письмо отправлено. Перейдите по ссылке из него — до этого вход по прежнему адресу.'
                                        : 'Перейдите по ссылке из письма — до этого вход по прежнему адресу. Ссылка действует час.'}
                                </p>

                                <div className="flex items-center gap-3 pt-1">
                                    <Button type="submit" variant="outline" size="sm" disabled={processing}>
                                        Отправить письмо ещё раз
                                    </Button>
                                    <Link
                                        href={route('email.cancel')}
                                        method="delete"
                                        as="button"
                                        className="text-muted-foreground text-[13px] underline"
                                    >
                                        Отменить смену
                                    </Link>
                                </div>
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Сохранить</Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
