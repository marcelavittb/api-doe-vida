<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstoqueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?: Auth::user();
        $query = Estoque::with('hemocentro')->orderBy('hemocentro_id')->orderBy('tipo_sangue');

        if ($user->hemocentro_id) {
            $query->where('hemocentro_id', $user->hemocentro_id);
        } elseif ($request->filled('hemocentro_id')) {
            $query->where('hemocentro_id', $request->hemocentro_id);
        }

        if ($request->filled('tipo_sangue')) {
            $query->where('tipo_sangue', $request->tipo_sangue);
        }

        return response()->json([
            'status' => 'sucesso',
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        if ($user->hemocentro_id) {
            $request->merge(['hemocentro_id' => $user->hemocentro_id]);
        }

        $validated = $request->validate([
            'hemocentro_id' => 'required|exists:hemocentros,id',
            'tipo_sangue' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'quantidade' => 'required|numeric',
            'quantidade_minima' => 'nullable|numeric|min:0',
        ]);

        $estoque = Estoque::firstOrNew([
            'hemocentro_id' => $validated['hemocentro_id'],
            'tipo_sangue' => $validated['tipo_sangue'],
        ]);

        $novaQuantidade = ($estoque->quantidade ?? 0) + $validated['quantidade'];

        if ($novaQuantidade < 0) {
            return response()->json([
                'message' => 'Quantidade insuficiente no estoque para essa baixa.',
                'estoque_atual' => $estoque->quantidade ?? 0,
            ], 422);
        }

        $estoque->quantidade = $novaQuantidade;
        $estoque->quantidade_minima = $validated['quantidade_minima'] ?? $estoque->quantidade_minima ?? 5000;
        $estoque->atualizado_em = now();
        $estoque->save();

        return response()->json([
            'message' => 'Estoque atualizado com sucesso!',
            'data' => $estoque->fresh('hemocentro'),
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user() ?: Auth::user();
        $estoque = Estoque::with('hemocentro')->findOrFail($id);

        if ($user->hemocentro_id && (int) $estoque->hemocentro_id !== (int) $user->hemocentro_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($estoque);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user() ?: Auth::user();
        $estoque = Estoque::findOrFail($id);

        if ($user->hemocentro_id && (int) $estoque->hemocentro_id !== (int) $user->hemocentro_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $validated = $request->validate([
            'quantidade' => 'sometimes|numeric|min:0',
            'quantidade_minima' => 'sometimes|numeric|min:0',
        ]);

        $validated['atualizado_em'] = now();
        $estoque->update($validated);

        return response()->json([
            'message' => 'Estoque atualizado com sucesso!',
            'data' => $estoque->fresh('hemocentro'),
        ]);
    }
}
