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

            $pergunta = TriagemPergunta::firstOrCreate(
                ['pergunta' => $dados['pergunta'], 'bloco' => $dados['bloco']],
                $dados
            );

            foreach ($opcoes as $opcao) {
                TriagemOpcao::firstOrCreate(
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
                'pergunta' => 'Você está se sentindo bem hoje, sem sintomas de gripe, febre ou mal-estar?',
                'bloco' => 0,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim, estou me sentindo bem', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Não, estou com sintomas', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'Você dormiu pelo menos 6 horas nas últimas 24 horas?',
                'bloco' => 0,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'Você ingeriu bebida alcoólica nas últimas 12 horas?',
                'bloco' => 0,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'Você está em jejum?',
                'bloco' => 0,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não, me alimentei normalmente', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim, estou em jejum', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'Você pesa pelo menos 50 kg?',
                'bloco' => 0,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim, peso 50 kg ou mais', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Não, peso menos de 50 kg', 'gera_inaptidao' => true, 'dias_inaptidao' => null],
                ],
            ],
            [
                'pergunta' => 'Você fez tatuagem ou piercing nos últimos 6 meses?',
                'bloco' => 0,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                ],
            ],
            [
                'pergunta' => 'Você está tomando algum medicamento no momento?',
                'bloco' => 0,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não estou tomando nenhum medicamento', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim, estou tomando antibiótico', 'gera_inaptidao' => true, 'dias_inaptidao' => 15],
                    ['texto_opcao' => 'Sim, estou tomando outro medicamento', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'O doador está se sentindo bem no momento da triagem?',
                'bloco' => 1,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'O doador dormiu pelo menos 6 horas nas últimas 24 horas?',
                'bloco' => 1,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador ingeriu bebida alcoólica nas últimas 12 horas?',
                'bloco' => 1,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador está em jejum?',
                'bloco' => 1,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não, se alimentou normalmente', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim, está em jejum', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador fumou nas últimas 2 horas?',
                'bloco' => 1,
                'obrigatoria' => false,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 1],
                ],
            ],
            [
                'pergunta' => 'O doador teve febre, gripe ou infecção nos últimos 7 dias?',
                'bloco' => 3,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 14],
                ],
            ],
            [
                'pergunta' => 'O doador está tomando algum medicamento?',
                'bloco' => 3,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim - antibiótico', 'gera_inaptidao' => true, 'dias_inaptidao' => 15],
                    ['texto_opcao' => 'Sim - anticoagulante', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                    ['texto_opcao' => 'Sim - outro', 'gera_inaptidao' => true, 'dias_inaptidao' => 7],
                ],
            ],
            [
                'pergunta' => 'O doador fez algum procedimento cirúrgico nos últimos 6 meses?',
                'bloco' => 3,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                ],
            ],
            [
                'pergunta' => 'O doador recebeu alguma vacina nos últimos 30 dias?',
                'bloco' => 3,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim - vacina de vírus atenuado', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                    ['texto_opcao' => 'Sim - outra vacina', 'gera_inaptidao' => true, 'dias_inaptidao' => 48],
                ],
            ],
            [
                'pergunta' => 'O doador teve dengue ou outra arbovirose recentemente?',
                'bloco' => 3,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim - dengue clássica', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                    ['texto_opcao' => 'Sim - dengue hemorrágica', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                    ['texto_opcao' => 'Sim - outra arbovirose', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                ],
            ],
            [
                'pergunta' => 'O doador recebeu transfusão de sangue ou transplante nos últimos 12 meses?',
                'bloco' => 3,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 365],
                ],
            ],
            [
                'pergunta' => 'O doador fez tatuagem ou piercing nos últimos 6 meses?',
                'bloco' => 4,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 180],
                ],
            ],
            [
                'pergunta' => 'O doador esteve em área de risco de malária nos últimos 30 dias?',
                'bloco' => 4,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 30],
                ],
            ],
            [
                'pergunta' => 'O doador teve contato com material biológico (sangue, mucosa) nos últimos 12 meses?',
                'bloco' => 4,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 365],
                ],
            ],
            [
                'pergunta' => 'O doador apresenta situação de risco acrescido para infecções transmissíveis pelo sangue?',
                'bloco' => 4,
                'obrigatoria' => true,
                'opcoes' => [
                    ['texto_opcao' => 'Não', 'gera_inaptidao' => false, 'dias_inaptidao' => null],
                    ['texto_opcao' => 'Sim', 'gera_inaptidao' => true, 'dias_inaptidao' => 365],
                ],
            ],
        ];
    }
}
