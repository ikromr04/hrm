<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The letter a new colleague gets: where to sign in, with what, and the
 * password picked for them. The password is never stored in readable form, so
 * this is the one time it is ever shown — losing it means resetting it.
 */
class AccountCreated extends Notification
{
    use Queueable;

    public function __construct(private readonly string $password) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Доступ в Evolet HRM')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line('Для вас создана учётная запись в системе Evolet HRM.')
            ->line("**Логин:** {$notifiable->email}")
            ->line("**Пароль:** {$this->password}")
            ->action('Войти в систему', route('login'))
            ->line('Пароль лучше сменить после первого входа: Настройки → Пароль.')
            ->salutation('С уважением, Evolet HRM');
    }
}
