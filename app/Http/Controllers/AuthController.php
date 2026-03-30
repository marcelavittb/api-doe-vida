<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;



class AuthController extends Controller
{
    public function register(Request $request){

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    public function login(Request $request){

    }
    
}

// FRONT -> API METODO X -> CONTROLLER -> ENVIAR PARA O MODEL -> MODEL FAZ A OPERAÇÃO NO BANCO DE DADOS -> RETORNA PARA O CONTROLLER -> CONTROLLER RETORNA PARA O FRONT 