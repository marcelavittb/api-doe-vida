<?php

namespace Tests\Feature;

use App\Models\Doacao;
use App\Models\Hemocentro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoacaoShowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_funcionario_cannot_view_doacao_from_another_hemocentro(): void
    {
        $roleFuncionario = Role::firstOrCreate([
            'name' => 'funcionario',
            'guard_name' => 'api',
        ]);

        $hemocentroDoFuncionario = Hemocentro::create([
            'nome' => 'Hemocentro A',
            'status' => true,
        ]);

        $outroHemocentro = Hemocentro::create([
            'nome' => 'Hemocentro B',
            'status' => true,
        ]);

        $funcionario = User::factory()->create([
            'role_id' => $roleFuncionario->id,
            'hemocentro_id' => $hemocentroDoFuncionario->id,
        ]);
        $funcionario->assignRole($roleFuncionario);

        $doador = User::factory()->create();

        $doacao = Doacao::create([
            'user_id' => $doador->id,
            'hemocentro_id' => $outroHemocentro->id,
            'funcionario_id' => $funcionario->id,
            'data_hora_doacao' => now(),
            'tipo_sangue' => 'O+',
            'quantidade' => 450,
        ]);

        Sanctum::actingAs($funcionario);

        $response = $this->getJson("/api/doacoes/{$doacao->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Acesso negado.');
    }
}
