<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $this->normalizarRoleRequest($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'cpf' => 'required|string|max:14|unique:users,cpf',
            'role' => ['required', 'string', 'exists:roles,name'],
            'hemocentro_id' => [
                Rule::requiredIf(fn () => in_array($request->role, ['funcionario', 'diretor'], true)),
                'nullable',
                'exists:hemocentros,id',
            ],
            'telefone' => 'nullable|string|max:20',
            'tipo_sang' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'sexo' => 'nullable|in:M,F,Outro,Prefiro nao informar,Prefiro nÃ£o informar',
            'data_nasc' => 'nullable|date',
        ]);

        if (! $this->validarCPF($validated['cpf'])) {
            return response()->json(['error' => 'CPF invalido'], 422);
        }

        if ($validated['role'] === 'doador') {
            $validated['hemocentro_id'] = null;
        }

        if (($validated['sexo'] ?? null) === 'Prefiro nao informar' || ($validated['sexo'] ?? null) === 'Prefiro nÃ£o informar') {
            $validated['sexo'] = 'Outro';
        }

        if (! empty($validated['data_nasc'])) {
            $validated['data_nasc'] = $this->formatarData($validated['data_nasc']);
        }

        $role = $this->buscarRole($validated['role']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'cpf' => $validated['cpf'],
            'role_id' => $role->id,
            'hemocentro_id' => $validated['hemocentro_id'] ?? null,
            'telefone' => $validated['telefone'] ?? null,
            'tipo_sang' => $validated['tipo_sang'] ?? null,
            'sexo' => $validated['sexo'] ?? null,
            'data_nasc' => $validated['data_nasc'] ?? null,
            'status' => $this->booleanSqlExpression(true),
            'criado_por' => $request->user()?->id,
        ]);

        $user->assignRole($role);

        return response()->json($user, 201);
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = User::query();
            $donorRoleId = $this->roleId('doador');
            $staffRoleIds = array_values(array_filter([
                $this->roleId('funcionario'),
                $this->roleId('diretor'),
            ]));

            $userRoleName = $user->getRoleNames()->first() ?? '';
            $isStaff = in_array($userRoleName, ['funcionario', 'diretor'], true)
                       || $user->hasAnyPermission(['ver_agendamentos', 'ver_doacoes', 'ver_triagens']);
            $isDonor = $userRoleName === 'doador';
            $staffScopeRequested = $request->input('scope') === 'staff';

            if ($staffScopeRequested) {
                if ((!in_array($userRoleName, ['diretor', 'admin'], true)) && (int) $user->role_id !== 4) {
                    return response()->json(['message' => 'Nao autorizado'], 403);
                }

                $hemocentroId = $request->input('hemocentro_id') ?: $user->hemocentro_id;

                if ($hemocentroId) {
                    $query->where('users.hemocentro_id', $hemocentroId);
                }

                if (!empty($staffRoleIds)) {
                    $query->whereIn('role_id', $staffRoleIds);
                }

                $query->with('hemocentro');
            } elseif ($isStaff && $user->hemocentro_id && $donorRoleId !== null) {
                $hemocentroId = $user->hemocentro_id;
                $query->where('role_id', $donorRoleId)
                    ->where(function ($q) use ($hemocentroId) {
                        $q->where('users.hemocentro_id', $hemocentroId)
                            ->orWhereHas('triagens', function ($triagens) use ($hemocentroId) {
                                $triagens->where('hemocentro_id', $hemocentroId);
                            })->orWhereExists(function ($sub) use ($hemocentroId) {
                                $sub->select(DB::raw(1))
                                    ->from('doacao')
                                    ->whereColumn('doacao.user_id', 'users.id')
                                    ->where('doacao.hemocentro_id', $hemocentroId);
                            });
                    });
            } elseif ($isDonor) {
                $query->where('id', $user->id);
            }

            if ($request->filled('search') || $request->filled('name')) {
                $searchTerm = $request->input('search') ?: $request->input('name');
                $query->whereRaw('LOWER(users.name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            }

            if ($request->filled('cpf')) {
                $cpf = $request->input('cpf');
                $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
                $query->where(function ($q) use ($cpf, $cpfLimpo) {
                    $q->where('users.cpf', 'like', "%{$cpf}%")
                        ->orWhereRaw("REPLACE(REPLACE(users.cpf, '.', ''), '-', '') LIKE ?", ["%{$cpfLimpo}%"]);
                });
            }

            if ($request->filled('tipo_sang')) {
                $query->where('users.tipo_sang', $request->input('tipo_sang'));
            }

            if ($request->filled('sexo')) {
                $query->where('users.sexo', $request->input('sexo'));
            }

            if ($request->has('status') && $request->input('status') !== null && $request->input('status') !== '') {
                $statusValue = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($statusValue !== null) {
                    $query->where('users.status', $statusValue);
                }
            }

            if ($request->filled('cidade')) {
                $query->whereRaw('LOWER(users.cidade) LIKE ?', ['%' . strtolower($request->input('cidade')) . '%']);
            }

            if ($request->filled('idade_min')) {
                $maxBirthDate = now()->subYears((int) $request->input('idade_min'))->toDateString();
                $query->whereDate('users.data_nasc', '<=', $maxBirthDate);
            }
            if ($request->filled('idade_max')) {
                $minBirthDate = now()->subYears((int) $request->input('idade_max') + 1)->addDay()->toDateString();
                $query->whereDate('users.data_nasc', '>=', $minBirthDate);
            }

            if ($request->filled('data_doacao_inicio') || $request->filled('data_doacao_fim')) {
                $query->whereHas('doacoes', function ($sub) use ($request) {
                    if ($request->filled('data_doacao_inicio')) {
                        $sub->where('data_hora_doacao', '>=', $request->input('data_doacao_inicio'));
                    }
                    if ($request->filled('data_doacao_fim')) {
                        $sub->where('data_hora_doacao', '<=', $request->input('data_doacao_fim'));
                    }
                });
            }

            $query->with(['doacoes' => function ($q) {
                $q->orderBy('data_hora_doacao', 'desc')->limit(1);
            }, 'doacoes.hemocentro']);
            $query->withMax('doacoes', 'data_hora_doacao');

            $perPage = $request->input('per_page', 15);
            $users = $query->orderBy('name')->paginate($perPage);
            $users->getCollection()->transform(function (User $donor) {
                $latestDonation = $donor->doacoes->first();

                $donor->setAttribute('ultima_doacao_em', $latestDonation?->data_hora_doacao);
                $donor->setAttribute('hemocentro_nome', $latestDonation?->hemocentro?->nome);
                $donor->setAttribute('hemocentro_cidade', $latestDonation?->hemocentro?->cidade);

                return $donor;
            });

            return response()->json([
                'data' => $users->items(),
                'items' => $users->items(),
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao listar usuarios', [
                'query' => $request->query(),
                'auth_user_id' => $request->user()?->id,
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao listar usuarios.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function perfilRfmt(Request $request)
    {
        $user = $request->user();
        $roleName = $user->getRoleNames()->first() ?? '';
        if ($roleName !== 'admin' && $user->role_id != 4 && ! $user->hasPermissionTo('gerenciar_campanhas')) {
            return response()->json(['message' => 'Nao autorizado'], 403);
        }

        $donorRoleId = $this->roleId('doador');
        if ($donorRoleId === null) {
            return response()->json([
                'status' => 'erro',
                'message' => 'Role de doador nao encontrada.',
            ], 500);
        }

        $hoje = now();

        $doadores = User::where('role_id', $donorRoleId)
            ->where('status', DB::raw('true'))
            ->whereNotNull('email')
            ->withCount('doacoes as total_doacoes')
            ->with(['doacoes' => function ($q) {
                $q->select('user_id', 'data_hora_doacao', 'quantidade')
                    ->orderBy('data_hora_doacao', 'desc');
            }])
            ->get(['id', 'name', 'email', 'tipo_sang', 'tempo_restricao', 'criado_em'])
            ->map(function ($doador) use ($hoje) {
                $doacoes = $doador->doacoes;

                $ultimaDoacao = $doacoes->first();
                $recenciaMeses = $ultimaDoacao
                    ? (int) $hoje->diffInMonths($ultimaDoacao->data_hora_doacao)
                    : 24;

                $frequencia = (int) ($doador->total_doacoes ?? 0);

                $volumeTotal = $doacoes->sum('quantidade');
                if ($volumeTotal == 0) {
                    $volumeTotal = $frequencia * 450;
                }

                $primeiraDoacao = $doacoes->last();
                $tempoPrimeiraMeses = $primeiraDoacao
                    ? (int) $hoje->diffInMonths($primeiraDoacao->data_hora_doacao)
                    : (int) $hoje->diffInMonths($doador->criado_em ?? $hoje);

                $risco = 'Ativo';
                if ($recenciaMeses > 18) {
                    $risco = 'Inativo';
                } elseif ($recenciaMeses > 9) {
                    $risco = 'Em_Risco';
                } elseif ($recenciaMeses > 3) {
                    $risco = 'Atencao';
                }

                return [
                    'id' => $doador->id,
                    'name' => $doador->name,
                    'email' => $doador->email,
                    'tipo_sang' => $doador->tipo_sang,
                    'recencia_meses' => max($recenciaMeses, 0),
                    'frequencia_doacoes' => max($frequencia, 0),
                    'volume_total_cc' => max((float) $volumeTotal, 0),
                    'tempo_desde_primeira_doacao' => max($tempoPrimeiraMeses, 1),
                    'risco_inatividade' => $risco,
                ];
            });

        return response()->json([
            'status' => 'sucesso',
            'total' => $doadores->count(),
            'data' => $doadores,
        ]);
    }

    public function show(int $id)
    {
        return User::findOrFail($id);
    }

    public function update(Request $request, int $id)
    {
        try {
            $user = User::findOrFail($id);
            $this->normalizarRoleRequest($request);

            \Log::info('Atualizacao de usuario', [
                'id' => $id,
                'dados' => $request->all(),
                'auth_user_id' => $request->user()?->id,
            ]);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|min:6',
                'cpf' => 'sometimes|string|max:14|unique:users,cpf,' . $id,
                'role' => ['sometimes', 'string', 'exists:roles,name'],
                'hemocentro_id' => [
                    Rule::requiredIf(function () use ($request, $user) {
                        $role = $request->role ?? $user->getRoleNames()->first();
                        return in_array($role, ['funcionario', 'diretor'], true);
                    }),
                    'nullable',
                    'exists:hemocentros,id',
                ],
                'status' => 'sometimes|boolean',
                'tipo_sang' => 'sometimes|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'sexo' => 'sometimes|in:M,F,Outro,Prefiro nao informar,Prefiro nÃ£o informar',
                'telefone' => 'sometimes|string|max:20',
                'data_nasc' => 'sometimes|date',
            ]);

            if (isset($validated['cpf']) && ! $this->validarCPF($validated['cpf'])) {
                return response()->json(['error' => 'CPF invalido'], 422);
            }

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            if (! empty($validated['data_nasc'])) {
                $validated['data_nasc'] = $this->formatarData($validated['data_nasc']);
            }

            if (($validated['sexo'] ?? null) === 'Prefiro nao informar' || ($validated['sexo'] ?? null) === 'Prefiro nÃ£o informar') {
                $validated['sexo'] = 'Outro';
            }

            $roleName = $validated['role'] ?? null;
            unset($validated['role']);

            if ($roleName) {
                $role = $this->buscarRole($roleName);
                $validated['role_id'] = $role->id;

                if ($roleName === 'doador') {
                    $validated['hemocentro_id'] = null;
                }
            }

            $status = array_key_exists('status', $validated) ? (bool) $validated['status'] : null;
            unset($validated['status']);
            $validated['atualizado_em'] = now();

            DB::table('users')
                ->where('id', $user->id)
                ->update($validated);

            if ($status !== null) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'status' => DB::raw($status ? 'true' : 'false'),
                        'atualizado_em' => now(),
                    ]);
            }

            if (isset($role)) {
                $user->syncRoles([$role]);
            }

            return response()->json([
                'message' => 'Usuario atualizado com sucesso!',
                'data' => $user->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Erro ao atualizar usuario', [
                'id' => $id,
                'dados' => $request->all(),
                'auth_user_id' => $request->user()?->id,
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar usuario.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->status = false;
            $user->save();
            $user->delete();

            return response()->json([
                'message' => 'Usuario inativado e removido com sucesso',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao remover usuario', [
                'id' => $id,
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao remover usuario.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function normalizarRoleRequest(Request $request): void
    {
        if ($request->filled('role_id') && ! $request->filled('role')) {
            $role = Role::find($request->input('role_id'));

            if ($role) {
                $request->merge(['role' => $role->name]);
            }
        }

        if (is_numeric($request->input('role'))) {
            $role = Role::find($request->input('role'));

            if ($role) {
                $request->merge(['role' => $role->name]);
            }
        }
    }

    private function buscarRole(string $nome): Role
    {
        return Role::where([
            'name' => $nome,
            'guard_name' => 'api',
        ])->firstOrFail();
    }

    private function roleId(string $nome): ?int
    {
        return Role::where([
            'name' => $nome,
            'guard_name' => 'api',
        ])->value('id');
    }

    private function booleanSqlExpression(bool $value): \Illuminate\Database\Query\Expression|bool
    {
        if (DB::getDriverName() === 'pgsql') {
            return DB::raw($value ? 'true' : 'false');
        }

        return $value;
    }

    private function formatarData(string $data): string
    {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
            return Carbon::createFromFormat('d/m/Y', $data)->format('Y-m-d');
        }

        return Carbon::parse($data)->format('Y-m-d');
    }

    private function validarCPF(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ((int) $cpf[$c] !== $d) {
                return false;
            }
        }

        return true;
    }
}
