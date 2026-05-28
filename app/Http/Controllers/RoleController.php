<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const ROLES_SISTEMA = ['doador', 'funcionario', 'diretor', 'admin', 'enfermeiro'];

    private const PERMISSIONS = [
        'Agendamentos' => [
            'ver_agendamentos'       => 'Ver agendamentos',
            'criar_agendamentos'     => 'Criar agendamentos',
            'confirmar_agendamentos' => 'Confirmar / reabrir agendamentos',
            'cancelar_agendamentos'  => 'Cancelar agendamentos',
        ],
        'Doações' => [
            'ver_doacoes'       => 'Ver doações',
            'registrar_doacoes' => 'Registrar doações',
        ],
        'Triagem' => [
            'ver_triagens'      => 'Ver triagens',
            'registrar_triagem' => 'Registrar triagem clínica',
        ],
        'Estoque' => [
            'ver_estoque'       => 'Ver estoque',
            'gerenciar_estoque' => 'Gerenciar estoque',
        ],
        'Alertas Médicos' => [
            'ver_alertas_medicos'       => 'Ver alertas médicos',
            'gerenciar_alertas_medicos' => 'Criar / editar alertas médicos',
        ],
        'Usuários' => [
            'ver_usuarios'     => 'Ver usuários',
            'criar_usuarios'   => 'Criar usuários',
            'excluir_usuarios' => 'Excluir usuários',
        ],
        'Hemocentros' => [
            'ver_hemocentros'       => 'Ver hemocentros',
            'gerenciar_hemocentros' => 'Gerenciar hemocentros',
        ],
        'Campanhas' => [
            'ver_campanhas'       => 'Ver campanhas',
            'gerenciar_campanhas' => 'Criar / editar campanhas',
            'disparar_campanhas'  => 'Disparar campanhas',
        ],
        'Estatísticas' => [
            'ver_estatisticas_hemocentro' => 'Ver estatísticas do hemocentro',
            'ver_estatisticas_globais'    => 'Ver estatísticas globais',
        ],
        'Relatórios' => [
            'exportar_relatorios' => 'Exportar relatórios PDF',
        ],
    ];

    public function permissions()
    {
        return response()->json(self::PERMISSIONS);
    }

    public function index()
    {
        $userCounts = DB::table('users')
            ->select('role_id', DB::raw('COUNT(*) as total'))
            ->whereNull('deletado_em')
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $roles = Role::with('permissions')->orderBy('id')->get()
            ->each(fn (Role $role) => $role->setAttribute('users_count', (int) ($userCounts[$role->id] ?? 0)))
            ->map(fn ($role) => $this->serializeRole($role));

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:50|regex:/^[a-z0-9_]+$/|unique:roles,name',
            'permissions'   => 'array',
            'permissions.*' => ['string', Rule::in($this->permissionNames())],
        ]);

        $name = strtolower(trim($validated['name']));

        if (in_array($name, self::ROLES_SISTEMA, true)) {
            return response()->json(['message' => 'Roles do sistema nao podem ser criadas pelo dashboard.'], 422);
        }

        $role = Role::create([
            'name'       => $name,
            'guard_name' => 'api',
        ]);

        if (!empty($validated['permissions'])) {
            $this->ensurePermissionsExist($validated['permissions']);
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($this->serializeRole($role->fresh('permissions')), 201);
    }

    public function update(Request $request, Role $role)
    {
        if (in_array($role->name, self::ROLES_SISTEMA, true)) {
            return response()->json(['message' => 'Roles do sistema nao podem ser alteradas.'], 422);
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:50|regex:/^[a-z0-9_]+$/|unique:roles,name,' . $role->id,
            'permissions'   => 'array',
            'permissions.*' => ['string', Rule::in($this->permissionNames())],
        ]);

        $name = strtolower(trim($validated['name']));

        if (in_array($name, self::ROLES_SISTEMA, true)) {
            return response()->json(['message' => 'Nomes de roles do sistema sao reservados.'], 422);
        }

        $role->update(['name' => $name]);

        if (array_key_exists('permissions', $validated)) {
            $this->ensurePermissionsExist($validated['permissions']);
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($this->serializeRole($role->fresh('permissions')));
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::ROLES_SISTEMA, true)) {
            return response()->json(['message' => 'Roles do sistema nao podem ser removidas.'], 422);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'Role possui usuarios vinculados. Remova-os antes de excluir.'], 422);
        }

        $role->delete();

        return response()->json(null, 204);
    }

    private function serializeRole(Role $role): array
    {
        return [
            'id'          => $role->id,
            'name'        => $role->name,
            'guard_name'  => $role->guard_name,
            'users_count' => (int) ($role->users_count ?? 0),
            'sistema'     => in_array($role->name, self::ROLES_SISTEMA, true),
            'permissions' => $role->permissions->pluck('name')->values(),
        ];
    }

    private function ensurePermissionsExist(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }
    }

    private function permissionNames(): array
    {
        return collect(self::PERMISSIONS)
            ->flatMap(fn (array $permissions) => array_keys($permissions))
            ->values()
            ->all();
    }
}
