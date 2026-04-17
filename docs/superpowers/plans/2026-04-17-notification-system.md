# Notification System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add full-stack push notifications to KPI BASS — real-time in-app via Pusher/Echo and background push via Firebase Cloud Messaging (FCM).

**Architecture:** A `NotificationService` creates `KpiNotification` records, broadcasts a Pusher event on `kpi.user.{id}`, and calls `FcmService` to push to registered browser/device tokens. Frontend listens via Echo for instant badge/toast updates and registers a service worker for background push.

**Tech Stack:** Laravel (Sanctum, Pusher, `kreait/laravel-firebase`), Vue 3 + Pinia, Firebase JS SDK v10, Web Push API (service worker)

---

## Prerequisites (manual steps before coding)

- [ ] Create a Firebase project at https://console.firebase.google.com
- [ ] Enable **Cloud Messaging** in the Firebase project
- [ ] Download the **Service Account JSON** → save as `storage/app/firebase-credentials.json` (never commit)
- [ ] In Firebase Console → Project Settings → Cloud Messaging → **Web Push certificates** → Generate key pair → copy the **VAPID public key**
- [ ] Add to `.env`:
```
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
VITE_FIREBASE_API_KEY=
VITE_FIREBASE_AUTH_DOMAIN=
VITE_FIREBASE_PROJECT_ID=
VITE_FIREBASE_STORAGE_BUCKET=
VITE_FIREBASE_MESSAGING_SENDER_ID=
VITE_FIREBASE_APP_ID=
VITE_FIREBASE_VAPID_KEY=
```
  (Values come from Firebase Console → Project Settings → General → Your apps → Web app config)

---

## File Map

| Action | File |
|---|---|
| Create | `database/migrations/2026_04_17_000001_create_fcm_tokens_table.php` |
| Create | `app/Models/FcmToken.php` |
| Modify | `app/Models/User.php` — add `fcmTokens()` relation |
| Create | `app/Services/FcmService.php` |
| Create | `app/Services/NotificationService.php` |
| Create | `app/Events/UserNotificationCreated.php` |
| Create | `app/Http/Controllers/Api/FcmTokenController.php` |
| Modify | `app/Http/Controllers/Api/NotificationController.php` — add `destroy()` |
| Modify | `app/Http/Controllers/Api/TaskController.php` — inject NotificationService |
| Modify | `app/Http/Controllers/Api/KpiManagementController.php` — inject NotificationService |
| Modify | `app/Http/Controllers/Api/KpiReportController.php` — inject NotificationService |
| Modify | `routes/api.php` — add FCM token + delete notification routes |
| Modify | `routes/channels.php` — already has `kpi.user.{userId}` ✓ |
| Create | `app/Console/Commands/NotifyDeadlineReminder.php` |
| Modify | `routes/console.php` — register daily schedule |
| Create | `resources/js/services/firebase.js` |
| Create | `public/firebase-messaging-sw.js` |
| Create | `resources/js/composables/useNotification.js` |
| Modify | `resources/js/stores/notification.js` — add `addRealtime()`, `deleteNotification()` |
| Modify | `resources/js/stores/auth.js` — call `init()` after login, `cleanup()` on logout |
| Modify | `resources/js/components/shared/NotificationBell.vue` — bounce badge, delete, 360px |
| Create | `tests/Feature/NotificationServiceTest.php` |
| Create | `tests/Feature/FcmTokenControllerTest.php` |

---

## Task 1: Install Firebase PHP package

**Files:**
- Modify: `composer.json` (via composer command)
- Create: `config/firebase.php` (published by package)

- [ ] **Step 1: Install the package**

```bash
cd "C:\Users\PC6\Documents\IT Ga\KPI_LARAVEL"
composer require kreait/laravel-firebase
```

Expected: package resolves and installs successfully.

- [ ] **Step 2: Publish the config**

```bash
php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider" --tag=config
```

Expected: `config/firebase.php` created.

- [ ] **Step 3: Verify `.env` has the credentials path**

