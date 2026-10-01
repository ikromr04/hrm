<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\ConfirmNewEmail;
use App\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Every letter leaves through the queue, so a slow or absent mail server never
 * fails what the person was doing; and each one is actually written and goes
 * to the address it is meant for.
 */
class MailQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_letter_is_queued()
    {
        $this->assertInstanceOf(ShouldQueue::class, new AccountCreated('secret'));
        $this->assertInstanceOf(ShouldQueue::class, new ConfirmNewEmail('new@evolet.tj'));
        $this->assertInstanceOf(ShouldQueue::class, new ResetPassword('token'));
    }

    public function test_the_password_in_the_welcome_letter_is_encrypted_while_it_waits()
    {
        $this->assertInstanceOf(ShouldBeEncrypted::class, new AccountCreated('secret'));
    }

    public function test_a_new_address_is_confirmed_by_a_letter_sent_to_that_address()
    {
        $user = User::factory()->create(['email' => 'old@evolet.tj']);
        $sent = [];
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
            $sent[] = array_map(fn ($address) => $address->getAddress(), $event->message->getTo());
        });

        // The array mailer writes the letter for real, so a broken message
        // fails here rather than on the mail server.
        $user->notify(new ConfirmNewEmail('new@evolet.tj'));

        $this->assertSame([['new@evolet.tj']], $sent);
    }

    public function test_other_letters_go_to_the_account_address()
    {
        $user = User::factory()->create(['email' => 'old@evolet.tj']);

        $this->assertSame('old@evolet.tj', $user->routeNotificationFor('mail', new AccountCreated('secret')));
    }
}
