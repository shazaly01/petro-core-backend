<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminUser;
    protected User $basicUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');

        $this->basicUser = User::factory()->create();
        $this->basicUser->assignRole('User');
    }

    #[Test]
    public function unauthenticated_users_cannot_access_user_endpoints(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
        $this->postJson('/api/users')->assertUnauthorized();
        $this->getJson('/api/users/1')->assertUnauthorized();
        $this->putJson('/api/users/1')->assertUnauthorized();
        $this->deleteJson('/api/users/1')->assertUnauthorized();
    }

    #[Test]
    public function a_user_with_limited_permissions_cannot_manage_users(): void
    {
        Sanctum::actingAs($this->basicUser);
        $userToManage = User::factory()->create();

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [])->assertForbidden();
        $this->getJson("/api/users/{$userToManage->id}")->assertForbidden();
        $this->putJson("/api/users/{$userToManage->id}", [])->assertForbidden();
        $this->deleteJson("/api/users/{$userToManage->id}")->assertForbidden();
    }

    #[Test]
    public function an_authorized_user_can_view_users_list(): void
    {
        Sanctum::actingAs($this->adminUser); // Admin يملك صلاحية user.view

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3, 'data'); // superAdmin, adminUser, basicUser
    }

    #[Test]
    public function an_authorized_user_can_create_a_user(): void
    {
        Sanctum::actingAs($this->adminUser); // Admin يملك صلاحية user.create

        $userData = [
            'full_name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['User'],
        ];

        $this->postJson('/api/users', $userData)
             ->assertStatus(201)
             ->assertJsonFragment(['username' => 'testuser']);

        $this->assertDatabaseHas('users', ['username' => 'testuser']);
        $this->assertTrue(User::where('username', 'testuser')->first()->hasRole('User'));
    }

    #[Test]
    public function an_authorized_user_can_update_a_user(): void
    {
        Sanctum::actingAs($this->adminUser); // Admin يملك صلاحية user.update
        $userToUpdate = User::factory()->create();
        $userToUpdate->assignRole('User');

        $updatedData = [
            'full_name' => 'Updated Name',
            'username' => $userToUpdate->username,
            'email' => $userToUpdate->email,
            'roles' => ['Admin'], // ترقية الدور
        ];

        $this->putJson("/api/users/{$userToUpdate->id}", $updatedData)
             ->assertOk()
             ->assertJsonPath('data.roles.0.name', 'Admin');

        $this->assertDatabaseHas('users', ['full_name' => 'Updated Name']);
        $this->assertTrue($userToUpdate->fresh()->hasRole('Admin'));
    }

    #[Test]
    public function a_user_cannot_delete_themselves(): void
    {
        Sanctum::actingAs($this->superAdmin);
        $this->deleteJson("/api/users/{$this->superAdmin->id}")->assertForbidden();
    }

    #[Test]
    public function a_user_cannot_delete_the_super_admin_user(): void
    {
        // إنشاء admin آخر لمحاولة حذف الـ Super Admin
        $anotherAdmin = User::factory()->create();
        $anotherAdmin->assignRole('Admin');
        $anotherAdmin->givePermissionTo('user.delete'); // إعطاء الصلاحية بشكل صريح للاختبار

        Sanctum::actingAs($anotherAdmin);

        $this->deleteJson("/api/users/{$this->superAdmin->id}")->assertForbidden();
    }

    #[Test]
    public function an_admin_without_delete_permission_cannot_delete_a_user(): void
    {
        Sanctum::actingAs($this->adminUser); // adminUser لا يملك صلاحية user.delete
        $userToDelete = User::factory()->create();

        $this->deleteJson("/api/users/{$userToDelete->id}")->assertForbidden();
    }

    #[Test]
    public function a_super_admin_can_delete_a_user(): void
    {
        Sanctum::actingAs($this->superAdmin);
        $userToDelete = User::factory()->create();

        $this->deleteJson("/api/users/{$userToDelete->id}")->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $userToDelete->id]);
    }
}
