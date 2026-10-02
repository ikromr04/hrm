<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * What the bell opens: one's own notifications, and marking them read.
 *
 * Every page carries only the count; the list is asked for when the bell is
 * pressed, which is why it answers in JSON rather than as a page. All three
 * addresses go through the signed-in person's own notifications, so there is
 * no id by which somebody else's could be reached.
 */
class NotificationController extends Controller
{
    /** How far back the list goes. It is a place to catch up, not an archive. */
    private const LIMIT = 30;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->limit(self::LIMIT)->get();
        $links = $this->links($user, $notifications);

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $notifications->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'kind' => $notification->data['kind'] ?? null,
                'text' => $notification->data['text'] ?? '',
                // The day a reminder was about, for the kinds that have one.
                'due' => $notification->data['due'] ?? null,
                'href' => $links[$notification->id] ?? null,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return $this->unread($request);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->unread($request);
    }

    /** What the badge should say now. */
    private function unread(Request $request): JsonResponse
    {
        return response()->json(['unread' => $request->user()->unreadNotifications()->count()]);
    }

    /**
     * Where each line leads, for the lines that may lead anywhere.
     *
     * A line is a sentence first; the link is offered on top of it only when
     * its reader may open the card it is about, asked now rather than when the
     * line was written — rights change, units are returned, people are deleted.
     * Without it the line stays as plain text, which is still true.
     *
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return array<string, string>
     */
    private function links(User $reader, Collection $notifications): array
    {
        $targets = $notifications
            ->keyBy('id')
            ->filter(fn (DatabaseNotification $n) => isset($n->data['target']['type'], $n->data['target']['id']))
            ->map(fn (DatabaseNotification $n) => $n->data['target']);

        $ids = fn (string $type) => $targets->where('type', $type)->pluck('id')->unique()->all();

        $employees = User::query()->whereKey($ids('employee'))->get()
            ->filter(fn (User $employee) => $reader->can('view', $employee))
            ->mapWithKeys(fn (User $employee) => [$employee->id => route('employees.show', $employee, false)]);

        $units = Equipment::query()->whereKey($ids('equipment'))->get()
            ->filter(fn (Equipment $unit) => $reader->can('view', $unit))
            ->mapWithKeys(fn (Equipment $unit) => [$unit->id => route('equipment.show', $unit, false)]);

        $links = [];

        foreach ($targets as $id => $target) {
            $href = ($target['type'] === 'employee' ? $employees : $units)[$target['id']] ?? null;

            if ($href !== null) {
                $links[$id] = $href;
            }
        }

        return $links;
    }
}
