<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\BuildsContent;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use BuildsContent, RefreshDatabase;

    public function test_register_creates_student_with_free_enrollment_and_ignores_role_input(): void
    {
        $this->plan('free');

        $res = $this->postJson('/api/auth/register', [
            'name' => 'Budi', 'email' => 'budi@example.com', 'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123',
            'role' => 'admin',
        ])->assertCreated();

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertEquals('student', $user->role);
        $this->assertEquals(1, $user->enrollments()->count());
        $this->assertNotEmpty($res->json('token'));
        $this->assertEquals(0, $res->json('user.access_level'));
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Budi', 'email' => 'b@example.com', 'password' => 'abc', 'password_confirmation' => 'abc',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_login_success_and_failure(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'Password123'])
            ->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'role']]);
        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'salah'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'salah'])->assertStatus(422);
        }
        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_logout_revokes_the_token(): void
    {
        User::factory()->create(['email' => 'a@example.com']);
        $token = $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'Password123'])->json('token');
        $this->assertEquals(1, PersonalAccessToken::count());

        $this->withHeader('Authorization', "Bearer $token")->postJson('/api/auth/logout')->assertOk();
        $this->assertEquals(0, PersonalAccessToken::count());
    }

    public function test_password_reset_flow(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);
        $this->postJson('/api/auth/forgot-password', ['email' => 'a@example.com'])->assertOk();
        // Respons sama untuk email yang tidak terdaftar (tidak membocorkan akun).
        $this->postJson('/api/auth/forgot-password', ['email' => 'tidak-ada@example.com'])->assertOk();

        $token = Password::createToken($user);
        $this->postJson('/api/auth/reset-password', [
            'token' => $token, 'email' => 'a@example.com', 'password' => 'BaruBaru123', 'password_confirmation' => 'BaruBaru123',
        ])->assertOk();

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'BaruBaru123'])->assertOk();
    }

    public function test_student_cannot_reach_admin_routes_and_guest_gets_401(): void
    {
        $this->getJson('/api/admin/stats')->assertUnauthorized();
        $this->as($this->student())->getJson('/api/admin/stats')->assertForbidden();
        $this->as($this->student())->postJson('/api/admin/topics', ['title' => 'x', 'type' => 'theory'])->assertForbidden();
        $this->as($this->admin())->getJson('/api/admin/stats')->assertOk();
    }
}
