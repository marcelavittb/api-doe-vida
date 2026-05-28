<?php

namespace Tests\Feature;

use App\Models\Campanha;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_keeps_campaign_access_without_explicit_permissions(): void
    {
        $admin = $this->userWithRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/auth/campanhas', [
            'titulo' => 'Campanha Admin',
            'data_publi' => now()->toDateTimeString(),
            'data_expiracao' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.titulo', 'Campanha Admin');
    }

    public function test_custom_role_with_campaign_permission_can_manage_campaigns(): void
    {
        $role = Role::create(['name' => 'marketing_campanhas', 'guard_name' => 'api']);
        $permission = Permission::create(['name' => 'gerenciar_campanhas', 'guard_name' => 'api']);
        $role->givePermissionTo($permission);
        $user = $this->userWithRole($role);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/campanhas', [
            'titulo' => 'Campanha Custom',
            'data_publi' => now()->toDateTimeString(),
            'data_expiracao' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.titulo', 'Campanha Custom');
    }

    public function test_custom_role_without_campaign_permission_is_forbidden(): void
    {
        $role = Role::create(['name' => 'recepcao', 'guard_name' => 'api']);
        $user = $this->userWithRole($role);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/campanhas', [
            'titulo' => 'Campanha Bloqueada',
            'data_publi' => now()->toDateTimeString(),
        ]);

        $response->assertForbidden();
    }

    public function test_disparar_campaign_permission_does_not_allow_campaign_crud(): void
    {
        $role = Role::create(['name' => 'disparo_campanhas', 'guard_name' => 'api']);
        $permission = Permission::create(['name' => 'disparar_campanhas', 'guard_name' => 'api']);
        $role->givePermissionTo($permission);
        $user = $this->userWithRole($role);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/campanhas', [
            'titulo' => 'Campanha Sem Gerencia',
            'data_publi' => now()->toDateTimeString(),
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_create_user_with_custom_role(): void
    {
        $admin = $this->userWithRole('admin');
        $role = Role::create(['name' => 'recepcionista', 'guard_name' => 'api']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/auth/users', [
            'name' => 'Usuario Custom',
            'email' => 'custom@example.com',
            'password' => 'password',
            'cpf' => '52998224725',
            'role_id' => $role->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('email', 'custom@example.com')
            ->assertJsonPath('role_id', $role->id);

        $this->assertTrue(User::where('email', 'custom@example.com')->first()->hasRole('recepcionista'));
    }

    public function test_system_roles_are_locked_in_role_management(): void
    {
        $admin = $this->userWithRole('admin');
        $enfermeiro = Role::create(['name' => 'enfermeiro', 'guard_name' => 'api']);

        Sanctum::actingAs($admin);

        $updateResponse = $this->putJson("/api/auth/roles/{$enfermeiro->id}", [
            'name' => 'enfermeiro_chefe',
            'permissions' => ['gerenciar_campanhas'],
        ]);

        $deleteResponse = $this->deleteJson("/api/auth/roles/{$enfermeiro->id}");

        $updateResponse->assertStatus(422);
        $deleteResponse->assertStatus(422);
    }

    private function userWithRole(string|Role $role): User
    {
        $role = is_string($role)
            ? Role::firstOrCreate(['name' => $role, 'guard_name' => 'api'])
            : $role;

        $user = User::factory()->create(['role_id' => $role->id]);
        $user->assignRole($role);

        return $user;
    }
}
