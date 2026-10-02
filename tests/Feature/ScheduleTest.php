<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * The server has no queue worker: shared hosting lets nothing stay running. One
 * cron line runs the schedule every minute, and the schedule is what sends the
 * letters.
 */
class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_schedule_sends_the_letters_waiting_in_the_queue()
    {
        // As on the server: letters wait in the jobs table rather than leave at once.
        config(['queue.default' => 'database']);

        $sent = [];
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
            $sent[] = $event->message->getTo()[0]->getAddress();
        });

        User::factory()->create(['email' => 'new@evolet.tj'])->notify(new AccountCreated('secret'));

        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame([], $sent);

        $this->artisan('schedule:run')->assertSuccessful();

        $this->assertSame(['new@evolet.tj'], $sent);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_nothing_on_the_schedule_starts_a_process_of_its_own()
    {
        // Schedule::command() runs through proc_open(), which shared hosting
        // commonly disables; a task written that way would never run there.
        // Tasks called in-process carry no command line.
        $events = app(Schedule::class)->events();

        $this->assertNotEmpty($events);

        foreach ($events as $event) {
            $this->assertNull($event->command, "«{$event->description}» is run as a separate process.");
        }
    }

    public function test_the_queue_is_emptied_every_minute_and_never_twice_at_once()
    {
        $drain = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->description, 'queue:work'));

        $this->assertNotNull($drain);
        $this->assertSame('* * * * *', $drain->expression);
        $this->assertTrue($drain->withoutOverlapping);
        // A lock left behind by a killed process must not hold the letters up for
        // a day, which is how long it lasts unless told otherwise.
        $this->assertLessThanOrEqual(10, $drain->expiresAt);
    }
}
