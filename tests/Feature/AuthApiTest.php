<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_password_and_issues_a_usable_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '  Pessoa de teste  ', 'email' => '  LEITURA@example.test ',
            'password' => 'Password-test-123', 'password_confirmation' => 'Password-test-123',
        ])->assertCreated()->assertJsonPath('data.user.name', 'Pessoa de teste')
            ->assertJsonPath('data.user.email', 'leitura@example.test');
        $user = User::query()->sole();
        $this->assertTrue(Hash::check('Password-test-123', $user->password));
        $this->assertNotSame('Password-test-123', $user->password);
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
        $token = $response->json('data.token');
        $this->assertNotEmpty(PersonalAccessToken::findToken($token));
        $this->withToken($token)->getJson('/api/auth/me')->assertOk()
            ->assertJsonPath('data.id', (string) $user->id);
    }

    public function test_login_rejects_invalid_credentials_without_issuing_a_token(): void
    {
        User::factory()->create(['email' => 'person@example.test', 'password' => 'Correct-password-123']);
        $this->postJson('/api/auth/login', [
            'email' => 'person@example.test', 'password' => 'Wrong-password-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_and_logout_revoke_the_current_token(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test', 'password' => 'Correct-password-123']);
        $login = $this->postJson('/api/auth/login', [
            'email' => 'person@example.test', 'password' => 'Correct-password-123',
        ])->assertOk();
        $token = $login->json('data.token');
        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_protected_routes_reject_missing_and_expired_tokens(): void
    {
        $this->getJson('/api/books')->assertUnauthorized();
        $user = User::factory()->create();
        $token = $user->createToken('expired', ['*'], now()->subMinute())->plainTextToken;
        $this->withToken($token)->getJson('/api/books')->assertUnauthorized();
    }

    public function test_registration_validates_duplicate_email_and_password_confirmation(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);
        $this->postJson('/api/auth/register', [
            'name' => 'Teste', 'email' => 'taken@example.test',
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_repeated_invalid_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', [
                'email' => 'invalid@example.test', 'password' => 'Invalid-password-123',
            ])->assertUnprocessable();
        }
        $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.test', 'password' => 'Invalid-password-123',
        ])->assertTooManyRequests();
    }

    public function test_malformed_authentication_fields_return_validation_errors(): void
    {
        $this->postJson('/api/auth/login', ['email' => ['invalid'], 'password' => ['invalid']])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->postJson('/api/auth/register', ['name' => ['invalid'], 'email' => ['invalid'], 'password' => '12345678'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
    }
}
