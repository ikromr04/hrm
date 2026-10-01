<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The framework's reset letter, sent from the queue like the others. Besides
 * not hanging on the mail server, the forgotten-password page then answers
 * just as fast for an address that exists as for one that does not.
 */
class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    //
}
