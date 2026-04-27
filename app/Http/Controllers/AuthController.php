<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;




class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6|confirmed',
            'cpf'       => 'required|string|size:11|unique:users,cpf',
            'telefone' => 'required|string',
            'tipo_sang' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'sexo'      => 'required|in:M,F,Outro,Prefiro não informar',
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
        ]);
$validated['telefone'] = preg_replace('/\D/', '', $validated['telefone']);
        try {
            $dataNasc = Carbon::createFromFormat('d/m/Y', $validated['data_nasc']);
            $idade = $dataNasc->age;

            if (!$this->validarCPF($validated['cpf'])) {
                return response()->json(['error' => 'CPF inválido.'], 422);
            }

            if ($idade < 18) {
                if (
                    empty($validated['responsavel_nome']) ||
                    empty($validated['responsavel_cpf']) ||
                    empty($validated['responsavel_data_nasc'])
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
            }

            // ✅ CRIA USUÁRIO
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
                'hemocentro_id' => null,
                'status'    => true,

                'responsavel_nome' => $validated['responsavel_nome'] ?? null,
                'responsavel_cpf'  => $validated['responsavel_cpf'] ?? null,
                'responsavel_data_nasc' => !empty($validated['responsavel_data_nasc'])
                    ? Carbon::createFromFormat('d/m/Y', $validated['responsavel_data_nasc'])->format('Y-m-d')
                    : null,
            ]);

            // 🔥 SPATIE
            $user->assignRole('doador');

            return response()->json([
                'message' => 'Doador registrado com sucesso!',
                'user'    => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao cadastrar usuário',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function validarCPF($cpf)
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

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail ou senha inválidos.'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user'    => $user,
            'roles'   => $user->getRoleNames(),
            'token'   => $token,
            'token_type' => 'Bearer',
        ]);
    }
}