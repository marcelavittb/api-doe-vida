<?php

namespace Database\Seeders;

use App\Models\TriagemOpcao;
use App\Models\TriagemPergunta;
use Illuminate\Database\Seeder;

class TriagemPerguntaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::perguntasPadrao() as $dados) {
            $opcoes = $dados['opcoes'];
            unset($dados['opcoes']);

            $pergunta = TriagemPergunta::updateOrCreate(
                ['pergunta' => $dados['pergunta'], 'bloco' => $dados['bloco']],
                $dados
            );

            foreach ($opcoes as $opcao) {
                TriagemOpcao::updateOrCreate(
                    [
                        'pergunta_id' => $pergunta->id,
                        'texto_opcao' => $opcao['texto_opcao'],
                    ],
                    $opcao
                );
            }
        }

        if (!$this->command) {
            return;
        }

        $this->command->info('Perguntas e opcoes de triagem criadas com sucesso!');
        $this->command->info('Bloco 0 (pre-triagem): ' . TriagemPergunta::where('bloco', 0)->count() . ' perguntas');
        $this->command->info('Bloco 1 (estado geral): ' . TriagemPergunta::where('bloco', 1)->count() . ' perguntas');
        $this->command->info('Bloco 3 (historico recente): ' . TriagemPergunta::where('bloco', 3)->count() . ' perguntas');
        $this->command->info('Bloco 4 (comportamental): ' . TriagemPergunta::where('bloco', 4)->count() . ' perguntas');
    }

    public static function perguntasPadrao(): array
    {
        return [
            [
                'pergunta' => 'Voce esta se sentindo bem hoje, sem sintomas de gripe, febre ou mal-estar?',
                'bloco' => 0,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim, estou me sentindo bem', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Nao, estou com sintomas', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'Voce dormiu pelo menos 6 horas nas ultimas 24 horas?',
                'bloco' => 0,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'Voce ingeriu bebida alcoolica nas ultimas 12 horas?',
                'bloco' => 0,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'Voce esta em jejum?',
                'bloco' => 0,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao, me alimentei normalmente', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim, estou em jejum', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'Voce pesa pelo menos 50 kg?',
                'bloco' => 0,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim, peso 50 kg ou mais', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Nao, peso menos de 50 kg', 'gera_inaptidao' => true, 'dias_inaptidao' => null],
                ],
            ],
            [
                'pergunta' => 'Voce fez tatuagem ou piercing nos ultimos 6 meses?',
                'bloco' => 0,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                ],
            ],
            [
                'pergunta' => 'Voce esta tomando algum medicamento no momento?',
                'bloco' => 0,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao estou tomando nenhum medicamento', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim, estou tomando antibiotico', 'gera_inaptidao' => true, 'dias_inaptidao' => 15],
                    ['texto_opcao' => 'Sim, estou tomando outro medicamento', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'O doador esta se sentindo bem no momento da triagem?',
                'bloco' => 1,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'O doador dormiu pelo menos 6 horas nas ultimas 24 horas?',
                'bloco' => 1,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador ingeriu bebida alcoolica nas ultimas 12 horas?',
                'bloco' => 1,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador esta em jejum?',
                'bloco' => 1,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao, se alimentou normalmente', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim, esta em jejum', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador fumou nas ultimas 2 horas?',
                'bloco' => 1,
                'obrigatoria' => false,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador teve febre, gripe ou infeccao nos ultimos 7 dias?',
                'bloco' => 3,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 14],
                ],
            ],
            [
                'pergunta' => 'O doador esta tomando algum medicamento?',
                'bloco' => 3,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim - antibiotico', 'gera_inaptidao' => true, 'dias_inaptidao' => 15],
                    ['texto_opcao' => 'Sim - anticoagulante', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                    ['texto_opcao' => 'Sim - outro', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'O doador fez algum procedimento cirurgico nos ultimos 6 meses?',
                'bloco' => 3,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                ],
            ],
            [
                'pergunta' => 'O doador recebeu alguma vacina nos ultimos 30 dias?',
                'bloco' => 3,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim - vacina de virus atenuado', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                    ['texto_opcao' => 'Sim - outra vacina', 'gera_inaptidao' => true, 'dias_inaptidao' => 48],
                ],
            ],
            [
                'pergunta' => 'O doador teve dengue ou outra arbovirose recentemente?',
                'bloco' => 3,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim - dengue classica', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                    ['texto_opcao' => 'Sim - dengue hemorragica', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                    ['texto_opcao' => 'Sim - outra arbovirose', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                ],
            ],
            [
                'pergunta' => 'O doador recebeu transfusao de sangue ou transplante nos ultimos 12 meses?',
                'bloco' => 3,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 365],
                ],
            ],
            [
                'pergunta' => 'O doador fez tatuagem ou piercing nos ultimos 6 meses?',
                'bloco' => 4,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                ],
            ],
            [
                'pergunta' => 'O doador esteve em area de risco de malaria nos ultimos 30 dias?',
                'bloco' => 4,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                ],
            ],
            [
                'pergunta' => 'O doador teve contato com material biologico (sangue, mucosa) nos ultimos 12 meses?',
                'bloco' => 4,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 365],
                ],
            ],
            [
                'pergunta' => 'O doador apresenta situacao de risco acrescido para infeccoes transmissiveis pelo sangue?',
                'bloco' => 4,
                'obrigatoria' => true,
                'status' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Nao', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 365],
                ],
            ],
        ];
    }
}
