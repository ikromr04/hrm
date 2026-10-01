<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The letter that changes an address. It goes to the new one, not the old: the
 * only proof that an address belongs to somebody is that they can read what
 * was sent to it. Until the link is followed the account keeps the address it
 * has, so a typo cannot lock anybody out of the system.
 *
 * Sent from the queue, like every letter here: the settings page answers at
 * once whatever the mail server is doing. The link's hour starts when the
 * letter is actually written, not when it was asked for.
 */
class ConfirmNewEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /** The address being confirmed; User::routeNotificationForMail() sends the letter there. */
    public function __construct(public readonly string $email) {}

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
            ->subject('Подтверждение нового адреса — Evolet HRM')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("В учётной записи Evolet HRM запрошена смена адреса на **{$this->email}**.")
            ->line('Адрес сменится только после того, как вы перейдёте по ссылке ниже. До этого входить нужно по старому адресу.')
            ->action('Подтвердить адрес', $this->link($notifiable))
            ->line('Ссылка действует час. Если смену запрашивали не вы — просто не переходите по ней.')
            ->salutation('С уважением, Evolet HRM');
    }

    /**
     * A signed link that expires, naming the address it confirms: a letter sent
     * for one address must not confirm another one asked for later.
     */
    private function link(object $notifiable): string
    {
        return URL::temporarySignedRoute('email.confirm', now()->addHour(), [
            'user' => $notifiable->getKey(),
            'hash' => sha1($this->email),
        ]);
    }
}
