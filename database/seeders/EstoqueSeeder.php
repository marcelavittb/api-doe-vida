<?php

namespace Database\Seeders;

use App\Models\Estoque;
use App\Models\Hemocentro;
use Illuminate\Database\Seeder;

class EstoqueSeeder extends Seeder
{
    public function run(): void
    {
        $hemocentros = Hemocentro::all();
        $tiposSanguineos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        if ($hemocentros->isEmpty()) {
            return;
        }

        foreach ($hemocentros as $hemocentro) {
            foreach ($tiposSanguineos as $tipoSangue) {
                $quantidadeBolsas = rand(3, 24);

                Estoque::updateOrCreate(
                    [
                        'hemocentro_id' => $hemocentro->id,
                        'tipo_sangue' => $tipoSangue,
                    ],
                    [
                        'quantidade' => $quantidadeBolsas * 450,
                        'quantidade_minima' => 5000,
                    ]
                );
            }
        }

        $this->command->info('Estoques por hemocentro e tipo sanguineo criados!');
    }
}
