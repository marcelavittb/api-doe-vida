<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agendamento;
use App\Models\User;
use App\Models\Hemocentro;
use Carbon\Carbon;

class AgendamentoSeeder extends Seeder
{
    public function run(): void
    {
        $doadores = User::where('role_id', 1)->get();
        $hemocentros = Hemocentro::all();
        $status = ['AGE', 'CON', 'CAN', 'EXC'];

        if ($doadores->isEmpty() || $hemocentros->isEmpty()) return;

        // Criar 12 agendamentos variados
        for ($i = 0; $i < 12; $i++) {
            $data = ($i % 2 == 0) 
                ? Carbon::now()->addDays(rand(1, 15)) // Futuros
                : Carbon::now()->subDays(rand(1, 30)); // Passados

            Agendamento::create([
                'user_id'            => $doadores->random()->id,
                'hemocentro_id'      => $hemocentros->random()->id,
                'data_hora_doacao'   => $data,
                'status_agendamento' => $status[array_rand($status)],
                'coletador_id'       => ($i % 3 == 0) ? User::where('role_id', 2)->first()->id : null,
            ]);
        }

        $this->command->info('12 Agendamentos variados criados!');
    }
}
