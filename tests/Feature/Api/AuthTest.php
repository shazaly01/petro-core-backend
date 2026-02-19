<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // تشغيل الـ Seeder لإنشاء الأدوار والصلاحيات
        $this->seed(PermissionSeeder::class);
    }

    #[Test]
    public function a_user_can_login_with_correct_credentials(): void
    {
        // Arrange: إنشاء مستخدم عادي
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->assignRole('Admin');

        // Act
        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('user.username', $user->username);
        $response->assertJsonPath('user.roles.0.name', 'Admin');
    }

    #[Test]
    public function super_admin_login_returns_all_permissions(): void
    {
        // Arrange: إنشاء مستخدم Super Admin
        $superAdmin = User::factory()->create(['password' => bcrypt('password')]);
        $superAdmin->assignRole('Super Admin');

        // Act
        $response = $this->postJson('/api/login', [
            'username' => $superAdmin->username,
            'password' => 'password',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('user.username', $superAdmin->username);
        // التأكد من أن الرد يحتوي على كل الصلاحيات (بسبب المنطق الخاص في AuthController)
        $this->assertCount(
            \Spatie\Permission\Models\Permission::count(),
            $response->json('user.roles.0.permissions')
        );
    }

    #[Test]
    public function a_user_cannot_login_with_incorrect_password(): void
    {
        // Arrange
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $credentials = [
            'username' => $user->username,
            'password' => 'wrong-password',
        ];

        // Act
        $response = $this->postJson('/api/login', $credentials);

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function login_requires_a_username_and_password(): void
    {
        $this->postJson('/api/login', ['username' => 'test'])->assertJsonValidationErrors('password');
        $this->postJson('/api/login', ['password' => 'test'])->assertJsonValidationErrors('username');
    }

    #[Test]
    public function a_logged_in_user_can_logout(): void
    {
        // Arrange
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $loginResponse = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);
        $token = $loginResponse->json('access_token');

        // Act
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->postJson('/api/logout');

        // Assert
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }
}
