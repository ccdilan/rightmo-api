<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_a_token_and_logout_revokes_it(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'correct-password'])->assertOk()->assertJsonPath('data.user.id', $user->id)->assertJsonMissingPath('data.user.password');
        $token = $response->json('data.token');
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_invalid_credentials_are_401_and_invalid_payload_is_422(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized()->assertJsonPath('message', 'The email or password is incorrect.');
        $this->postJson('/api/v1/auth/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('expired', ['*'], now()->subMinute())->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_all_catalog_endpoints_require_authentication(): void
    {
        foreach ([['GET', '/products'], ['GET', '/products/1'], ['POST', '/products'], ['PATCH', '/products/1'], ['PUT', '/products/1'], ['DELETE', '/products/1'], ['POST', '/products/1/image'], ['GET', '/categories'], ['GET', '/categories/1'], ['POST', '/categories'], ['PUT', '/categories/1'], ['PATCH', '/categories/1'], ['DELETE', '/categories/1'], ['GET', '/auth/me'], ['POST', '/auth/logout']] as [$method,$path]) {
            $this->json($method, '/api/v1'.$path)->assertUnauthorized()->assertJsonStructure(['message']);
        }
        $this->withToken('not-a-token')->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'throttle@example.test', 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/v1/auth/login', ['email' => 'throttle@example.test', 'password' => 'wrong'])->assertTooManyRequests()->assertHeader('Retry-After');
    }

    public function test_token_expires_after_eight_hours(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['password' => 'correct-password']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'correct-password'])->assertOk()->json('data.token');
        $this->travel(8)->hours();
        $this->travel(1)->seconds();
        $this->withToken($token)->getJson('/api/v1/products')->assertUnauthorized();
    }
}
