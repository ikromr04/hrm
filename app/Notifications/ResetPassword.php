<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The framework's reset letter, sent from the queue like the others. Besides
 * not hanging on the mail server, the forgotten-password page then answers
 * just as fast for an address that exists as for one that does not.
 */
class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    // The framework's letter is not written to be queued, so it lacks what a
    // queued one is asked for — which connection, which queue, what delay.
    use Queueable;
}
