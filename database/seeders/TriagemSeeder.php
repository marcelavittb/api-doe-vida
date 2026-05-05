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
        $doadores = User::where('role_id', 1)->get();
        $funcionarios = User::where('role_id', 2)->get();
        $hemocentros = Hemocentro::all();

        if ($doadores->isEmpty() || $funcionarios->isEmpty()) return;

        // Criar 10 triagens
        for ($i = 0; $i < 10; $i++) {
            $apto = (rand(0, 10) > 2); // 80% aptos
            Triagem::create([
                'user_id'        => $doadores->random()->id,
                'funcionario_id' => $funcionarios->random()->id,
                'hemocentro_id'  => $hemocentros->random()->id,
                'data_triagem'   => Carbon::now()->subDays(rand(1, 60)),
                'status_triagem' => 'C',
                'apto'           => $apto,
                'motivo_inaptidao' => $apto ? null : 'Anemia leve detectada no teste rápido.',
                'observacoes'    => 'Doador cooperativo.',
            ]);
        }

        $this->command->info('10 Triagens variadas criadas!');
    }
}
