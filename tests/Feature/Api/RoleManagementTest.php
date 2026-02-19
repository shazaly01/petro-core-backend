<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoleManagementTest extends TestCase
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
    public function unauthenticated_users_cannot_access_role_endpoints(): void
    {
        $this->getJson('/api/roles')->assertUnauthorized();
        $this->postJson('/api/roles')->assertUnauthorized();
        $this->getJson('/api/roles/1')->assertUnauthorized();
        $this->putJson('/api/roles/1')->assertUnauthorized();
        $this->deleteJson('/api/roles/1')->assertUnauthorized();
        $this->getJson('/api/roles/permissions')->assertUnauthorized();
    }

    #[Test]
    public function a_user_with_limited_permissions_cannot_manage_roles(): void
    {
        Sanctum::actingAs($this->basicUser);
        $role = Role::create(['name' => 'Test Role', 'guard_name' => 'api']);

        $this->getJson('/api/roles')->assertForbidden();
        $this->getJson('/api/roles/permissions')->assertForbidden();
        $this->postJson('/api/roles', ['name' => 'New Role', 'permissions' => []])->assertForbidden();
        $this->getJson("/api/roles/{$role->id}")->assertForbidden();
        $this->putJson("/api/roles/{$role->id}", ['name' => 'Updated Name', 'permissions' => []])->assertForbidden();
        $this->deleteJson("/api/roles/{$role->id}")->assertForbidden();
    }

    #[Test]
    public function an_authorized_user_can_view_roles_and_permissions_list(): void
    {
        Sanctum::actingAs($this->adminUser); // Admin يمتلك صلاحية role.view

        // يوجد 3 أدوار تم إنشاؤها في الـ Seeder + 1 في setUp = 4
        // Super Admin, Admin, User
        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonCount(3, 'data'); // Super Admin, Admin, User

        $this->getJson('/api/roles/permissions')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    #[Test]
    public function an_authorized_user_can_create_a_role(): void
    {
        Sanctum::actingAs($this->adminUser); // Admin يمتلك صلاحية role.create

        $permission = Permission::where('name', 'driver.view')->first();
        $roleData = [
            'name' => 'Dispatcher',
            'permissions' => [$permission->name],
        ];

        $response = $this->postJson('/api/roles', $roleData);

        $response->assertStatus(201) // 201 Created
                 ->assertJsonFragment(['name' => 'Dispatcher']);

        $this->assertDatabaseHas('roles', ['name' => 'Dispatcher']);
        $this->assertTrue(Role::where('name', 'Dispatcher')->first()->hasPermissionTo('driver.view'));
    }

    #[Test]
    public function an_authorized_user_can_update_a_role(): void
    {
        Sanctum::actingAs($this->adminUser); // Admin يمتلك صلاحية role.update
        $role = Role::create(['name' => 'Old Name', 'guard_name' => 'api']);
        $permission = Permission::where('name', 'truck.view')->first();

        $updatedData = [
            'name' => 'New Name',
            'permissions' => [$permission->name],
        ];

        $this->putJson("/api/roles/{$role->id}", $updatedData)
             ->assertOk()
             ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('roles', ['name' => 'New Name']);
        $this->assertDatabaseMissing('roles', ['name' => 'Old Name']);
        $this->assertTrue(Role::where('name', 'New Name')->first()->hasPermissionTo('truck.view'));
    }

    #[Test]
    public function a_user_cannot_delete_default_roles(): void
    {
        Sanctum::actingAs($this->superAdmin); // حتى الـ Super Admin لا يمكنه حذفها

        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'User')->first();

        $this->deleteJson("/api/roles/{$superAdminRole->id}")->assertForbidden();
        $this->deleteJson("/api/roles/{$adminRole->id}")->assertForbidden();
        $this->deleteJson("/api/roles/{$userRole->id}")->assertForbidden();
    }

    #[Test]
    public function an_admin_user_cannot_delete_roles(): void
    {
        Sanctum::actingAs($this->adminUser); // Admin لا يملك صلاحية role.delete
        $role = Role::create(['name' => 'Deletable Role', 'guard_name' => 'api']);

        $this->deleteJson("/api/roles/{$role->id}")->assertForbidden();
    }

    #[Test]
    public function a_super_admin_can_delete_a_non_default_role(): void
    {
        Sanctum::actingAs($this->superAdmin);
        $role = Role::create(['name' => 'Temporary Role', 'guard_name' => 'api']);

        $this->deleteJson("/api/roles/{$role->id}")->assertNoContent(); // 204 No Content

        $this->assertDatabaseMissing('roles', ['name' => 'Temporary Role']);
    }
}
