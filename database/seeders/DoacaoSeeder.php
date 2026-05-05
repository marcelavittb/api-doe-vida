<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doacao;
use App\Models\User;
use App\Models\Hemocentro;
use Carbon\Carbon;

class DoacaoSeeder extends Seeder
{
    public function run(): void
    {
        $doadores = User::where('role_id', 1)->get();
        $funcionarios = User::where('role_id', 2)->get();
        $hemocentros = Hemocentro::all();

        if ($doadores->isEmpty() || $hemocentros->isEmpty()) return;

        // Criar 10 Doações (Bolsas reais)
        for ($i = 0; $i < 10; $i++) {
            $doador = $doadores->random();
            $dataDoacao = Carbon::now()->subMonths(rand(1, 24));
            
            Doacao::create([
                'user_id'              => $doador->id,
                'hemocentro_id'        => $hemocentros->random()->id,
                'funcionario_id'       => $funcionarios->random()->id,
                'data_hora_doacao'     => $dataDoacao,
                'tipo_sangue'          => $doador->tipo_sang ?? 'O+',
                'quantidade'           => 450,
                'data_validade_sangue' => $dataDoacao->copy()->addDays(35),
            ]);
        }

        $this->command->info('10 Doações (bolsas) históricas criadas!');
    }
}
