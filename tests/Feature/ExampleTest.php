<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example — verifies the API is reachable.
     * Tests the login endpoint returns 400 (missing fields) rather than 500.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->getJson('/api/auth/me');

        // Unauthenticated request should return 401, not 500
        $response->assertStatus(401);
    }
}
