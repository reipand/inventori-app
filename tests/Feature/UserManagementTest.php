<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePengelola(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'                 => 'pengelola',
            'is_active'            => true,
            'must_change_password' => false,
        ], $overrides));
    }

    private function makeKasir(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'                 => 'kasir',
            'is_active'            => true,
            'must_change_password' => false,
        ], $overrides));
    }

    private function tokenFor(User $user): string
    {
        return auth('api')->login($user);
    }

    // -------------------------------------------------------------------------
    // GET /api/users — index
    // -------------------------------------------------------------------------

    #[Test]
    public function pengelola_can_list_users(): void
    {
        $pengelola = $this->makePengelola();
        User::factory()->count(3)->create();

        $response = $this->withToken($this->tokenFor($pengelola))
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'data' => [['id', 'email', 'role', 'is_active', 'created_at']],
                ],
            ]);
    }

    #[Test]
    public function kasir_cannot_list_users(): void
    {
        $kasir = $this->makeKasir();

        $response = $this->withToken($this->tokenFor($kasir))
            ->getJson('/api/users');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error'   => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini',
                ],
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_list_users(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // POST /api/users — store
    // -------------------------------------------------------------------------

    #[Test]
    public function pengelola_can_create_kasir_account(): void
    {
        Mail::fake();

        $pengelola = $this->makePengelola();

        $response = $this->withToken($this->tokenFor($pengelola))
            ->postJson('/api/users', [
                'email' => 'newkasir@example.com',
                'role'  => 'kasir',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'email'                => 'newkasir@example.com',
                    'role'                 => 'kasir',
                    'is_active'            => true,
                    'must_change_password' => true,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email'                => 'newkasir@example.com',
            'role'                 => 'kasir',
            'must_change_password' => true,
        ]);
    }

    #[Test]
    public function store_sends_email_with_temp_password(): void
    {
        Mail::fake();

        $pengelola = $this->makePengelola();

        $this->withToken($this->tokenFor($pengelola))
            ->postJson('/api/users', [
                'email' => 'newuser@example.com',
                'role'  => 'pengelola',
            ])
            ->assertStatus(201);

        Mail::assertSent(\App\Mail\TempPasswordMail::class, function ($mail) {
            return $mail->hasTo('newuser@example.com');
        });
    }

    #[Test]
    public function store_returns_temp_password_in_response(): void
    {
        Mail::fake();

        $pengelola = $this->makePengelola();

        $response = $this->withToken($this->tokenFor($pengelola))
            ->postJson('/api/users', [
                'email' => 'newuser2@example.com',
                'role'  => 'kasir',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['temp_password']]);

        $tempPassword = $response->json('data.temp_password');
        $this->assertNotEmpty($tempPassword);
        $this->assertGreaterThanOrEqual(10, strlen($tempPassword));
    }

    #[Test]
    public function store_rejects_duplicate_email(): void
    {
        Mail::fake();

        $pengelola = $this->makePengelola();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->withToken($this->tokenFor($pengelola))
            ->postJson('/api/users', [
                'email' => 'existing@example.com',
                'role'  => 'kasir',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error'   => [
                    'code'    => 'CONFLICT',
                    'message' => 'Email sudah terdaftar',
                ],
            ]);
    }

    #[Test]
    public function store_rejects_invalid_role(): void
    {
        Mail::fake();

        $pengelola = $this->makePengelola();

        $response = $this->withToken($this->tokenFor($pengelola))
            ->postJson('/api/users', [
                'email' => 'someone@example.com',
                'role'  => 'admin',
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function store_rejects_missing_fields(): void
    {
        $pengelola = $this->makePengelola();

        $response = $this->withToken($this->tokenFor($pengelola))
            ->postJson('/api/users', []);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function kasir_cannot_create_user(): void
    {
        $kasir = $this->makeKasir();

        $response = $this->withToken($this->tokenFor($kasir))
            ->postJson('/api/users', [
                'email' => 'newuser@example.com',
                'role'  => 'kasir',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => ['message' => 'Anda tidak memiliki izin untuk mengakses halaman ini'],
            ]);
    }

    // -------------------------------------------------------------------------
    // PUT /api/users/:id/deactivate — deactivate
    // -------------------------------------------------------------------------

    #[Test]
    public function pengelola_can_deactivate_user(): void
    {
        $pengelola = $this->makePengelola();
        $target    = $this->makeKasir(['email' => 'target@example.com']);

        $response = $this->withToken($this->tokenFor($pengelola))
            ->putJson("/api/users/{$target->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => ['is_active' => false],
            ]);

        $this->assertFalse((bool) $target->fresh()->is_active);
    }

    #[Test]
    public function deactivated_user_cannot_access_protected_routes(): void
    {
        $pengelola   = $this->makePengelola();
        $target      = $this->makeKasir();
        $targetToken = $this->tokenFor($target);

        // Deactivate the target
        $this->withToken($this->tokenFor($pengelola))
            ->putJson("/api/users/{$target->id}/deactivate")
            ->assertStatus(200);

        // Target's token should now be rejected by check_active middleware
        $response = $this->withToken($targetToken)->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    #[Test]
    public function deactivated_user_cannot_login(): void
    {
        $pengelola = $this->makePengelola();
        $target    = User::factory()->create([
            'email'     => 'deactivated@example.com',
            'password'  => bcrypt('password123'),
            'role'      => 'kasir',
            'is_active' => true,
        ]);

        // Deactivate
        $this->withToken($this->tokenFor($pengelola))
            ->putJson("/api/users/{$target->id}/deactivate")
            ->assertStatus(200);

        // Attempt login
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'deactivated@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function pengelola_cannot_deactivate_themselves(): void
    {
        $pengelola = $this->makePengelola();

        $response = $this->withToken($this->tokenFor($pengelola))
            ->putJson("/api/users/{$pengelola->id}/deactivate");

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function deactivate_returns_404_for_nonexistent_user(): void
    {
        $pengelola = $this->makePengelola();

        $response = $this->withToken($this->tokenFor($pengelola))
            ->putJson('/api/users/nonexistent-id/deactivate');

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function kasir_cannot_deactivate_user(): void
    {
        $kasir  = $this->makeKasir();
        $target = $this->makeKasir(['email' => 'target2@example.com']);

        $response = $this->withToken($this->tokenFor($kasir))
            ->putJson("/api/users/{$target->id}/deactivate");

        $response->assertStatus(403);
    }
}
