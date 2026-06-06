<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6|confirmed',
            'cpf'       => 'required|string|max:14|unique:users,cpf',
            'telefone'  => 'required|string',
            'tipo_sang' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'sexo'      => 'required|string|max:30',
            'data_nasc' => 'required|date_format:d/m/Y',
            'cep'       => 'required|regex:/^\d{5}-?\d{3}$/',
            'rua'       => 'required|string|max:255',
            'numero'    => 'required|string|max:10',
            'bairro'    => 'nullable|string|max:255',
            'cidade'    => 'required|string|max:255',
            'uf'        => 'nullable|in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO',

            'responsavel_nome' => 'nullable|string|max:255',
            'responsavel_cpf'  => 'nullable|string|size:11',
            'responsavel_data_nasc' => 'nullable|date_format:d/m/Y',
            'responsavel_telefone' => 'nullable|string|max:20',
            'lgpd_aceite' => 'required|accepted',
            'respostas_pre_triagem' => 'nullable|array',
            'respostas_pre_triagem.*.pergunta_id' => 'required_with:respostas_pre_triagem|exists:triagem_perguntas,id',
            'respostas_pre_triagem.*.opcao_id' => 'required_with:respostas_pre_triagem|exists:triagem_opcoes,id',
            'respostas_pre_triagem.*.resultado_geral' => 'nullable|in:apto,inapto,consulte_medico',
        ]);

        $validated['telefone'] = preg_replace('/\D/', '', $validated['telefone']);
        if (!empty($validated['responsavel_telefone'])) {
            $validated['responsavel_telefone'] = preg_replace('/\D/', '', $validated['responsavel_telefone']);
        }

        if (in_array($validated['sexo'], ['Prefiro não informar', 'Prefiro nÃ£o informar'], true)) {
            $validated['sexo'] = 'Outro';
        }

        if (!in_array($validated['sexo'], ['M', 'F', 'Outro'], true)) {
            return response()->json([
                'error' => 'Sexo inválido.',
                'errors' => [
                    'sexo' => ['O campo sexo deve ser M, F ou Outro.'],
                ],
            ], 422);
        }

        try {
            $dataNasc = Carbon::createFromFormat('d/m/Y', $validated['data_nasc']);
            $idade = $dataNasc->age;

            if (!$this->validarCPF($validated['cpf'])) {
                return response()->json(['error' => 'CPF inválido.'], 422);
            }

            if ($idade < 16) {
                return response()->json([
                    'error' => 'Ã‰ necessÃ¡rio ter no mÃ­nimo 16 anos para se cadastrar como doador.'
                ], 422);
            }

            if ($idade < 18) {
                if (
                    empty($validated['responsavel_nome']) ||
                    empty($validated['responsavel_cpf']) ||
                    empty($validated['responsavel_data_nasc']) ||
                    empty($validated['responsavel_telefone'])
                ) {
                    return response()->json([
                        'error' => 'Menores de idade precisam de responsável.'
                    ], 422);
                }

                if (!$this->validarCPF($validated['responsavel_cpf'])) {
                    return response()->json([
                        'error' => 'CPF do responsável inválido.'
                    ], 422);
                }

                $dataResp = Carbon::createFromFormat('d/m/Y', $validated['responsavel_data_nasc']);

                if ($dataResp->age < 18) {
                    return response()->json([
                        'error' => 'Responsável deve ser maior de idade.'
                    ], 422);
                }

                if ($dataResp->age > 100) {
                    return response()->json([
                        'error' => 'Responsavel deve ter no maximo 100 anos.'
                    ], 422);
                }

                if (!in_array(strlen($validated['responsavel_telefone']), [10, 11], true)) {
                    return response()->json([
                        'error' => 'Telefone do responsÃ¡vel invÃ¡lido.'
                    ], 422);
                }
            }

            $role = Role::firstOrCreate([
                'name' => 'doador',
                'guard_name' => 'api',
            ]);

            $user = User::create([
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'password'  => Hash::make($validated['password']),
                'cpf'       => $validated['cpf'],
                'telefone'  => $validated['telefone'] ?? null,
                'tipo_sang' => $validated['tipo_sang'] ?? null,
                'sexo'      => $validated['sexo'],
                'data_nasc' => $dataNasc->format('Y-m-d'),
                'cep'       => $validated['cep'],
                'rua'       => $validated['rua'],
                'numero'    => $validated['numero'],
                'bairro'    => $validated['bairro'] ?? null,
                'cidade'    => $validated['cidade'],
                'uf'        => $validated['uf'] ?? null,
                'role_id'   => $role->id,
                'hemocentro_id' => null,
                'status'    => true,
                'lgpd_aceite' => true,
                'lgpd_aceite_em' => now(),
                'lgpd_ip' => $request->ip(),

                'responsavel_nome' => $validated['responsavel_nome'] ?? null,
                'responsavel_cpf'  => $validated['responsavel_cpf'] ?? null,
                'responsavel_data_nasc' => !empty($validated['responsavel_data_nasc'])
                    ? Carbon::createFromFormat('d/m/Y', $validated['responsavel_data_nasc'])->format('Y-m-d')
                    : null,
                'responsavel_telefone' => $validated['responsavel_telefone'] ?? null,
            ]);

            $user->assignRole($role);

            // Salvar respostas da pré-triagem se enviadas
            if (!empty($validated['respostas_pre_triagem'])) {
                $resultadoGeral = $validated['respostas_pre_triagem'][0]['resultado_geral'] ?? null;

                if ($resultadoGeral === 'apto') {
                    $user->update([
                        'apto_pelo_autoexame' => true,
                        'autoexame_validade'  => now()->addDays(7),
                    ]);
                }

                foreach ($validated['respostas_pre_triagem'] as $resposta) {
                    \App\Models\PreTriagemResposta::create([
                        'user_id' => $user->id,
                        'pergunta_id' => $resposta['pergunta_id'],
                        'opcao_id' => $resposta['opcao_id'],
                        'resultado_geral' => $resultadoGeral,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Doador registrado com sucesso!',
                'user'    => $user->fresh(),
                'role'    => $role->name,
                'roles'   => $user->getRoleNames()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'lgpd_aceite_em' => $user->lgpd_aceite_em,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao cadastrar usuário',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function validarCPF(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;

            if ($cpf[$c] != $d) return false;
        }

        return true;
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$this->canUsePasswordReset($user)) {
            return response()->json([
                'error' => 'Apenas doadores cadastrados podem recuperar a senha.'
            ], 422);
        }

        DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->insert([
            'email' => $validated['email'],
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        try {
            Mail::raw(
                "Seu codigo de recuperacao do DoaVida: {$token}\n\nEste codigo expira em 15 minutos.",
                function ($message) use ($validated) {
                    $message->to($validated['email'])
                        ->subject('Recuperacao de senha - DoaVida');
                }
            );
        } catch (\Throwable $e) {
            DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->delete();

            Log::warning('Falha ao enviar codigo de recuperacao de senha.', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            $response = [
                'error' => 'Nao foi possivel enviar o codigo por email. Verifique a configuracao de email do servidor.'
            ];

            if (app()->environment('local')) {
                $response['details'] = $e->getMessage();
            }

            return response()->json($response, 500);
        }

        $response = [
            'message' => 'Codigo de recuperacao enviado para o email informado.',
        ];

        if (app()->environment('testing')) {
            $response['codeForDev'] = $token;
        }

        return response()->json($response, 200);
    }

    public function resetPassword(Request $request)
    {
        $this->normalizePasswordResetRequest($request);

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required'
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$this->canUsePasswordReset($user)) {
            return response()->json([
                'error' => 'Apenas doadores cadastrados podem recuperar a senha.'
            ], 422);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$record) {
            return response()->json([
                'error' => 'Codigo invalido ou expirado.'
            ], 422);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->delete();

            return response()->json([
                'error' => 'Codigo expirado. Solicite uma nova recuperacao.'
            ], 422);
        }

        if (!Hash::check($validated['token'], $record->token)) {
            return response()->json([
                'error' => 'Codigo invalido ou expirado.'
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        return response()->json([
            'message' => 'Senha redefinida com sucesso.'
        ], 200);
    }

    private function canUsePasswordReset(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('doador')) {
            return true;
        }

        $doadorRoleId = Role::where('name', 'doador')->value('id');

        return $doadorRoleId !== null && (int) $user->role_id === (int) $doadorRoleId;
    }

    private function normalizePasswordResetRequest(Request $request): void
    {
        $aliases = [
            'password' => ['senha', 'nova_senha', 'new_password', 'newPassword'],
            'password_confirmation' => ['confirmar_senha', 'senha_confirmation', 'confirmacao_senha', 'new_password_confirmation', 'confirmPassword'],
            'token' => ['codigo', 'code'],
        ];

        foreach ($aliases as $field => $possibleNames) {
            if ($request->filled($field)) {
                continue;
            }

            foreach ($possibleNames as $possibleName) {
                if ($request->filled($possibleName)) {
                    $request->merge([$field => $request->input($possibleName)]);
                    break;
                }
            }
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        // 1. Busca o usuário pelo e-mail
        $user = User::where('email', $credentials['email'])->first();

        // 2. Valida se o usuário existe e se a senha está correta usando Hash::check
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail ou senha inválidos.'
            ], 401);
        }

        // 3. Carrega o hemocentro, assim como você já estava fazendo
        /** @var \App\Models\User $user */
        $user->load('hemocentro');

        // 4. Cria o token via Sanctum de forma totalmente stateless (sem sessão)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user'    => $user,
            'roles'   => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'token'   => $token,
            'token_type' => 'Bearer',
        ]);
    }
    /**
     * POST /api/auth/elegibilidade
     * Salva o resultado do autoexame de elegibilidade.
     */
    public function salvarElegibilidade(Request $request)
    {
        $request->validate([
            'apto' => 'required|boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $user->update([
            'apto_pelo_autoexame' => $request->apto,
            // Validade de 24 horas para o teste
            'autoexame_validade'  => $request->apto ? now()->addDay() : null,
        ]);

        return response()->json([
            'status' => 'sucesso',
            'message' => 'Resultado da elegibilidade salvo com sucesso.',
            'apto' => $user->apto_pelo_autoexame,
            'validade' => $user->autoexame_validade
        ]);
    }

    /**
     * GET /api/auth/elegibilidade/atual
     * Retorna o status atual de elegibilidade do usuário.
     */
    public function getElegibilidade(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $valido = $user->autoexame_validade && $user->autoexame_validade->isFuture();

        return response()->json([
            'apto' => (bool) ($user->apto_pelo_autoexame && $valido),
            'validade' => $user->autoexame_validade,
            'is_valido' => $valido
        ]);
    }

    /**
     * DELETE /api/auth/minha-conta
     * LGPD Art. 18 — Direito de exclusão.
     * Anonimiza os dados pessoais do doador sem apagar o registro,
     * preservando a integridade das doações históricas no banco.
     */
    public function excluirConta(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        // Anonimizar dados pessoais — preserva o registro para integridade histórica
        $user->update([
            'name' => 'Usuário Removido',
            'email' => 'removido_' . $user->id . '@doe-vida.removido',
            'cpf' => str_pad((string) $user->id, 11, '0', STR_PAD_LEFT),
            'telefone' => null,
            'cep' => null,
            'rua' => null,
            'bairro' => null,
            'cidade' => null,
            'complemento' => null,
            'numero' => null,
            'uf' => null,
            'data_nasc' => null,
            'tipo_sang' => null,
            'responsavel_nome' => null,
            'responsavel_cpf' => null,
            'responsavel_data_nasc' => null,
            'responsavel_telefone' => null,
            'status' => false,
        ]);

        // Revogar todos os tokens de acesso
        $user->tokens()->delete();

        // Log de auditoria LGPD
        Log::info('LGPD — CONTA EXCLUÍDA POR SOLICITAÇÃO DO TITULAR', [
            'user_id' => $user->id,
            'timestamp' => now(),
            'ip' => $request->ip(),
        ]);

        // Soft delete
        $user->delete();

        return response()->json([
            'message' => 'Sua conta foi removida com sucesso. Seus dados pessoais foram anonimizados conforme a LGPD.',
        ]);
    }

    /**
     * GET /api/auth/meus-dados
     * LGPD Art. 18 — Direito de portabilidade.
     * Retorna todos os dados que o sistema possui sobre o doador autenticado.
     */
    public function meusDados(Request $request)
    {
        $user = $request->user()->load([
            'preTriagemRespostas.pergunta',
            'preTriagemRespostas.opcao',
            'triagens.aptidao',
            'alertasMedicos',
            'tipoSangueHistorico',
        ]);

        return response()->json([
            'dados_pessoais' => [
                'nome' => $user->name,
                'email' => $user->email,
                'cpf' => $user->cpf,
                'telefone' => $user->telefone,
                'tipo_sang' => $user->tipo_sang,
                'sexo' => $user->sexo,
                'data_nasc' => $user->data_nasc,
                'endereco' => [
                    'cep' => $user->cep,
                    'rua' => $user->rua,
                    'numero' => $user->numero,
                    'bairro' => $user->bairro,
                    'cidade' => $user->cidade,
                    'uf' => $user->uf,
                    'complemento' => $user->complemento,
                ],
                'lgpd_aceite_em' => $user->lgpd_aceite_em,
                'cadastrado_em' => $user->criado_em,
            ],
            'pre_triagem' => $user->preTriagemRespostas->map(fn($r) => [
                'pergunta' => $r->pergunta->pergunta ?? null,
                'resposta' => $r->opcao->texto_opcao ?? null,
                'resultado_geral' => $r->resultado_geral,
                'respondido_em' => $r->respondido_em,
            ]),
            'historico_tipo_sangue' => $user->tipoSangueHistorico->map(fn($h) => [
                'de' => $h->tipo_sangue_anterior,
                'para' => $h->tipo_sangue_novo,
                'motivo' => $h->categoria_motivo,
                'alterado_em' => $h->alterado_em,
            ]),
            'alertas_medicos' => $user->alertasMedicos->map(fn($a) => [
                'mensagem' => $a->notificacao_doador,
                'status' => $a->status,
                'criado_em' => $a->criado_em,
            ]),
            'exportado_em' => now(),
        ]);
    }
}
