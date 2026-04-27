<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Triagem;
use Illuminate\Http\request;
use Illuminate\support\facades\Validator;


class TriagemController extends Controller
{


    public function index()
    {
        $triagens = Triagem::where('status_triagem', '!=', 'E')
        ->with(['doador', 'funcionario', 'hemocentro'])
        ->get();
        return response()->json($triagens);

    }

    public function store(Request $request)
    {
       $rules =[
            'user_id' => 'required|exists:users,id',
            'funcionario_id' => 'required|exists:users,id',
            'hemocentro_id'  => 'required|exists:hemocentros,id',
            'data_triagem'   => 'required|date',
            'apto'           => 'required|boolean',

            'motivo_inaptidao' => 'required_if:apto,false|nullable|string',
            'observacoes' => 'nullable|string',
       ];

       $validator = Validator::make($request->all(), $rules);
       if ($validator->fails()) {
            return response()->json([ $validator->errors ()], 422);
       }
       


    $triagem = Triagem::create([
            'user_id' => $request->user_id,
            'funcionario_id' => $request->funcionario_id,
            'hemocentro_id' => $request->hemocentro_id,
            'data_triagem' => $request->data_triagem,
            'status_triagem' => 'C', // Definimos como Concluída por padrão ao criar
            'apto' => $request->apto,
            'motivo_inaptidao' => $request->motivo_inaptidao,
            'observacoes' => $request->observacoes,
        ]);

        return response()->json([
            'message' => 'Triagem realizada com sucesso!',
            'data' => $triagem
        ], 201);
    }

    public function show($id)
    {
        $triagem = Triagem::with(['doador', 'funcionario', 'hemocentro'])->find($id);

        if (!$triagem || $triagem->status_triagem == 'E') {
            return response()->json(['message' => 'Triagem não encontrada.'], 404);
        }

        return response()->json($triagem);
    }


    public function update(Request $request, $id)
    {
        $triagem = Triagem::findOrFail($id);

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
        
        $triagem->status_triagem = 'E';
        $triagem->save();

        return response()->json(['message' => 'Triagem removida do sistema.']);
    }


}


