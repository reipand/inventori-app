<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'pengelola',
            'is_active' => true,
            'must_change_password' => false,
        ], $overrides));
    }

    private function samplePayload(array $overrides = []): array
    {
        return array_merge([
            'title'   => 'Test Notification',
            'message' => 'This is a test message',
            'type'    => 'info',
            'link'    => null,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // G2.1 — sendToUser menyimpan ke DB
    // -------------------------------------------------------------------------

    #[Test]
    public function sendToUser_persists_notification_to_database(): void
    {
        // Mock FCM so it doesn't make real network calls
        $this->app->bind('firebase.messaging', fn () => $this->createMock(\Kreait\Firebase\Contract\Messaging::class));

        $user = $this->makeUser();
        $payload = $this->samplePayload();

        $result = $this->service->sendToUser($user->id, $payload);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title'   => $payload['title'],
            'message' => $payload['message'],
            'type'    => $payload['type'],
            'is_read' => false,
        ]);
    }

    #[Test]
    public function sendToUser_returns_the_created_notification(): void
    {
        $this->app->bind('firebase.messaging', fn () => $this->createMock(\Kreait\Firebase\Contract\Messaging::class));

        $user = $this->makeUser();
        $payload = $this->samplePayload(['title' => 'Stock Alert', 'type' => 'warning']);

        $result = $this->service->sendToUser($user->id, $payload);

        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals('Stock Alert', $result->title);
        $this->assertEquals('warning', $result->type);
        $this->assertFalse($result->is_read);
        $this->assertNotNull($result->id);
    }

    #[Test]
    public function sendToUser_stores_optional_link_when_provided(): void
    {
        $this->app->bind('firebase.messaging', fn () => $this->createMock(\Kreait\Firebase\Contract\Messaging::class));

        $user = $this->makeUser();
        $payload = $this->samplePayload(['link' => '/products/42']);

        $this->service->sendToUser($user->id, $payload);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'link'    => '/products/42',
        ]);
    }

    #[Test]
    public function sendToUser_stores_null_link_when_not_provided(): void
    {
        $this->app->bind('firebase.messaging', fn () => $this->createMock(\Kreait\Firebase\Contract\Messaging::class));

        $user = $this->makeUser();
        $payload = $this->samplePayload(); // link is null

        $this->service->sendToUser($user->id, $payload);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'link'    => null,
        ]);
    }

    #[Test]
    public function sendToUser_sends_fcm_to_all_registered_devices(): void
    {
        $user = $this->makeUser();

        UserDevice::create(['user_id' => $user->id, 'fcm_token' => 'token-device-1']);
        UserDevice::create(['user_id' => $user->id, 'fcm_token' => 'token-device-2']);

        $mockMessaging = $this->createMock(\Kreait\Firebase\Contract\Messaging::class);
        $mockMessaging->expects($this->exactly(2))->method('send');

        $this->app->bind('firebase.messaging', fn () => $mockMessaging);

        $this->service->sendToUser($user->id, $this->samplePayload());
    }

    #[Test]
    public function sendToUser_still_saves_to_db_even_when_fcm_fails(): void
    {
        $user = $this->makeUser();
        UserDevice::create(['user_id' => $user->id, 'fcm_token' => 'bad-token']);

        $mockMessaging = $this->createMock(\Kreait\Firebase\Contract\Messaging::class);
        $mockMessaging->method('send')->willThrowException(new \RuntimeException('FCM error'));

        $this->app->bind('firebase.messaging', fn () => $mockMessaging);

        Log::shouldReceive('warning')->once();

        $result = $this->service->sendToUser($user->id, $this->samplePayload());

        $this->assertNotNull($result->id);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id]);
    }

    #[Test]
    public function sendToUser_does_not_send_fcm_when_user_has_no_devices(): void
    {
        $user = $this->makeUser();

        $mockMessaging = $this->createMock(\Kreait\Firebase\Contract\Messaging::class);
        $mockMessaging->expects($this->never())->method('send');

        $this->app->bind('firebase.messaging', fn () => $mockMessaging);

        $result = $this->service->sendToUser($user->id, $this->samplePayload());

        $this->assertNotNull($result->id);
    }

    // -------------------------------------------------------------------------
    // G2.2 — markRead hanya mengubah notifikasi milik user yang benar
    // -------------------------------------------------------------------------

    #[Test]
    public function markRead_sets_is_read_true_for_owner(): void
    {
        $owner = $this->makeUser();
        $notif = Notification::create([
            'user_id' => $owner->id,
            'title'   => 'Test',
            'message' => 'Msg',
            'type'    => 'info',
            'is_read' => false,
        ]);

        $this->actingAs($owner, 'api')
            ->patchJson("/api/notifications/{$notif->id}/read")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'id'      => $notif->id,
            'is_read' => true,
        ]);
    }

    #[Test]
    public function markRead_returns_404_when_other_user_tries_to_mark(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();

        $notif = Notification::create([
            'user_id' => $owner->id,
            'title'   => 'Test',
            'message' => 'Msg',
            'type'    => 'info',
            'is_read' => false,
        ]);

        $this->actingAs($other, 'api')
            ->patchJson("/api/notifications/{$notif->id}/read")
            ->assertNotFound();

        // Notification must remain unread
        $this->assertDatabaseHas('notifications', [
            'id'      => $notif->id,
            'is_read' => false,
        ]);
    }

    #[Test]
    public function markRead_does_not_affect_other_notifications_of_same_user(): void
    {
        $user = $this->makeUser();

        $target = Notification::create(['user_id' => $user->id, 'title' => 'A', 'message' => 'M', 'type' => 'info', 'is_read' => false]);
        $other  = Notification::create(['user_id' => $user->id, 'title' => 'B', 'message' => 'M', 'type' => 'info', 'is_read' => false]);

        $this->actingAs($user, 'api')
            ->patchJson("/api/notifications/{$target->id}/read")
            ->assertOk();

        $this->assertDatabaseHas('notifications', ['id' => $target->id, 'is_read' => true]);
        $this->assertDatabaseHas('notifications', ['id' => $other->id,  'is_read' => false]);
    }

    // -------------------------------------------------------------------------
    // G2.3 — DeviceController::register melakukan upsert (tidak duplikat token)
    // -------------------------------------------------------------------------

    #[Test]
    public function register_creates_new_device_record(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'api')
            ->postJson('/api/devices/register', ['fcm_token' => 'unique-token-abc'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_devices', [
            'user_id'   => $user->id,
            'fcm_token' => 'unique-token-abc',
        ]);
    }

    #[Test]
    public function register_does_not_create_duplicate_when_same_token_registered_twice(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'api')
            ->postJson('/api/devices/register', ['fcm_token' => 'same-token']);

        $this->actingAs($user, 'api')
            ->postJson('/api/devices/register', ['fcm_token' => 'same-token'])
            ->assertOk();

        $this->assertSame(1, \App\Models\UserDevice::where('fcm_token', 'same-token')->count());
    }

    #[Test]
    public function register_reassigns_token_to_new_user_on_upsert(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();

        // userA registers the token first
        $this->actingAs($userA, 'api')
            ->postJson('/api/devices/register', ['fcm_token' => 'shared-token']);

        // userB registers the same token (e.g. new login on same device)
        $this->actingAs($userB, 'api')
            ->postJson('/api/devices/register', ['fcm_token' => 'shared-token'])
            ->assertOk();

        // Only one record should exist, now owned by userB
        $this->assertSame(1, \App\Models\UserDevice::where('fcm_token', 'shared-token')->count());
        $this->assertDatabaseHas('user_devices', [
            'fcm_token' => 'shared-token',
            'user_id'   => $userB->id,
        ]);
    }

    #[Test]
    public function register_stores_device_info_when_provided(): void
    {
        $user = $this->makeUser();
        $info = ['browser' => 'Chrome', 'os' => 'Linux'];

        $this->actingAs($user, 'api')
            ->postJson('/api/devices/register', ['fcm_token' => 'token-with-info', 'device_info' => $info])
            ->assertOk();

        $device = \App\Models\UserDevice::where('fcm_token', 'token-with-info')->first();
        $this->assertNotNull($device);
        $this->assertEquals($info, $device->device_info);
    }
}
