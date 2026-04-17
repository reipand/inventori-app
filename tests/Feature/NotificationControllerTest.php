<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'pengelola',
            'is_active' => true,
            'must_change_password' => false,
        ], $overrides));
    }

    private function makeNotification(string $userId, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $userId,
            'title'   => 'Test Notification',
            'message' => 'Test message',
            'type'    => 'info',
            'link'    => null,
            'is_read' => false,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // G2.2 — markRead hanya mengubah notifikasi milik user yang benar
    // -------------------------------------------------------------------------

    #[Test]
    public function markRead_marks_own_notification_as_read(): void
    {
        $user = $this->makeUser();
        $token = auth('api')->login($user);
        $notif = $this->makeNotification($user->id);

        $response = $this->withToken($token)
            ->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'id'      => $notif->id,
            'is_read' => true,
        ]);
    }

    #[Test]
    public function markRead_returns_404_when_notification_belongs_to_another_user(): void
    {
        $owner = $this->makeUser();
        $attacker = $this->makeUser();
        $attackerToken = auth('api')->login($attacker);

        // Notification belongs to $owner, not $attacker
        $notif = $this->makeNotification($owner->id);

        $response = $this->withToken($attackerToken)
            ->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(404);

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
        $token = auth('api')->login($user);

        $target = $this->makeNotification($user->id);
        $other  = $this->makeNotification($user->id);

        $this->withToken($token)->patchJson("/api/notifications/{$target->id}/read");

        $this->assertDatabaseHas('notifications', ['id' => $target->id, 'is_read' => true]);
        $this->assertDatabaseHas('notifications', ['id' => $other->id,  'is_read' => false]);
    }

    #[Test]
    public function markRead_returns_401_without_token(): void
    {
        $user = $this->makeUser();
        $notif = $this->makeNotification($user->id);

        $response = $this->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(401);
    }

    #[Test]
    public function markRead_returns_404_for_nonexistent_notification(): void
    {
        $user = $this->makeUser();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->patchJson('/api/notifications/99999/read');

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // markAllRead — sanity checks
    // -------------------------------------------------------------------------

    #[Test]
    public function markAllRead_only_marks_notifications_of_authenticated_user(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $tokenA = auth('api')->login($userA);

        $notifA = $this->makeNotification($userA->id);
        $notifB = $this->makeNotification($userB->id);

        $this->withToken($tokenA)->patchJson('/api/notifications/read-all')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', ['id' => $notifA->id, 'is_read' => true]);
        // userB's notification must remain untouched
        $this->assertDatabaseHas('notifications', ['id' => $notifB->id, 'is_read' => false]);
    }

    // -------------------------------------------------------------------------
    // GET /api/notifications — only returns own notifications
    // -------------------------------------------------------------------------

    #[Test]
    public function index_returns_only_authenticated_users_notifications(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $tokenA = auth('api')->login($userA);

        $this->makeNotification($userA->id, ['title' => 'For A']);
        $this->makeNotification($userB->id, ['title' => 'For B']);

        $response = $this->withToken($tokenA)->getJson('/api/notifications');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertContains('For A', $titles);
        $this->assertNotContains('For B', $titles);
    }
}
