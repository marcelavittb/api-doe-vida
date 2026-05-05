<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Triagem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TriagemController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Triagem::where('status_triagem', '!=', 'E')
                        ->with(['doador', 'funcionario', 'hemocentro', 'agendamento']);

        if ($user->role_id == 1) {
            $query->where('user_id', $user->id);
        } elseif ($user->hemocentro_id) {
            $query->where('hemocentro_id', $user->hemocentro_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
       $user = auth()->user();

       if (!$request->has('hemocentro_id') && $user->hemocentro_id) {
           $request->merge(['hemocentro_id' => $user->hemocentro_id]);
       }

       $rules = [
            'agendamento_id' => 'required|exists:agendamento,id',
            'user_id'        => 'required|exists:users,id',
            'hemocentro_id'  => 'required|exists:hemocentros,id',
            'data_triagem'   => 'required|date',
            'apto'           => 'required|boolean',
            'motivo_inaptidao' => 'required_if:apto,false|nullable|string',
            'observacoes'    => 'nullable|string',
       ];

       $validator = Validator::make($request->all(), $rules);
       if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
       }

       // EVITAR DUPLICIDADE: Verifica se já existe uma triagem para este agendamento
       $existente = Triagem::where('agendamento_id', $request->agendamento_id)
                           ->where('status_triagem', '!=', 'E')
                           ->first();

       if ($existente) {
           return response()->json([
               'message' => 'Já existe uma triagem registrada para este agendamento.',
               'data' => $existente
           ], 409); // Conflict
       }

       $triagem = Triagem::create([
            'agendamento_id' => $request->agendamento_id,
            'user_id'        => $request->user_id,
            'funcionario_id' => auth()->id(),
            'hemocentro_id'  => $request->hemocentro_id,
            'data_triagem'   => $request->data_triagem,
            'status_triagem' => 'C', 
            'apto'           => $request->apto,
            'motivo_inaptidao' => $request->motivo_inaptidao,
            'observacoes'    => $request->observacoes,
        ]);

        return response()->json([
            'message' => 'Triagem realizada com sucesso!',
            'data' => $triagem
        ], 201);
    }

    public function show($id)
    {
        $user = auth()->user();
        $triagem = Triagem::with(['doador', 'funcionario', 'hemocentro', 'agendamento'])->find($id);

        if (!$triagem || $triagem->status_triagem == 'E') {
            return response()->json(['message' => 'Triagem não encontrada.'], 404);
        }

        if ($user->role_id == 1 && $triagem->user_id != $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($triagem);
    }

    public function update(Request $request, $id)
    {
        $triagem = Triagem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'apto' => 'sometimes|boolean',
            'status_triagem' => 'sometimes|in:P,C,E',
            'motivo_inaptidao' => 'required_if:apto,false|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $triagem->update($request->only([
            'apto', 
            'motivo_inaptidao', 
            'observacoes', 
            'status_triagem'
        ]));

        return response()->json([
            'message' => 'Triagem atualizada com sucesso!',
            'data' => $triagem
        ]);
    }

    public function destroy($id)
    {
        $triagem = Triagem::findOrFail($id);
        
        $triagem->status_triagem = 'E'; // Excluída
        $triagem->save();

        return response()->json(['message' => 'Triagem removida do sistema.']);
    }
}
