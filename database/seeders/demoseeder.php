<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DemoSeeder extends Seeder
{
    private array $nomesMasculinos = [
        'Lucas', 'Gabriel', 'Matheus', 'Pedro', 'Gustavo', 'Rafael', 'Felipe', 'Thiago',
        'Bruno', 'Diego', 'Vinicius', 'Eduardo', 'Rodrigo', 'Henrique', 'Leonardo',
        'André', 'Carlos', 'Fábio', 'Marcelo', 'Paulo', 'Ricardo', 'Sérgio', 'Tiago',
        'Alessandro', 'Caio', 'Danilo', 'Erick', 'Fernando', 'Giovanni', 'Hugo',
        'Igor', 'Júlio', 'Kevin', 'Leandro', 'Murilo', 'Nathan', 'Otávio', 'Patrick',
        'Renan', 'Samuel', 'Túlio', 'Vítor', 'Wagner', 'Adalberto', 'Bernardo',
        'Cristiano', 'Davi', 'Elton', 'Francisco', 'Guilherme', 'Hélio', 'Jefferson',
        'Lauro', 'Márcio', 'Nelson', 'Orestes', 'Renato', 'Sidney', 'Valter', 'Zé',
    ];

    private array $nomesFemininos = [
        'Ana', 'Beatriz', 'Carolina', 'Daniela', 'Elena', 'Fernanda', 'Gabriela', 'Helena',
        'Isabella', 'Juliana', 'Karen', 'Larissa', 'Mariana', 'Natália', 'Olivia',
        'Patrícia', 'Renata', 'Sabrina', 'Tatiana', 'Vanessa', 'Aline', 'Bruna',
        'Camila', 'Débora', 'Eduarda', 'Flávia', 'Giovanna', 'Heloísa', 'Íris',
        'Joana', 'Kelly', 'Letícia', 'Mônica', 'Núbia', 'Priscila', 'Roberta',
        'Simone', 'Tânia', 'Vera', 'Yasmim', 'Alessandra', 'Bárbara', 'Cláudia',
        'Denise', 'Elisa', 'Fabiana', 'Graziela', 'Ingrid', 'Jéssica', 'Luana',
        'Milena', 'Noemi', 'Paula', 'Raquel', 'Solange', 'Thainá', 'Viviane', 'Zilda',
    ];

    private array $sobrenomes = [
        'Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves',
        'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro', 'Martins', 'Carvalho', 'Almeida',
        'Lopes', 'Sousa', 'Fernandes', 'Vieira', 'Barbosa', 'Rocha', 'Dias', 'Nascimento',
        'Andrade', 'Moreira', 'Nunes', 'Marques', 'Machado', 'Mendes', 'Freitas',
        'Cardoso', 'Ramos', 'Gonçalves', 'Correia', 'Tavares', 'Cruz', 'Campos',
        'Pinto', 'Teixeira', 'Azevedo', 'Araújo', 'Cunha', 'Faria', 'Castro',
        'Monteiro', 'Pires', 'Fonseca', 'Borges', 'Cavalcanti', 'Moura', 'Xavier',
        'Duarte', 'Brito', 'Guimarães', 'Batista', 'Miranda', 'Coelho', 'Melo',
        'Rezende', 'Vasconcelos', 'Nogueira', 'Braga', 'Lacerda', 'Queiroz', 'Peixoto',
    ];

    private array $cidades = [
        ['cidade' => 'Curitiba',             'uf' => 'PR', 'cep' => '80010-010'],
        ['cidade' => 'São José dos Pinhais', 'uf' => 'PR', 'cep' => '83005-010'],
        ['cidade' => 'Araucária',            'uf' => 'PR', 'cep' => '83701-010'],
        ['cidade' => 'Colombo',              'uf' => 'PR', 'cep' => '83404-010'],
        ['cidade' => 'Pinhais',              'uf' => 'PR', 'cep' => '83324-010'],
        ['cidade' => 'Campo Largo',          'uf' => 'PR', 'cep' => '83601-010'],
        ['cidade' => 'Almirante Tamandaré',  'uf' => 'PR', 'cep' => '83501-010'],
        ['cidade' => 'Piraquara',            'uf' => 'PR', 'cep' => '83302-010'],
    ];

    private array $ruas = [
        'Rua das Flores', 'Avenida Brasil', 'Rua XV de Novembro', 'Rua Marechal Deodoro',
        'Avenida Iguaçu', 'Rua Visconde de Nacar', 'Avenida Batel', 'Rua Comendador Araújo',
        'Rua Padre Agostinho', 'Avenida Água Verde', 'Rua Cruz Machado', 'Avenida Manoel Ribas',
        'Rua General Carneiro', 'Avenida Sete de Setembro', 'Rua Amintas de Barros',
        'Avenida República Argentina', 'Rua Imaculada Conceição', 'Rua João Negrão',
        'Avenida Cândido de Abreu', 'Rua Treze de Maio',
    ];

    private array $bairros = [
        'Centro', 'Batel', 'Água Verde', 'Portão', 'Cajuru', 'Bacacheri',
        'Hauer', 'Pinheirinho', 'Capão Raso', 'Boa Vista', 'Santa Felicidade',
        'Campo Comprido', 'Xaxim', 'Sítio Cercado', 'Guaíra', 'Alto da XV',
        'Seminário', 'Tingui', 'Pilarzinho', 'Mercês',
    ];

    private array $tiposSanguineos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private array $categoriasInaptidao = [
        'sinais_vitais_fora_do_padrao',
        'intervalo_minimo_nao_cumprido',
        'medicamento_incompativel',
        'cirurgia_recente',
        'viagem_area_de_risco',
        'comportamento_de_risco',
        'condicao_clinica_na_triagem',
    ];

    private function nome(string $sexo): string
    {
        $p = $sexo === 'M'
            ? $this->nomesMasculinos[array_rand($this->nomesMasculinos)]
            : $this->nomesFemininos[array_rand($this->nomesFemininos)];
        return "$p {$this->sobrenomes[array_rand($this->sobrenomes)]} {$this->sobrenomes[array_rand($this->sobrenomes)]}";
    }

    private function cpf(int $seq): string
    {
        return sprintf('%03d.%03d.%03d-%02d',
            ($seq * 3) % 999 + 1,
            ($seq * 7) % 999 + 1,
            ($seq * 13) % 999 + 1,
            $seq % 98 + 1
        );
    }

    private function tel(): string
    {
        $ddd = ['41','42','43','44','45','46'][rand(0,5)];
        return "($ddd) 9" . rand(1000,9999) . '-' . rand(1000,9999);
    }

    private function nascimento(): string
    {
        return rand(1965,2004) . '-' . str_pad(rand(1,12),2,'0',STR_PAD_LEFT) . '-' . str_pad(rand(1,28),2,'0',STR_PAD_LEFT);
    }

    private function ip(): string
    {
        return rand(1,254).'.'.rand(0,254).'.'.rand(0,254).'.'.rand(1,254);
    }

    private function cidade(): array
    {
        return $this->cidades[array_rand($this->cidades)];
    }

    public function run(): void
    {
        $this->command->info('DemoSeeder iniciado...');

        // Desativa verificação de FK durante inserção em massa (PostgreSQL)
        DB::statement('SET session_replication_role = replica');

        // ── Roles ────────────────────────────────────────────────────────────
        $rDoador      = Role::firstOrCreate(['name' => 'doador',      'guard_name' => 'api']);
        $rFuncionario = Role::firstOrCreate(['name' => 'funcionario', 'guard_name' => 'api']);
        $rDiretor     = Role::firstOrCreate(['name' => 'diretor',     'guard_name' => 'api']);
        $rAdmin       = Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'api']);

        // ── Hemocentros ──────────────────────────────────────────────────────
        $hemoIds = DB::table('hemocentros')->pluck('id')->toArray();
        if (empty($hemoIds)) {
            $this->command->error('Nenhum hemocentro. Rode HemocentroSeeder primeiro.');
            return;
        }

        // ── Funcionários (3 por hemocentro) ──────────────────────────────────
        $funcPorHemo = [];
        foreach ($hemoIds as $hemoId) {
            for ($f = 0; $f < 3; $f++) {
                $sexo = rand(0,1) ? 'M' : 'F';
                $u = User::create([
                    'name'           => $this->nome($sexo),
                    'email'          => "func.{$hemoId}.{$f}." . rand(100,999) . '@doevida.test',
                    'password'       => Hash::make('password'),
                    'sexo'           => $sexo,
                    'cpf'            => $this->cpf(8000 + $hemoId * 10 + $f),
                    'telefone'       => $this->tel(),
                    'hemocentro_id'  => $hemoId,
                    'role_id'        => $rFuncionario->id,
                    'status'         => true,
                    'lgpd_aceite'    => true,
                    'lgpd_aceite_em' => now(),
                    'lgpd_ip'        => $this->ip(),
                ]);
                $u->assignRole($rFuncionario);
                $funcPorHemo[$hemoId][] = $u->id;
            }
        }
        $this->command->info('Funcionários criados.');

        // ── Perguntas de triagem ──────────────────────────────────────────────
        $pergIds = DB::table('triagem_perguntas')->pluck('id')->toArray();
        if (empty($pergIds)) {
            foreach ([
                'Está se sentindo bem hoje?',
                'Dormiu bem nas últimas 24 horas?',
                'Tomou algum medicamento nos últimos 3 dias?',
                'Passou por cirurgia nos últimos 6 meses?',
                'Esteve em área de risco de doenças tropicais?',
                'Realizou procedimento odontológico nos últimos 3 dias?',
                'Está com febre ou sintomas gripais?',
                'Fez tatuagem ou piercing nos últimos 12 meses?',
            ] as $p) {
                $pergIds[] = DB::table('triagem_perguntas')->insertGetId([
                    'pergunta'   => $p, 'obrigatoria' => true, 'status' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // ── 1000 Doadores ─────────────────────────────────────────────────────
        $this->command->info('Criando 1000 doadores...');
        $bar = $this->command->getOutput()->createProgressBar(1000);

        $doadorIds  = [];
        $doadorMeta = []; // id => [tipo_sang, sexo]
        $totalDoacoes = 0;

        for ($i = 1; $i <= 1000; $i++) {
            $sexo   = rand(0,1) ? 'M' : 'F';
            $tipo   = $this->tiposSanguineos[array_rand($this->tiposSanguineos)];
            $cidade = $this->cidade();

            $u = User::create([
                'name'                => $this->nome($sexo),
                'email'               => "doador.{$i}." . rand(1000,9999) . '@doevida.test',
                'password'            => Hash::make('password'),
                'cpf'                 => $this->cpf($i),
                'tipo_sang'           => $tipo,
                'sexo'                => $sexo,
                'telefone'            => $this->tel(),
                'data_nasc'           => $this->nascimento(),
                'cep'                 => $cidade['cep'],
                'rua'                 => $this->ruas[array_rand($this->ruas)],
                'numero'              => rand(10, 2000),
                'bairro'              => $this->bairros[array_rand($this->bairros)],
                'cidade'              => $cidade['cidade'],
                'uf'                  => $cidade['uf'],
                'role_id'             => $rDoador->id,
                'status'              => rand(0,9) > 0,
                'lgpd_aceite'         => true,
                'lgpd_aceite_em'      => Carbon::now()->subDays(rand(30,730))->toDateTimeString(),
                'lgpd_ip'             => $this->ip(),
                'apto_pelo_autoexame' => (bool) rand(0,1),
                'autoexame_validade'  => rand(0,1) ? Carbon::now()->addHours(rand(1,20))->toDateTimeString() : null,
            ]);
            $u->assignRole($rDoador);

            $doadorIds[]       = $u->id;
            $doadorMeta[$u->id] = ['tipo' => $tipo, 'sexo' => $sexo];
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();

        // ── Histórico de doações ───────────────────────────────────────────────
        $this->command->info('Criando histórico de doações...');
        $bar2 = $this->command->getOutput()->createProgressBar(1000);

        foreach ($doadorIds as $doadorId) {
            $meta    = $doadorMeta[$doadorId];
            $hemoId  = $hemoIds[array_rand($hemoIds)];
            $funcs   = $funcPorHemo[$hemoId];
            $funcId  = $funcs[array_rand($funcs)];
            $numCiclos = rand(0, 5);
            if (rand(0,4) === 0) $numCiclos = 0; // 20% sem histórico

            $ultimaDoacao = null;

            for ($c = 0; $c < $numCiclos; $c++) {
                $diasAtras = (($numCiclos - $c) * 130) + rand(5, 45);
                $dataDoacao = Carbon::now()->subDays($diasAtras);

                // Agendamento
                $agendId = DB::table('agendamento')->insertGetId([
                    'user_id'            => $doadorId,
                    'coletador_id'       => $funcId,
                    'hemocentro_id'      => $hemoId,
                    'data_hora_doacao'   => $dataDoacao->toDateTimeString(),
                    'status_agendamento' => 'FIN',
                    'criado_em'          => $dataDoacao->copy()->subDays(rand(1,7))->toDateTimeString(),
                    'atualizado_em'      => $dataDoacao->toDateTimeString(),
                ]);

                // Triagem
                $apto = rand(0,9) > 1; // 80% aptos
                $dataTriagem = $dataDoacao->copy()->subMinutes(rand(20, 60));
                $triagemId = DB::table('triagens')->insertGetId([
                    'agendamento_id'   => $agendId,
                    'user_id'          => $doadorId,
                    'funcionario_id'   => $funcId,
                    'hemocentro_id'    => $hemoId,
                    'data_triagem'     => $dataTriagem->toDateTimeString(),
                    'status_triagem'   => 'E',
                    'apto'             => $apto,
                    'motivo_inaptidao' => !$apto ? 'Inaptidão identificada na triagem clínica.' : null,
                    'created_at'       => $dataTriagem->toDateTimeString(),
                    'updated_at'       => $dataDoacao->toDateTimeString(),
                ]);

                // Sinais vitais
                DB::table('triagem_sinais_vitais')->insert([
                    'triagem_id'          => $triagemId,
                    'peso'                => rand(52, 115) + (rand(0,9)/10),
                    'pressao_sistolica'   => rand(100, 135),
                    'pressao_diastolica'  => rand(60, 88),
                    'temperatura'         => 36.0 + (rand(0,8)/10),
                    'frequencia_cardiaca' => rand(58, 92),
                    'hemoglobina'         => 12.0 + (rand(0,30)/10),
                    'hematocrito'         => rand(36,52) + (rand(0,9)/10),
                    'criado_em'           => $dataTriagem->toDateTimeString(),
                ]);

                // Aptidão
                $catInap = !$apto ? $this->categoriasInaptidao[array_rand($this->categoriasInaptidao)] : null;
                DB::table('triagem_aptidao')->insert([
                    'triagem_id'           => $triagemId,
                    'resultado'            => $apto ? 'apto' : 'inapto_temporario',
                    'categoria_inaptidao'  => $catInap,
                    'observacoes_internas' => $apto
                        ? 'Doador dentro dos parâmetros clínicos. Liberado para coleta.'
                        : 'Recomendado retorno após período de recuperação.',
                    'notificacao_doador'   => $apto
                        ? 'Você está apto para a doação. Muito obrigado pela sua contribuição!'
                        : 'Infelizmente não será possível realizar a doação hoje. Retorne em breve.',
                    'valido_ate'           => !$apto ? $dataDoacao->copy()->addDays(rand(30,90))->toDateString() : null,
                    'criado_em'            => $dataDoacao->toDateTimeString(),
                    'atualizado_em'        => $dataDoacao->toDateTimeString(),
                ]);

                // Doação (só se apto)
                if ($apto) {
                    $qtd = rand(400, 480);
                    $lancado = rand(0,1);
                    DB::table('doacao')->insert([
                        'user_id'             => $doadorId,
                        'hemocentro_id'       => $hemoId,
                        'funcionario_id'      => $funcId,
                        'agendamento_id'      => $agendId,
                        'triagem_id'          => $triagemId,
                        'data_hora_doacao'    => $dataDoacao->toDateTimeString(),
                        'data_validade_sangue' => $dataDoacao->copy()->addDays(35)->toDateTimeString(),
                        'tipo_sangue'         => $meta['tipo'],
                        'quantidade'          => $qtd,
                        'atualizado_em'       => $dataDoacao->toDateTimeString(),
                        'estoque_lancado_em'  => $lancado ? $dataDoacao->copy()->addHours(rand(1,4))->toDateTimeString() : null,
                        'estoque_lancado_por' => $lancado ? $funcId : null,
                    ]);

                    $ultimaDoacao = $dataDoacao;
                    $totalDoacoes++;
                }
            }

            // Atualizar restrição no doador
            if ($ultimaDoacao) {
                $diasR = ($meta['sexo'] === 'M') ? 90 : 120;
                $restricao = $ultimaDoacao->copy()->addDays($diasR);
                if ($restricao->isFuture()) {
                    User::where('id', $doadorId)->update(['tempo_restricao' => $restricao->toDateString()]);
                }
            }

            $bar2->advance();
        }

        $bar2->finish();
        $this->command->newLine();

        // ── Agendamentos futuros (próximos 30 dias) ───────────────────────────
        $this->command->info('Criando agenda futura...');
        $horarios = ['08:00','09:00','10:00','11:00','13:00','14:00','15:00','16:00'];
        $amostra = array_slice($doadorIds, 0, 300);
        shuffle($amostra);
        foreach ($amostra as $doadorId) {
            $hemoId = $hemoIds[array_rand($hemoIds)];
            $data   = Carbon::now()->addDays(rand(1,30))->setTimeFromTimeString($horarios[rand(0,7)]);
            DB::table('agendamento')->insert([
                'user_id'            => $doadorId,
                'hemocentro_id'      => $hemoId,
                'data_hora_doacao'   => $data->toDateTimeString(),
                'status_agendamento' => rand(0,1) ? 'AGE' : 'CON',
                'criado_em'          => now()->subDays(rand(0,5))->toDateTimeString(),
                'atualizado_em'      => now()->toDateTimeString(),
            ]);
        }

        // ── Estoque consolidado ───────────────────────────────────────────────
        $this->command->info('Consolidando estoque...');
        foreach ($hemoIds as $hemoId) {
            foreach ($this->tiposSanguineos as $tipo) {
                $coletado = DB::table('doacao')
                    ->where('hemocentro_id', $hemoId)
                    ->where('tipo_sangue', $tipo)
                    ->sum('quantidade');

                $saldo = (float)$coletado * (rand(25, 65) / 100);

                DB::table('estoque')->updateOrInsert(
                    ['hemocentro_id' => $hemoId, 'tipo_sangue' => $tipo],
                    ['quantidade' => round($saldo), 'quantidade_minima' => 5000, 'atualizado_em' => now()]
                );
            }
        }

        // ── Campanhas de exemplo ──────────────────────────────────────────────
        $this->command->info('Criando campanhas...');
        foreach ([
            ['titulo' => 'Doe Vida: Campanha de Inverno',       'tipo' => null,  'dias' => -60],
            ['titulo' => 'Urgência O Negativo',                  'tipo' => 'O-',  'dias' => -30],
            ['titulo' => 'Semana do Doador Voluntário',         'tipo' => null,  'dias' => -15],
            ['titulo' => 'Campanha AB Negativo — Estoque Crítico', 'tipo' => 'AB-','dias' => -5],
            ['titulo' => 'Doe: Cada Gota Conta',                'tipo' => null,  'dias' => 7],
        ] as $camp) {
            $pub = Carbon::now()->addDays($camp['dias']);
            DB::table('campanhas')->insertOrIgnore([
                'titulo'         => $camp['titulo'],
                'subtitulo'      => 'Sua doação salva vidas. Venha doe agora.',
                'descricao'      => 'Campanha de incentivo à doação de sangue voluntária e consciente no Paraná.',
                'tipo_sangue'    => $camp['tipo'],
                'hemocentro_id'  => null,
                'status'         => true,
                'criado_por'     => 'seeder',
                'data_publi'     => $pub->toDateTimeString(),
                'data_expiracao' => $pub->copy()->addDays(30)->toDateTimeString(),
                'criado_em'      => $pub->toDateTimeString(),
                'atualizado_em'  => $pub->toDateTimeString(),
                'total_disparado' => rand(0, 500),
                'total_aberto'   => rand(0, 200),
            ]);
        }

        DB::statement('SET session_replication_role = DEFAULT');

        $this->command->info('');
        $this->command->info('✅ DemoSeeder concluído!');
        $this->command->info("   1000 doadores com nomes reais fictícios");
        $this->command->info("   $totalDoacoes doações com triagem, sinais vitais e aptidão vinculados");
        $this->command->info("   300 agendamentos futuros");
        $this->command->info("   Estoque atualizado para todos os hemocentros");
        $this->command->info("   5 campanhas criadas");
    }
}
