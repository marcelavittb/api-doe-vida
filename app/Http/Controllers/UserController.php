<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            'sexo' => 'nullable|in:M,F,Outro,Prefiro nao informar,Prefiro não informar',
            'data_nasc' => 'nullable|date',
        ]);

        if (!$this->validarCPF($validated['cpf'])) {
            return response()->json(['error' => 'CPF invalido'], 422);
        }

        if ($validated['role'] === 'doador') {
            $validated['hemocentro_id'] = null;
        }

        if (($validated['sexo'] ?? null) === 'Prefiro nao informar' || ($validated['sexo'] ?? null) === 'Prefiro não informar') {
            $validated['sexo'] = 'Outro';
        }

        if (!empty($validated['data_nasc'])) {
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
            'status' => true,
            'criado_por' => $request->user()?->id,
        ]);

        $user->assignRole($role);

        return response()->json($user, 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = User::query();

        // Filtros de busca
        $query->when($request->search, function ($q, $search) {
            $q->where('name', DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like', "%{$search}%");
        });

        $query->when($request->cpf, function ($q, $cpf) {
            $q->where('cpf', DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like', "%{$cpf}%");
        });

        $userRoleName = $user->getRoleNames()->first() ?? '';
        $isStaff = in_array($userRoleName, ['funcionario', 'diretor'])
                   || $user->hasAnyPermission(['ver_agendamentos', 'ver_doacoes', 'ver_triagens']);
        $isDonor  = $userRoleName === 'doador';

        if ($isStaff && $user->hemocentro_id) {
            $hemocentroId = $user->hemocentro_id;
            $query->where('role_id', 1)
                ->whereHas('triagens', function ($q) use ($hemocentroId) {
                    $q->where('hemocentro_id', $hemocentroId);
                });
        } elseif ($isDonor) {
            $query->where('id', $user->id);
        }
        // Admin e roles customizadas sem hemocentro_id: sem filtro adicional (vê tudo)

        return $query->orderBy('name')->get();
    }

    public function show(int $id)
    {
        return User::findOrFail($id);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $this->normalizarRoleRequest($request);

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
            'sexo' => 'sometimes|in:M,F,Outro,Prefiro nao informar,Prefiro não informar',
            'telefone' => 'sometimes|string|max:20',
            'data_nasc' => 'sometimes|date',
        ]);

        if (isset($validated['cpf']) && !$this->validarCPF($validated['cpf'])) {
            return response()->json(['error' => 'CPF invalido'], 422);
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (!empty($validated['data_nasc'])) {
            $validated['data_nasc'] = $this->formatarData($validated['data_nasc']);
        }

        if (($validated['sexo'] ?? null) === 'Prefiro nao informar' || ($validated['sexo'] ?? null) === 'Prefiro não informar') {
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

        $user->update($validated);

        if (isset($role)) {
            $user->syncRoles([$role]);
        }

        return response()->json([
            'message' => 'Usuario atualizado com sucesso!',
            'data' => $user->fresh(),
        ], 200);
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        $user->status = false;
        $user->save();
        $user->delete();

        return response()->json([
            'message' => 'Usuario inativado e removido com sucesso',
        ]);
    }

    private function normalizarRoleRequest(Request $request): void
    {
        if ($request->filled('role_id') && !$request->filled('role')) {
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
