<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request) {

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'cpf' => 'required|unique:users,cpf'
        ]);

        // valida CPF real
        if (!$this->validarCPF($request->cpf)) {
            return response()->json([
                'error' => 'CPF inválido'
            ], 400);
        }

        $data = $request->all();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json($user);
    }

    public function index() {
        return User::all();
    }

    // ✅ AGORA está no lugar certo
    private function validarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) return false;

        if (preg_match('/(\d)\1{10}/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;

            if ($cpf[$c] != $d) return false;
        }

        return true;
    }
    
    public function show($id) {
        return User::findOrFail($id);
    }

    public function update(Request $request, $id) 
{
    $user = User::findOrFail($id);

    $validated = $request->validate([
        'password'      => 'sometimes|min:6',
        'name'          => 'sometimes|string|max:255',
        'email'         => 'sometimes|email|unique:users,email,'.$id,
        'hemocentro_id' => 'sometimes|exists:hemocentros,id', // Sem o S
        'cpf'           => 'sometimes|unique:users,cpf,'.$id,
        'status'        => 'sometimes|boolean',
        'tipo_sang'     => 'sometimes|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
        'sexo'          => 'sometimes|in:M,F',
        'telefone'      => 'sometimes|string|max:20',
    ]);

    $user->update($validated);
     
    return response()->json([
        'message' => 'Usuário atualizado com sucesso!',
        'data' => $user->fresh() // Carrega os dados atualizados do banco
    ], 200);
}

    public function destroy($id) {

        $user = User::findOrFail($id);
        $user->status = 0;
        $user->save();

        return response()->json(['message' => 'Usuário inativado com sucesso']);
    }
}