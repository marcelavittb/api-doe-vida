<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Triagem;
use App\Models\User;
use App\Models\Hemocentro;
use Carbon\Carbon;

class TriagemSeeder extends Seeder
{
    public function run(): void
    {
        // Removido truncate() para evitar erros de FK no Postgres.
        // Se quiser limpar a tabela, prefira Triagem::query()->delete() ou rode migrations frescas.
        
        $doadores = User::where('role_id', 1)->get();
        $funcionarios = User::where('role_id', 2)->get();
        $hemocentros = Hemocentro::all();

        if ($doadores->isEmpty() || $funcionarios->isEmpty()) return;

        // Criar 150 triagens para volume de dados
        for ($i = 0; $i < 150; $i++) {
            $apto = (rand(0, 10) > 2); // 80% aptos
            Triagem::create([
                'user_id'        => $doadores->random()->id,
                'funcionario_id' => $funcionarios->random()->id,
                'hemocentro_id'  => $hemocentros->random()->id,
                'data_triagem'   => Carbon::now()->subDays(rand(1, 365)),
                'status_triagem' => 'C',
                'apto'           => $apto,
                'motivo_inaptidao' => $apto ? null : 'Inaptidão temporária detectada.',
                'observacoes'    => 'Triagem realizada para fins de teste.',
            ]);
        }

        $this->command->info('150 Triagens variadas criadas!');
    }
}
