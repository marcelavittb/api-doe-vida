<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class PasswordResetController extends Controller
{
    // 📩 ESQUECI SENHA
    public function forgot(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    // 🔥 remove tokens antigos
    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    $token = Str::random(60);

    DB::table('password_reset_tokens')->insert([
        'email' => $request->email,
        'token' => Hash::make($token),
        'created_at' => now()
    ]);

    return response()->json([
        'message' => 'Token gerado com sucesso',
        'token' => $token
    ]);
}

    // 🔐 RESETAR SENHA
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json([
                'error' => 'Token inválido'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        $user->password = Hash::make($request->password);
        $user->save();

        // remove token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Senha redefinida com sucesso'
        ]);
    }
}