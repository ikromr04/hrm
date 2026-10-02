<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A line behind the bell.
 *
 * Every such line is the same three things: what kind of event it is, which
 * decides the icon; one sentence, written when it happened so it still reads
 * true after the names involved have changed; and the page it is about. The
 * page is kept as a kind and an id rather than as an address, because whether
 * it may be offered as a link is asked of the reader's rights when the list is
 * opened, not when the line was written.
 *
 * Written straight to the table rather than from the queue: it is one insert,
 * and the count beside the bell should be right on the very next page.
 */
abstract class InAppNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind(),
            'text' => $this->text(),
            'target' => $this->target(),
            ...$this->extra(),
        ];
    }

    /** What happened, in a word the list picks an icon by. */
    abstract protected function kind(): string;

    /** The sentence itself. */
    abstract protected function text(): string;

    /**
     * The card the line is about: an employee's or a unit's.
     *
     * @return array{type: 'employee'|'equipment', id: int}|null
     */
    abstract protected function target(): ?array;

    /**
     * Anything else a kind keeps beside the sentence.
     *
     * @return array<string, mixed>
     */
    protected function extra(): array
    {
        return [];
    }
}