Open `.env` and confirm this line exists (add if missing):
```
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
```

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock config/firebase.php
git commit -m "chore: install kreait/laravel-firebase for FCM push notifications"
```

---

## Task 2: FCM tokens migration + model

**Files:**
- Create: `database/migrations/2026_04_17_000001_create_fcm_tokens_table.php`
- Create: `app/Models/FcmToken.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/FcmTokenControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/fcm/token', [
            'token' => 'test-fcm-token-abc123',
            'device_type' => 'web',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'test-fcm-token-abc123',
        ]);
    }

    public function test_registering_same_token_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/fcm/token', ['token' => 'dup-token', 'device_type' => 'web']);
        $this->postJson('/api/fcm/token', ['token' => 'dup-token', 'device_type' => 'web']);

        $this->assertDatabaseCount('fcm_tokens', 1);
    }

    public function test_user_can_delete_their_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        FcmToken::create(['user_id' => $user->id, 'token' => 'del-token', 'device_type' => 'web']);

        $response = $this->deleteJson('/api/fcm/token', ['token' => 'del-token']);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('fcm_tokens', ['token' => 'del-token']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/FcmTokenControllerTest.php
```

Expected: FAIL — "Table fcm_tokens not found" or route 404.

- [ ] **Step 3: Create migration**

```bash
php artisan make:migration create_fcm_tokens_table
```

Open the generated file in `database/migrations/` and replace its content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('token');
            $table->enum('device_type', ['web', 'android', 'ios'])->default('web');
            $table->timestamps();
            $table->unique(['user_id', 'token'], 'unique_user_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate
```

Expected: `fcm_tokens` table created.

- [ ] **Step 5: Create FcmToken model**

Create `app/Models/FcmToken.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    protected $fillable = ['user_id', 'token', 'device_type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 6: Add relation to User model**

Open `app/Models/User.php`. Find the `kpiNotifications()` method and add after it:

```php
public function fcmTokens(): HasMany
{
    return $this->hasMany(FcmToken::class);
}
```

Also add the import at the top if not present: `use Illuminate\Database\Eloquent\Relations\HasMany;`

- [ ] **Step 7: Run tests to verify they still fail (route missing)**

```bash
php artisan test tests/Feature/FcmTokenControllerTest.php
```

Expected: FAIL — 404 on `/api/fcm/token` (route doesn't exist yet).

- [ ] **Step 8: Commit**

```bash
git add database/migrations/ app/Models/FcmToken.php app/Models/User.php
git commit -m "feat: add fcm_tokens table, model, and User relation"
```

---

## Task 3: FcmTokenController + routes

**Files:**
- Create: `app/Http/Controllers/Api/FcmTokenController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Create FcmTokenController**

Create `app/Http/Controllers/Api/FcmTokenController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device_type' => ['nullable', 'in:web,android,ios'],
        ]);

        FcmToken::updateOrCreate(
            ['user_id' => $request->user()->id, 'token' => $data['token']],
            ['device_type' => $data['device_type'] ?? 'web'],
        );

        return $this->success(null, 'FCM token disimpan.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $request->user()
            ->fcmTokens()
            ->where('token', $data['token'])
            ->delete();

        return $this->success(null, 'FCM token dihapus.');
    }
}
```

- [ ] **Step 2: Add routes to api.php**

Open `routes/api.php`. Inside the `auth:sanctum` middleware group, add after the notification routes:

```php
use App\Http\Controllers\Api\FcmTokenController;

// FCM token registration
Route::post('/fcm/token', [FcmTokenController::class, 'store']);
Route::delete('/fcm/token', [FcmTokenController::class, 'destroy']);
```

Also add the `use` import at the top of the file with the other imports.

- [ ] **Step 3: Run tests to verify they pass**

```bash
php artisan test tests/Feature/FcmTokenControllerTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/FcmTokenController.php routes/api.php tests/Feature/FcmTokenControllerTest.php
git commit -m "feat: add FcmTokenController with store/destroy endpoints"
```

---

## Task 4: FcmService

**Files:**
- Create: `app/Services/FcmService.php`

- [ ] **Step 1: Create FcmService**

Create `app/Services/FcmService.php`:

```php
<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct(private readonly Messaging $messaging) {}

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($title, $body))
            ->withData(array_map('strval', $data));

        try {
            $report = $this->messaging->sendMulticast($message, $tokens);

            if ($report->hasFailures()) {
                $invalidTokens = $report->invalidTokens();
                if (!empty($invalidTokens)) {
                    $user->fcmTokens()
                        ->whereIn('token', $invalidTokens)
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/FcmService.php
git commit -m "feat: add FcmService for sending FCM push notifications"
```

---

## Task 5: Broadcasting event

**Files:**
- Create: `app/Events/UserNotificationCreated.php`

- [ ] **Step 1: Create the broadcast event**

Create `app/Events/UserNotificationCreated.php`:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly array $notification,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('kpi.user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        return $this->notification;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Events/UserNotificationCreated.php
git commit -m "feat: add UserNotificationCreated broadcast event"
```

---

## Task 6: NotificationService

**Files:**
- Create: `app/Services/NotificationService.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/NotificationServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\KpiNotification;
use App\Models\User;
use App\Services\FcmService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\UserNotificationCreated;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_creates_kpi_notification_record(): void
    {
        Event::fake();
        $this->mock(FcmService::class)->shouldReceive('sendToUser')->once();

        $user = User::factory()->create();
        $service = app(NotificationService::class);

        $service->send($user, 'task_assigned', 'Task Baru', 'Kamu dapat task baru.', ['task_id' => 1]);

        $this->assertDatabaseHas('kpi_notifications', [
            'user_id' => $user->id,
            'type' => 'task_assigned',
            'title' => 'Task Baru',
            'body' => 'Kamu dapat task baru.',
        ]);
    }

    public function test_send_dispatches_broadcast_event(): void
    {
        Event::fake();
        $this->mock(FcmService::class)->shouldReceive('sendToUser')->once();

        $user = User::factory()->create();
        app(NotificationService::class)->send($user, 'task_assigned', 'Task Baru', 'Body', []);

        Event::assertDispatched(UserNotificationCreated::class, fn ($e) => $e->userId === $user->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/NotificationServiceTest.php
```

Expected: FAIL — `NotificationService` class not found.

- [ ] **Step 3: Create NotificationService**

Create `app/Services/NotificationService.php`:

```php
<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\KpiNotification;
use App\Models\User;

class NotificationService
{
    public function __construct(private readonly FcmService $fcm) {}

    public function send(User $user, string $type, string $title, string $body, array $data = []): KpiNotification
    {
        $notification = KpiNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $data,
        ]);

        UserNotificationCreated::dispatch($user->id, [
            'id' => $notification->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $data,
            'is_read' => false,
            'read_at' => null,
            'created_at' => $notification->created_at->toISOString(),
        ]);

        $this->fcm->sendToUser($user, $title, $body, array_merge($data, ['type' => $type]));

        return $notification;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Feature/NotificationServiceTest.php
```

Expected: 2 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/NotificationService.php tests/Feature/NotificationServiceTest.php
git commit -m "feat: add NotificationService orchestrating DB + broadcast + FCM"
```

---

## Task 7: Add delete endpoint to NotificationController

**Files:**
- Modify: `app/Http/Controllers/Api/NotificationController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Add `destroy()` to NotificationController**

Open `app/Http/Controllers/Api/NotificationController.php` and add this method after `markAllRead()`:

```php
public function destroy(Request $request, KpiNotification $notification): JsonResponse
{
    if ($notification->user_id !== $request->user()->id) {
        return $this->error('Akses ditolak.', [], 403);
    }

    $notification->delete();

    return $this->success(null, 'Notifikasi dihapus.');
}
```

- [ ] **Step 2: Add delete route to api.php**

In `routes/api.php`, inside the `auth:sanctum` group, add after the existing notification routes:

```php
Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Api/NotificationController.php routes/api.php
git commit -m "feat: add DELETE /api/notifications/{id} endpoint"
```

---

## Task 8: Trigger notifications from controllers

**Files:**
- Modify: `app/Http/Controllers/Api/TaskController.php`
- Modify: `app/Http/Controllers/Api/KpiManagementController.php`
- Modify: `app/Http/Controllers/Api/KpiReportController.php`

- [ ] **Step 1: Inject NotificationService into TaskController**

Open `app/Http/Controllers/Api/TaskController.php`.

Change the constructor to inject NotificationService:

```php
public function __construct(
    private readonly TaskAssignmentService $taskAssignmentService,
    private readonly \App\Services\NotificationService $notificationService,
) {}
```

In the `store()` method, find the block where a manual assignment task is created (after `$task = $this->taskAssignmentService->create(...)`). Add after the `ActivityLog::record(...)` call:

```php
// Notify the assigned employee
if ($task->assignee) {
    $this->notificationService->send(
        $task->assignee,
        'task_assigned',
        'Task Baru Diberikan',
        "Kamu mendapat task baru: {$task->judul}",
        ['task_id' => $task->id],
    );
}
```

- [ ] **Step 2: Inject NotificationService into KpiManagementController**

Open `app/Http/Controllers/Api/KpiManagementController.php`.

Change the constructor:

```php
public function __construct(
    private readonly KpiService $kpiService,
    private readonly \App\Services\NotificationService $notificationService,
) {}
```

In the `input()` method, after `$score = $this->kpiService->inputRecord(...)`, add:

```php
// Notify the employee whose KPI was updated
$employee = \App\Models\User::find($request->validated()['user_id'] ?? null);
if ($employee) {
    $this->notificationService->send(
        $employee,
        'kpi_updated',
        'KPI Kamu Diperbarui',
        'HR telah memperbarui nilai KPI kamu.',
        ['user_id' => $employee->id],
    );
}
```

- [ ] **Step 3: Inject NotificationService into KpiReportController**

Open `app/Http/Controllers/Api/KpiReportController.php`.

Add a constructor if not present, or modify it:

```php
public function __construct(
    private readonly \App\Services\NotificationService $notificationService,
) {}
```

In the `store()` method, after the report is created and saved successfully, add notification to HR managers:

```php
// Notify all HR managers that a report was submitted
\App\Models\User::where('role', 'hr_manager')->each(function ($hr) use ($report, $user) {
    $this->notificationService->send(
        $hr,
        'report_submitted',
        'Laporan KPI Baru',
        "{$user->nama} mengajukan laporan KPI baru.",
        ['report_id' => $report->id, 'user_id' => $user->id],
    );
});
```

In the `review()` method (line ~146), after `$kpiReport->update([...])`, add:

```php
// Notify the report owner
$status = $data['status'];
$this->notificationService->send(
    $kpiReport->user,
    $status === 'approved' ? 'report_approved' : 'report_rejected',
    $status === 'approved' ? 'Laporan KPI Disetujui' : 'Laporan KPI Ditolak',
    $status === 'approved'
        ? 'Laporan KPI kamu telah disetujui oleh HR.'
        : "Laporan KPI kamu ditolak. Catatan: " . ($data['review_note'] ?? '-'),
    ['report_id' => $kpiReport->id],
);
```

- [ ] **Step 4: Run the full test suite to ensure nothing is broken**

```bash
php artisan test --parallel
```

Expected: All pre-existing tests pass. New notification tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/TaskController.php \
        app/Http/Controllers/Api/KpiManagementController.php \
        app/Http/Controllers/Api/KpiReportController.php
git commit -m "feat: trigger notifications from task assign, KPI update, and report review"
```

---

## Task 9: Deadline reminder command

**Files:**
- Create: `app/Console/Commands/NotifyDeadlineReminder.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Create the artisan command**

```bash
php artisan make:command NotifyDeadlineReminder
```

Open `app/Console/Commands/NotifyDeadlineReminder.php` and replace its content:

```php
<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyDeadlineReminder extends Command
{
    protected $signature = 'notify:deadline-reminder';
    protected $description = 'Send deadline reminder notifications for tasks due in 1 or 3 days';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetDates = [
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
        ];

        $tasks = Task::with('assignee')
            ->whereDate('end_date', $targetDates)
            ->whereNotNull('assigned_to')
            ->get();

        foreach ($tasks as $task) {
            if (! $task->assignee) {
                continue;
            }

            $daysLeft = now()->diffInDays($task->end_date, false);
            $daysLabel = $daysLeft === 1 ? 'besok' : "{$daysLeft} hari lagi";

            $this->notificationService->send(
                $task->assignee,
                'deadline_reminder',
                'Pengingat Deadline Task',
                "Task \"{$task->judul}\" jatuh tempo {$daysLabel}.",
                ['task_id' => $task->id, 'days_left' => $daysLeft],
            );
        }

        $this->info("Deadline reminders sent for {$tasks->count()} tasks.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Register schedule in routes/console.php**

Open `routes/console.php` and add:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('notify:deadline-reminder')->dailyAt('07:00');
```

- [ ] **Step 3: Test the command runs without error**

```bash
php artisan notify:deadline-reminder
```

Expected: Output like `Deadline reminders sent for 0 tasks.` (0 if no tasks near deadline).

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/NotifyDeadlineReminder.php routes/console.php
git commit -m "feat: add daily deadline reminder artisan command"
```

---

## Task 10: Install Firebase JS SDK + env config

**Files:**
- Modify: `package.json` (via npm install)
- Create: `resources/js/services/firebase.js`

- [ ] **Step 1: Install Firebase JS SDK**

```bash
npm install firebase
```

Expected: firebase added to `node_modules` and `package.json`.

- [ ] **Step 2: Create resources/js/services/firebase.js**

Create `resources/js/services/firebase.js`:

```js
import { initializeApp } from 'firebase/app';
import { getMessaging } from 'firebase/messaging';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

const app = initializeApp(firebaseConfig);

export const messaging = getMessaging(app);
export const vapidKey = import.meta.env.VITE_FIREBASE_VAPID_KEY;
```

- [ ] **Step 3: Create public/firebase-messaging-sw.js**

Create `public/firebase-messaging-sw.js`:

```js
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

// Config is passed via query param from the SW registration call
// Parse it from the URL
const url = new URL(location.href);
const config = {
    apiKey: url.searchParams.get('apiKey'),
    authDomain: url.searchParams.get('authDomain'),
    projectId: url.searchParams.get('projectId'),
    storageBucket: url.searchParams.get('storageBucket'),
    messagingSenderId: url.searchParams.get('messagingSenderId'),
    appId: url.searchParams.get('appId'),
};

firebase.initializeApp(config);
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    const { title, body } = payload.notification ?? {};
    self.registration.showNotification(title ?? 'KPI BASS', {
        body: body ?? '',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        data: payload.data ?? {},
    });
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow('/');
            }
        }),
    );
});
```

- [ ] **Step 4: Commit**

```bash
git add package.json package-lock.json resources/js/services/firebase.js public/firebase-messaging-sw.js
git commit -m "feat: add Firebase JS SDK and service worker for FCM web push"
```

---

## Task 11: Update notification store

**Files:**
- Modify: `resources/js/stores/notification.js`

- [ ] **Step 1: Open the current store**

File is at `resources/js/stores/notification.js`. The current store has: `notifications`, `isLoading`, `unreadCount`, `recent`, `fetchNotifications()`, `markRead()`, `markAllRead()`.

- [ ] **Step 2: Add `addRealtime()` and `deleteNotification()`**

Replace the entire `return` block at the bottom:

```js
    async function deleteNotification(id) {
        const index = notifications.value.findIndex((n) => n.id === id);
        const removed = notifications.value.splice(index, 1)[0];
        try {
            await api.delete(`/notifications/${id}`);
        } catch {
            if (removed) notifications.value.splice(index, 0, removed);
        }
    }

    function addRealtime(notif) {
        notifications.value.unshift(notif);
    }

    return {
        notifications,
        isLoading,
        unreadCount,
        recent,
        fetchNotifications,
        markRead,
        markAllRead,
        deleteNotification,
        addRealtime,
    };
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/stores/notification.js
git commit -m "feat: add addRealtime() and deleteNotification() to notification store"
```

---

## Task 12: useNotification composable

**Files:**
- Create: `resources/js/composables/useNotification.js`

- [ ] **Step 1: Create the composable**

Create `resources/js/composables/useNotification.js`:

```js
import { getToken, onMessage } from 'firebase/messaging';
import { messaging, vapidKey } from '@/services/firebase';
import { useNotificationStore } from '@/stores/notification';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';

let echoChannel = null;

export function useNotification() {
    const store = useNotificationStore();
    const { showToast } = useToast();
    const auth = useAuthStore();

    async function init() {
        if (!auth.isLoggedIn) return;

        subscribeEcho();
        onForeground();
        await registerFcmToken();
    }

    function onForeground() {
        onMessage(messaging, (payload) => {
            const { title, body } = payload.notification ?? {};
            showToast(title ?? 'Notifikasi', body ?? '', 'info');
        });
    }

    function subscribeEcho() {
        if (!window.Echo || !auth.user?.id) return;

        echoChannel = window.Echo
            .private(`kpi.user.${auth.user.id}`)
            .listen('.notification.new', (notif) => {
                store.addRealtime(notif);
                showToast(notif.title, notif.body, 'info');
            });
    }

    async function registerFcmToken() {
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            // Register SW with firebase config as query params
            const swUrl = buildSwUrl();
            const registration = await navigator.serviceWorker.register(swUrl, { scope: '/' });

            const token = await getToken(messaging, {
                vapidKey,
                serviceWorkerRegistration: registration,
            });

            if (token) {
                await api.post('/fcm/token', { token, device_type: 'web' });
                sessionStorage.setItem('fcm_token', token);
            }
        } catch (err) {
            // FCM permission denied or not supported — in-app realtime still works
            console.debug('FCM not available:', err?.message);
        }
    }

    function buildSwUrl() {
        const params = new URLSearchParams({
            apiKey: import.meta.env.VITE_FIREBASE_API_KEY ?? '',
            authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN ?? '',
            projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID ?? '',
            storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET ?? '',
            messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID ?? '',
            appId: import.meta.env.VITE_FIREBASE_APP_ID ?? '',
        });
        return `/firebase-messaging-sw.js?${params.toString()}`;
    }

    async function cleanup() {
        if (echoChannel) {
            echoChannel.stopListening('.notification.new');
            echoChannel = null;
        }

        const token = sessionStorage.getItem('fcm_token');
        if (token) {
            await api.delete('/fcm/token', { data: { token } }).catch(() => {});
            sessionStorage.removeItem('fcm_token');
        }
    }

    return { init, cleanup };
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/composables/useNotification.js
git commit -m "feat: add useNotification composable (FCM + Echo subscription)"
```

---

## Task 13: Auth store integration

**Files:**
- Modify: `resources/js/stores/auth.js`

- [ ] **Step 1: Import and wire useNotification into auth store**

Open `resources/js/stores/auth.js`. At the top, add the import:

```js
import { useNotification } from '@/composables/useNotification';
```

In the `login()` function, after `localStorage.setItem('user', ...)` and before the role-based router.push, add:

```js
// Start realtime + push notifications
useNotification().init();
```

In the `logout()` function, inside the `try` block before `await api.post('/auth/logout')`, add:

```js
await useNotification().cleanup();
```

- [ ] **Step 2: Also init on page refresh (if already logged in)**

In `fetchMe()`, if it succeeds (after user data is set), add:

```js
useNotification().init();
```

Find the success path in `fetchMe()` — it should be after `user.value = ...` assignment. If `fetchMe` doesn't exist in the file, check `readStoredUser()` — add init call at the bottom of the store setup block:

```js
// Re-init notifications if user is already logged in (page refresh)
if (token.value && user.value) {
    useNotification().init();
}
```

Add this inside the `defineStore` function body, outside any action function.

- [ ] **Step 3: Commit**

```bash
git add resources/js/stores/auth.js
git commit -m "feat: wire useNotification init/cleanup to login/logout in auth store"
```

---

## Task 14: Upgrade NotificationBell component

**Files:**
- Modify: `resources/js/components/shared/NotificationBell.vue`

- [ ] **Step 1: Update the script section**

Replace the current `<script setup>` block with:

```vue
<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notification';
import { useAutoRefresh } from '@/composables/useAutoRefresh';

const store = useNotificationStore();
const router = useRouter();
const open = ref(false);
const hasNew = ref(false);

onMounted(() => store.fetchNotifications());
useAutoRefresh(() => store.fetchNotifications(), { interval: 30_000 });

// Watch for realtime additions to trigger bounce
let prevCount = store.unreadCount;
store.$subscribe(() => {
    if (store.unreadCount > prevCount) {
        hasNew.value = true;
        setTimeout(() => { hasNew.value = false; }, 2000);
    }
    prevCount = store.unreadCount;
});

function toggle() { open.value = !open.value; }
function close()  { open.value = false; }

async function handleMarkAll() { await store.markAllRead(); }
async function handleMarkOne(id) { await store.markRead(id); }
async function handleDelete(id) { await store.deleteNotification(id); }

function goToAll() {
    close();
    router.push('/notifikasi');
}

const typeIcon = {
    task_assigned:      '📋',
    kpi_updated:        '📊',
    report_submitted:   '📄',
    deadline_reminder:  '🔔',
    report_approved:    '✅',
    report_rejected:    '❌',
    low_performance:    '⚠️',
    info:               'ℹ️',
};
</script>
```

- [ ] **Step 2: Update the template section**

Replace the current `<template>` block with:

```vue
<template>
    <div class="relative">
        <!-- Bell button -->
        <button
            type="button"
            class="relative flex h-11 w-11 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
            :class="open ? 'bg-slate-100 text-slate-700' : ''"
            aria-label="Notifikasi"
            @click="toggle"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span
                v-if="store.unreadCount > 0"
                :class="[
                    'absolute -right-0.5 -top-0.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white',
                    hasNew ? 'animate-bounce' : '',
                ]"
            >
                {{ store.unreadCount > 99 ? '99+' : store.unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 top-12 z-50 w-[360px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
            >
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Notifikasi</span>
                        <span
                            v-if="store.unreadCount > 0"
                            class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-600"
                        >
                            {{ store.unreadCount }}
                        </span>
                    </div>
                    <button
                        v-if="store.unreadCount > 0"
                        type="button"
                        class="text-[11px] font-medium text-blue-600 hover:underline"
                        @click="handleMarkAll"
                    >
                        Tandai semua dibaca
                    </button>
                </div>

                <!-- List -->
                <div class="max-h-80 overflow-y-auto">
                    <template v-if="store.isLoading">
                        <div class="px-4 py-10 text-center text-sm text-slate-400">Memuat...</div>
                    </template>
                    <template v-else-if="store.recent.length === 0">
                        <div class="flex flex-col items-center gap-2 px-4 py-10 text-center">
                            <svg class="h-10 w-10 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                            <p class="text-sm text-slate-400">Belum ada notifikasi</p>
                        </div>
                    </template>
                    <template v-else>
                        <div
                            v-for="n in store.recent"
                            :key="n.id"
                            :class="[
                                'group flex w-full gap-3 px-4 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800',
                                !n.is_read ? 'bg-blue-50/60 dark:bg-blue-950/20' : '',
                            ]"
                        >
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 gap-3 text-left"
                                @click="handleMarkOne(n.id)"
                            >
                                <span class="mt-0.5 text-base leading-none">{{ typeIcon[n.type] ?? '🔔' }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[12px] font-semibold text-slate-800 dark:text-slate-100">{{ n.title }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-[11px] text-slate-500 dark:text-slate-400">{{ n.body }}</p>
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ new Date(n.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                    </p>
                                </div>
                            </button>
                            <div class="flex shrink-0 flex-col items-center gap-1 pt-0.5">
                                <div v-if="!n.is_read" class="h-2 w-2 rounded-full bg-blue-500" />
                                <button
                                    type="button"
                                    class="hidden h-5 w-5 items-center justify-center rounded text-slate-300 hover:bg-red-50 hover:text-red-400 group-hover:flex dark:hover:bg-red-950/30"
                                    title="Hapus"
                                    @click.stop="handleDelete(n.id)"
                                >
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="border-t border-slate-100 px-4 py-2.5 dark:border-slate-700">
                    <button
                        type="button"
                        class="w-full text-center text-[12px] font-medium text-blue-600 hover:underline"
                        @click="goToAll"
                    >
                        Lihat semua notifikasi →
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Backdrop -->
        <div v-if="open" class="fixed inset-0 z-40" @click="close" />
    </div>
</template>
```

- [ ] **Step 3: Build frontend to check for errors**

```bash
npm run build 2>&1 | tail -20
```

Expected: Build succeeds with no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/shared/NotificationBell.vue
git commit -m "feat: upgrade NotificationBell with bounce badge, delete, 360px dropdown, and dark mode"
```

---

## Task 15: Final verification

- [ ] **Step 1: Run full test suite**

```bash
php artisan test --parallel
```

Expected: All tests pass.

- [ ] **Step 2: Run frontend build**

```bash
npm run build
```

Expected: No errors.

- [ ] **Step 3: Manual smoke test (with dev server running)**

```bash
npm run dev
```

1. Log in as `pegawai`
2. Open DevTools → Application → Service Workers — verify `firebase-messaging-sw.js` registered
3. Log in as `hr_manager` in a second tab
4. HR assigns a task to pegawai → verify pegawai sees toast + badge updates without page reload
5. Click the bell → verify 360px dropdown opens with the task notification
6. Click × on a notification → verify it disappears (optimistic delete)
7. Click "Tandai semua dibaca" → verify all badges clear

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "feat: complete notification system (Pusher realtime + FCM push + upgraded bell UI)"
```
