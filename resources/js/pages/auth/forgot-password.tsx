import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type ForgotPasswordForm = {
    email: string;
};

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm<ForgotPasswordForm>({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <AuthLayout title="Восстановление пароля" description="Введите рабочую почту — мы пришлём ссылку для сброса пароля">
            <Head title="Восстановление пароля" />

            {status && <div className="bg-brand-soft text-brand-strong rounded-md px-3 py-2 text-center text-sm font-medium">{status}</div>}

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid content-start gap-2">
                    <Label htmlFor="email">Электронная почта</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autoFocus
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="name@evolet.tj"
                        aria-invalid={!!errors.email}
                    />
                    <InputError message={errors.email} />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    Отправить ссылку
                </Button>

                <p className="text-muted-foreground text-center text-sm">
                    Вспомнили пароль? <TextLink href={route('login')}>Вернуться ко входу</TextLink>
                </p>
            </form>
        </AuthLayout>
    );
}
