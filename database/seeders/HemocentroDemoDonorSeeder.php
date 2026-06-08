<?php

namespace Database\Seeders;

use App\Models\Hemocentro;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HemocentroDemoDonorSeeder extends Seeder
{
    private const DONORS_PER_HEMOCENTRO = 40;

    private array $maleNames = [
        'Lucas', 'Gabriel', 'Mateus', 'Pedro', 'Rafael', 'Bruno', 'Thiago', 'Leonardo',
        'Henrique', 'Carlos', 'Fernando', 'Guilherme', 'Diego', 'Samuel', 'Vinicius',
    ];

    private array $femaleNames = [
        'Ana', 'Beatriz', 'Camila', 'Daniela', 'Fernanda', 'Gabriela', 'Helena', 'Isabela',
        'Juliana', 'Larissa', 'Mariana', 'Natalia', 'Patricia', 'Renata', 'Vanessa',
    ];

    private array $lastNames = [
        'Silva', 'Santos', 'Oliveira', 'Souza', 'Pereira', 'Costa', 'Lima', 'Gomes',
        'Ribeiro', 'Martins', 'Carvalho', 'Almeida', 'Barbosa', 'Rocha', 'Dias',
    ];

    private array $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private array $streets = [
        'Rua das Flores',
        'Avenida Brasil',
        'Rua Quinze de Novembro',
        'Rua Marechal Deodoro',
        'Avenida Iguacu',
        'Rua Padre Anchieta',
        'Avenida Sete de Setembro',
        'Rua Conselheiro Laurindo',
    ];

    private array $districts = [
        'Centro',
        'Batel',
        'Agua Verde',
        'Portao',
        'Boa Vista',
        'Alto da XV',
        'Bacacheri',
        'Cabral',
    ];

    public function run(): void
    {
        $roleDonor = $this->role('doador');
        $roleStaff = $this->role('funcionario');
        $hemocentros = Hemocentro::query()->orderBy('id')->get();

        if ($hemocentros->isEmpty()) {
            $this->command?->warn('Nenhum hemocentro encontrado para criar doadores de demo.');
            return;
        }

        $totalCreatedOrUpdated = 0;

        foreach ($hemocentros as $hemocentro) {
            $staff = $this->ensureStaffMember($hemocentro, $roleStaff);

            for ($index = 1; $index <= self::DONORS_PER_HEMOCENTRO; $index++) {
                $sex = $index % 2 === 0 ? 'F' : 'M';
                $email = sprintf('demo.doador.h%d.%02d@doevida.test', $hemocentro->id, $index);
                $birthDate = Carbon::now()->subYears(19 + (($index + $hemocentro->id) % 27))->subDays($index);
                $pastDonationAt = Carbon::now()
                    ->subDays(35 + (($index + $hemocentro->id) % 40))
                    ->setTime(8 + ($index % 8), 0);
                $futureAppointmentAt = $this->juneAppointmentAt($hemocentro->id, $index);
                $bloodType = $this->bloodTypes[($index + $hemocentro->id) % count($this->bloodTypes)];
                $cpf = $this->formatCpf($this->generateCpfNumber(($hemocentro->id * 1000) + $index));
                $phone = $this->phoneFor($hemocentro->id, $index);
                $name = $this->fullNameFor($sex, $index, $hemocentro->id);
                $now = now();

                $userId = DB::table('users')->where('email', $email)->value('id');

                $baseUserData = [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'cpf' => $cpf,
                    'telefone' => $phone,
                    'tipo_sang' => $bloodType,
                    'sexo' => $sex,
                    'data_nasc' => $birthDate->format('Y-m-d'),
                    'cep' => $this->normalizedCepFor($hemocentro->id, $index),
                    'rua' => $this->streets[($index + $hemocentro->id) % count($this->streets)],
                    'numero' => 100 + $index,
                    'bairro' => $this->districts[($index + $hemocentro->id) % count($this->districts)],
                    'cidade' => $hemocentro->cidade,
                    'uf' => $hemocentro->uf,
                    'hemocentro_id' => $hemocentro->id,
                    'role_id' => $roleDonor->id,
                    'lgpd_aceite_em' => $now,
                    'lgpd_ip' => sprintf('10.%d.%d.%d', $hemocentro->id, $index, 10 + $index),
                    'apto_pelo_autoexame' => DB::raw('true'),
                    'autoexame_validade' => Carbon::now()->addDays(7),
                    'tempo_restricao' => $this->restrictionUntil($sex, $pastDonationAt),
                    'atualizado_em' => $now,
                ];

                if ($userId) {
                    DB::table('users')
                        ->where('id', $userId)
                        ->update(array_merge($baseUserData, [
                            'status' => DB::raw('true'),
                            'lgpd_aceite' => DB::raw('true'),
                        ]));
                } else {
                    $userId = DB::table('users')->insertGetId(array_merge($baseUserData, [
                        'status' => DB::raw('true'),
                        'lgpd_aceite' => DB::raw('true'),
                        'criado_em' => $now,
                    ]));
                }

                $user = User::find($userId);
                if ($user) {
                    $user->syncRoles([$roleDonor]);
                }

                $this->ensurePastDonationFlow(
                    userId: $userId,
                    staffId: $staff->id,
                    hemocentroId: $hemocentro->id,
                    bloodType: $bloodType,
                    donatedAt: $pastDonationAt
                );

                $this->ensureJuneAppointment(
                    userId: $userId,
                    hemocentroId: $hemocentro->id,
                    scheduledAt: $futureAppointmentAt
                );

                $totalCreatedOrUpdated++;
            }
        }

        $this->command?->info("Seeder de demo finalizado: {$totalCreatedOrUpdated} doadores preparados.");
    }

    private function ensurePastDonationFlow(int $userId, int $staffId, int $hemocentroId, string $bloodType, Carbon $donatedAt): void
    {
        $appointmentId = DB::table('agendamento')
            ->where('user_id', $userId)
            ->where('hemocentro_id', $hemocentroId)
            ->where('data_hora_doacao', $donatedAt->toDateTimeString())
            ->value('id');

        if (! $appointmentId) {
            $appointmentId = DB::table('agendamento')->insertGetId([
                'user_id' => $userId,
                'coletador_id' => $staffId,
                'hemocentro_id' => $hemocentroId,
                'data_hora_doacao' => $donatedAt->toDateTimeString(),
                'status_agendamento' => 'FIN',
                'criado_em' => $donatedAt->copy()->subDays(5)->toDateTimeString(),
                'atualizado_em' => $donatedAt->toDateTimeString(),
            ]);
        }

        $screeningAt = $donatedAt->copy()->subMinutes(45);
        $triageId = DB::table('triagens')
            ->where('agendamento_id', $appointmentId)
            ->value('id');

        if (! $triageId) {
            $triageId = DB::table('triagens')->insertGetId([
                'agendamento_id' => $appointmentId,
                'user_id' => $userId,
                'funcionario_id' => $staffId,
                'hemocentro_id' => $hemocentroId,
                'data_triagem' => $screeningAt->toDateTimeString(),
                'status_triagem' => 'E',
                'apto' => DB::raw('true'),
                'motivo_inaptidao' => null,
                'observacoes' => 'Triagem de demonstracao concluida com aptidao positiva.',
                'created_at' => $screeningAt->toDateTimeString(),
                'updated_at' => $donatedAt->toDateTimeString(),
            ]);
        }

        DB::table('triagem_sinais_vitais')->updateOrInsert(
            ['triagem_id' => $triageId],
            [
                'peso' => 68.5,
                'pressao_sistolica' => 120,
                'pressao_diastolica' => 80,
                'temperatura' => 36.5,
                'frequencia_cardiaca' => 72,
                'hemoglobina' => 13.8,
                'hematocrito' => 42.0,
                'criado_em' => $screeningAt->toDateTimeString(),
            ]
        );

        DB::table('triagem_aptidao')->updateOrInsert(
            ['triagem_id' => $triageId],
            [
                'resultado' => 'apto',
                'categoria_inaptidao' => null,
                'observacoes_internas' => 'Doador apto para demonstracao do sistema.',
                'notificacao_doador' => 'Voce esta apto para doar sangue. Obrigado por ajudar!',
                'valido_ate' => null,
                'criado_em' => $donatedAt->toDateTimeString(),
                'atualizado_em' => $donatedAt->toDateTimeString(),
            ]
        );

        $donationExists = DB::table('doacao')
            ->where('agendamento_id', $appointmentId)
            ->where('triagem_id', $triageId)
            ->exists();

        if (! $donationExists) {
            DB::table('doacao')->insert([
                'user_id' => $userId,
                'hemocentro_id' => $hemocentroId,
                'funcionario_id' => $staffId,
                'agendamento_id' => $appointmentId,
                'triagem_id' => $triageId,
                'data_hora_doacao' => $donatedAt->toDateTimeString(),
                'data_validade_sangue' => $donatedAt->copy()->addDays(35)->toDateTimeString(),
                'tipo_sangue' => $bloodType,
                'quantidade' => 450,
                'atualizado_em' => $donatedAt->toDateTimeString(),
                'created_at' => $donatedAt->toDateTimeString(),
                'updated_at' => $donatedAt->toDateTimeString(),
                'estoque_lancado_em' => $donatedAt->copy()->addHour()->toDateTimeString(),
                'estoque_lancado_por' => $staffId,
            ]);
        }
    }

    private function ensureJuneAppointment(int $userId, int $hemocentroId, Carbon $scheduledAt): void
    {
        $existingId = DB::table('agendamento')
            ->where('user_id', $userId)
            ->where('hemocentro_id', $hemocentroId)
            ->whereIn('status_agendamento', ['AGE', 'CON'])
            ->orderByDesc('id')
            ->value('id');

        if ($existingId) {
            DB::table('agendamento')
                ->where('id', $existingId)
                ->update([
                    'data_hora_doacao' => $scheduledAt->toDateTimeString(),
                    'status_agendamento' => 'AGE',
                    'atualizado_em' => Carbon::now()->toDateTimeString(),
                ]);
            return;
        }

        DB::table('agendamento')->insert([
            'user_id' => $userId,
            'hemocentro_id' => $hemocentroId,
            'data_hora_doacao' => $scheduledAt->toDateTimeString(),
            'status_agendamento' => 'AGE',
            'criado_em' => Carbon::now()->subDays(1)->toDateTimeString(),
            'atualizado_em' => Carbon::now()->toDateTimeString(),
        ]);
    }

    private function juneAppointmentAt(int $hemocentroId, int $index): Carbon
    {
        $year = (int) Carbon::now()->format('Y');
        $startDay = Carbon::now()->month === 6
            ? max(Carbon::now()->day, 1)
            : 1;
        $day = $startDay + (($index + ($hemocentroId * 3)) % max(30 - $startDay + 1, 1));
        $hourSlots = [8, 9, 10, 11, 13, 14, 15, 16];

        return Carbon::create($year, 6, $day, $hourSlots[$index % count($hourSlots)], 0, 0);
    }

    private function ensureStaffMember(Hemocentro $hemocentro, Role $roleStaff): User
    {
        $staff = User::query()
            ->where('role_id', $roleStaff->id)
            ->where('hemocentro_id', $hemocentro->id)
            ->first();

        if ($staff) {
            return $staff;
        }

        $email = sprintf('demo.funcionario.h%d@doevida.test', $hemocentro->id);
        $now = now();

        $staffId = DB::table('users')->where('email', $email)->value('id');
        if (! $staffId) {
            $staffId = DB::table('users')->insertGetId([
                'name' => 'Funcionario Demo ' . $hemocentro->nome,
                'email' => $email,
                'password' => Hash::make('password'),
                'cpf' => $this->formatCpf($this->generateCpfNumber(($hemocentro->id * 1000) + 900)),
                'telefone' => $this->phoneFor($hemocentro->id, 90),
                'hemocentro_id' => $hemocentro->id,
                'role_id' => $roleStaff->id,
                'status' => DB::raw('true'),
                'lgpd_aceite' => DB::raw('true'),
                'lgpd_aceite_em' => $now,
                'lgpd_ip' => sprintf('10.%d.200.1', $hemocentro->id),
                'criado_em' => $now,
                'atualizado_em' => $now,
            ]);
        }

        $staff = User::findOrFail($staffId);
        $staff->syncRoles([$roleStaff]);

        return $staff;
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'api',
        ]);
    }

    private function fullNameFor(string $sex, int $index, int $hemocentroId): string
    {
        $firstNames = $sex === 'M' ? $this->maleNames : $this->femaleNames;

        return sprintf(
            '%s %s %s',
            $firstNames[($index + $hemocentroId) % count($firstNames)],
            $this->lastNames[($index * 2 + $hemocentroId) % count($this->lastNames)],
            $this->lastNames[($index * 3 + $hemocentroId) % count($this->lastNames)]
        );
    }

    private function phoneFor(int $hemocentroId, int $index): string
    {
        return sprintf('(41) 9%04d-%04d', ($hemocentroId * 100 + $index) % 10000, (5000 + $index) % 10000);
    }

    private function normalizedCepFor(int $hemocentroId, int $index): string
    {
        return sprintf('%05d-%03d', 80000 + ($hemocentroId * 10) + ($index % 10), 100 + $index);
    }

    private function restrictionUntil(string $sex, Carbon $donatedAt): ?string
    {
        $days = $sex === 'M' ? 90 : 120;
        $restriction = $donatedAt->copy()->addDays($days);

        return $restriction->isFuture() ? $restriction->toDateString() : null;
    }

    private function generateCpfNumber(int $seed): string
    {
        $base = str_pad((string) (($seed * 37) % 1000000000), 9, '0', STR_PAD_LEFT);

        if (preg_match('/^(\d)\1{8}$/', $base)) {
            $base = '123456789';
        }

        $digit1 = $this->cpfDigit($base, 10);
        $digit2 = $this->cpfDigit($base . $digit1, 11);

        return $base . $digit1 . $digit2;
    }

    private function cpfDigit(string $value, int $factor): int
    {
        $sum = 0;
        foreach (str_split($value) as $digit) {
            $sum += ((int) $digit) * $factor--;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private function formatCpf(string $cpf): string
    {
        return substr($cpf, 0, 3) . '.'
            . substr($cpf, 3, 3) . '.'
            . substr($cpf, 6, 3) . '-'
            . substr($cpf, 9, 2);
    }
}
