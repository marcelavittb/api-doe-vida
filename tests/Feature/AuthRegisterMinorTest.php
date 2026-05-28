<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthRegisterMinorTest extends TestCase
{
    use RefreshDatabase;

    public function test_minor_with_guardian_can_register(): void
    {
        Role::create([
            'name' => 'doador',
            'guard_name' => 'api',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Doador Menor',
            'email' => 'menor@example.com',
            'password' => 'Senha123',
            'password_confirmation' => 'Senha123',
            'cpf' => '52998224725',
            'telefone' => '41999998888',
            'sexo' => 'M',
            'data_nasc' => now()->subYears(17)->format('d/m/Y'),
            'cep' => '80000-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'cidade' => 'Curitiba',
            'uf' => 'PR',
            'responsavel_nome' => 'Responsavel Legal',
            'responsavel_cpf' => '11144477735',
            'responsavel_data_nasc' => now()->subYears(40)->format('d/m/Y'),
            'responsavel_telefone' => '41988887777',
            'lgpd_aceite' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'menor@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'menor@example.com',
            'responsavel_nome' => 'Responsavel Legal',
            'responsavel_cpf' => '11144477735',
            'responsavel_telefone' => '41988887777',
        ]);
    }

    public function test_user_younger_than_sixteen_cannot_register_even_with_guardian(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Doador Muito Novo',
            'email' => 'jovem@example.com',
            'password' => 'Senha123',
            'password_confirmation' => 'Senha123',
            'cpf' => '52998224725',
            'telefone' => '41999998888',
            'sexo' => 'M',
            'data_nasc' => now()->subYears(15)->format('d/m/Y'),
            'cep' => '80000-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'cidade' => 'Curitiba',
            'uf' => 'PR',
            'responsavel_nome' => 'Responsavel Legal',
            'responsavel_cpf' => '11144477735',
            'responsavel_data_nasc' => now()->subYears(40)->format('d/m/Y'),
            'responsavel_telefone' => '41988887777',
            'lgpd_aceite' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    public function test_guardian_older_than_one_hundred_is_rejected(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Doador Menor',
            'email' => 'guardiao-antigo@example.com',
            'password' => 'Senha123',
            'password_confirmation' => 'Senha123',
            'cpf' => '52998224725',
            'telefone' => '41999998888',
            'sexo' => 'M',
            'data_nasc' => now()->subYears(17)->format('d/m/Y'),
            'cep' => '80000-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'cidade' => 'Curitiba',
            'uf' => 'PR',
            'responsavel_nome' => 'Responsavel Antigo',
            'responsavel_cpf' => '11144477735',
            'responsavel_data_nasc' => now()->subYears(101)->format('d/m/Y'),
            'responsavel_telefone' => '41988887777',
            'lgpd_aceite' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error']);
    }
}
