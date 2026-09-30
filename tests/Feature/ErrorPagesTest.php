<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * A wrong address.
 *
 * It is answered by a page of the application rather than by the framework's own
 * screen — and it still answers 404, so a link checker and a browser are told the
 * same thing the page says.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_wrong_address_is_a_page_of_the_application()
    {
        $this->actingAs(User::factory()->create())
            ->get('/no-such-page')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('errors/404'));
    }

    public function test_a_visitor_who_is_not_signed_in_gets_it_too()
    {
        // Not a redirect to the login page: the address is wrong whoever asks, and
        // saying "sign in" would send somebody looking for a page that is not there.
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('errors/404')->where('auth.user', null));
    }

    public function test_an_unknown_employee_is_not_found_rather_than_refused()
    {
        $this->actingAs($this->colleague())
            ->get('/employees/999999')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('errors/404'));
    }

    public function test_the_api_still_answers_in_json()
    {
        // A page is for a person; a request that asked for data gets data.
        $this->actingAs(User::factory()->create())
            ->getJson('/no-such-page')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_a_refusal_is_a_page_of_the_application_too()
    {
        // The page exists and is simply not theirs, which is a different sentence
        // and a different number.
        $this->actingAs(User::factory()->create())
            ->get('/employees')
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('errors/403'));
    }

    public function test_an_expired_session_sends_them_back_with_a_sentence()
    {
        // Tests run without the token check, so the expiry is raised by hand the
        // way the check would raise it.
        Route::middleware('web')->post('/_qa-expired', fn () => throw new TokenMismatchException);

        $this->actingAs(User::factory()->create())
            ->from('/profile')
            ->post('/_qa-expired')
            ->assertRedirect('/profile')
            ->assertSessionHas('notice');
    }

    public function test_a_refusal_asked_for_in_json_stays_json()
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/employees')
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }
}
