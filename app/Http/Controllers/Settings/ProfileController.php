<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\ConfirmNewEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The one thing a person settles about themselves: the address they sign in
 * with. Everything else on their card — the name, the position, the department
 * — is filed by HR, from their profile.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            // An address asked for and still waiting on its letter.
            'pendingEmail' => $request->user()->pending_email,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Asking for a new address, not setting one: the letter goes to the address
     * asked for, and only answering it makes the change. Proof that an address
     * belongs to somebody is that they can read what was sent there.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $email = $request->validated()['email'];

        if ($email === $user->email) {
            // Asked for what they already have: whatever was waiting is dropped.
            $user->forceFill(['pending_email' => null])->save();

            return to_route('profile.edit');
        }

        $user->forceFill(['pending_email' => $email])->save();
        $user->notify(new ConfirmNewEmail($email));

        return to_route('profile.edit')->with('status', 'email-confirmation-sent');
    }

    /**
     * The link from that letter. The hash names the address the letter was sent
     * for, so a letter cannot confirm an address asked for after it.
     */
    public function confirm(Request $request, User $user, string $hash): RedirectResponse
    {
        abort_unless($request->user()->is($user), 403);
        abort_unless($user->pending_email !== null && hash_equals(sha1($user->pending_email), $hash), 403);

        $user->forceFill([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        return to_route('profile.edit')->with('status', 'email-changed');
    }

    /**
     * Thinking better of it: the address stays as it is.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['pending_email' => null])->save();

        return to_route('profile.edit');
    }

    // Nobody closes their own account: an employee is removed by an
    // administrator, from their profile, along with everything on file.
}
