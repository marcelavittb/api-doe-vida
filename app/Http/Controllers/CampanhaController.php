<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCampaignDispatchJob;
use App\Models\Campanha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CampanhaController extends Controller
{
    // GET /api/campanhas
    public function index(Request $request)
    {
        $campanhas = Campanha::with('hemocentro')
            ->orderBy('criado_em', 'desc')
            ->get();

        return response()->json([
            'status' => 'sucesso',
            'data' => $campanhas,
        ]);
    }

    // GET /api/campanhas/{id}
    public function show($id)
    {
        $campanha = Campanha::with('hemocentro', 'criador')->findOrFail($id);

        return response()->json(['status' => 'sucesso', 'data' => $campanha]);
    }

    // POST /api/auth/campanhas
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'subtitulo' => 'nullable|string|max:255',
                'descricao' => 'nullable|string|max:255',
                'tipo_sangue' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'hemocentro_id' => 'nullable|exists:hemocentros,id',
                'data_publi' => 'required|date',
                'data_expiracao' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            [$dataPubli, $dataExpiracao, $dateErrors] = $this->validateAndNormalizeCampaignDates(
                $request->input('data_publi'),
                $request->input('data_expiracao')
            );

            if ($dateErrors !== []) {
                return response()->json($dateErrors, 422);
            }

            $campanhaId = DB::table('campanhas')->insertGetId([
                'titulo' => $request->titulo,
                'subtitulo' => $request->subtitulo,
                'descricao' => $request->descricao,
                'tipo_sangue' => $request->tipo_sangue,
                'hemocentro_id' => $request->hemocentro_id,
                'data_publi' => $dataPubli,
                'data_expiracao' => $dataExpiracao,
                'status' => DB::raw('true'),
                'criado_por' => Auth::id(),
                'total_disparado' => 0,
                'total_aberto' => 0,
                'criado_em' => now(),
                'atualizado_em' => now(),
            ]);

            $campanha = Campanha::with('hemocentro')->findOrFail($campanhaId);

            Log::info('CAMPANHA CRIADA', [
                'campanha_id' => $campanha->id,
                'titulo' => $campanha->titulo,
                'criado_por' => Auth::id(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Campanha criada com sucesso!',
                'data' => $campanha,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao criar campanha.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // PUT /api/auth/campanhas/{id}
    public function update(Request $request, $id)
    {
        try {
            $campanha = Campanha::findOrFail($id);

            Log::info('Atualizacao de campanha', [
                'id' => $id,
                'dados' => $request->all(),
            ]);

            $validator = Validator::make($request->all(), [
                'titulo' => 'sometimes|string|max:255',
                'subtitulo' => 'nullable|string|max:255',
                'descricao' => 'nullable|string|max:255',
                'tipo_sangue' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'hemocentro_id' => 'nullable|exists:hemocentros,id',
                'data_publi' => 'sometimes|date',
                'data_expiracao' => 'nullable|date',
                'status' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $payload = $request->only([
                'titulo', 'subtitulo', 'descricao',
                'tipo_sangue', 'hemocentro_id', 'data_publi', 'data_expiracao',
            ]);

            if ($request->has('data_publi') || $request->has('data_expiracao')) {
                [$dataPubli, $dataExpiracao, $dateErrors] = $this->validateAndNormalizeCampaignDates(
                    $request->input('data_publi', $campanha->data_publi?->format('Y-m-d H:i:s')),
                    $request->input('data_expiracao'),
                    $campanha->data_publi?->format('Y-m-d H:i:s')
                );

                if ($dateErrors !== []) {
                    return response()->json([
                        'success' => false,
                        'errors' => $dateErrors,
                    ], 422);
                }

                if ($request->has('data_publi')) {
                    $payload['data_publi'] = $dataPubli;
                }

                $payload['data_expiracao'] = $dataExpiracao;
            }

            $payload['atualizado_em'] = now();

            DB::table('campanhas')
                ->where('id', $campanha->id)
                ->update($payload);

            if ($request->has('status')) {
                DB::table('campanhas')
                    ->where('id', $campanha->id)
                    ->update([
                        'status' => DB::raw($request->boolean('status') ? 'true' : 'false'),
                        'atualizado_em' => now(),
                    ]);
            }

            return response()->json([
                'message' => 'Campanha atualizada!',
                'data' => $campanha->fresh('hemocentro'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao atualizar campanha.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // DELETE /api/auth/campanhas/{id}
    public function destroy($id)
    {
        try {
            $campanha = Campanha::findOrFail($id);

            DB::table('campanhas')
                ->where('id', $campanha->id)
                ->update([
                    'status' => DB::raw('false'),
                    'deletado_em' => now(),
                    'atualizado_em' => now(),
                ]);

            return response()->json(['message' => 'Campanha removida!']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao remover campanha.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // POST /api/auth/campanhas/{id}/disparar
    public function disparar($id)
    {
        try {
            $campanha = Campanha::findOrFail($id);
            $this->garantirEstruturaDisparoCampanha();
            ProcessCampaignDispatchJob::dispatch($campanha->id, Auth::id());

            Log::info('CAMPANHA ENFILEIRADA PARA PROCESSAMENTO', [
                'campanha_id' => $campanha->id,
                'titulo' => $campanha->titulo,
                'disparado_por' => Auth::id(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'campanha_id' => $campanha->id,
                'message' => 'Campanha enviada para processamento.',
                'modo_envio' => 'queue',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao disparar campanha.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function garantirEstruturaDisparoCampanha(): void
    {
        foreach (['campanhas', 'users', 'jobs'] as $tabela) {
            if (!Schema::hasTable($tabela)) {
                throw new \RuntimeException("Tabela obrigatoria ausente: {$tabela}.");
            }
        }
    }

    private function validateAndNormalizeCampaignDates(?string $dataPubli, ?string $dataExpiracao, ?string $dataPubliAtual = null): array
    {
        $errors = [];

        $dataPubliNormalizada = $this->normalizeCampaignDate($dataPubli, 'data_publi', $errors);
        $dataExpiracaoNormalizada = $this->normalizeCampaignDate($dataExpiracao, 'data_expiracao', $errors, true);

        $hoje = new \DateTimeImmutable('today');

        if ($dataPubliNormalizada) {
            $dataPubliDia = new \DateTimeImmutable(substr($dataPubliNormalizada, 0, 10));
            $dataPubliAtualNormalizada = null;

            if ($dataPubliAtual) {
                try {
                    $dataPubliAtualNormalizada = (new \DateTimeImmutable($dataPubliAtual))->format('Y-m-d');
                } catch (\Throwable) {
                    $dataPubliAtualNormalizada = null;
                }
            }

            if ($dataPubliDia < $hoje && $dataPubliDia->format('Y-m-d') !== $dataPubliAtualNormalizada) {
                $errors['data_publi'] = ['A data de publicacao nao pode ser anterior a hoje.'];
            }
        }

        if ($dataExpiracaoNormalizada) {
            $dataExpiracaoDia = new \DateTimeImmutable(substr($dataExpiracaoNormalizada, 0, 10));

            if ($dataExpiracaoDia < $hoje) {
                $errors['data_expiracao'] = ['A data de expiracao nao pode ser anterior a hoje.'];
            }
        }

        if ($dataPubliNormalizada && $dataExpiracaoNormalizada) {
            $dataPubliDia = new \DateTimeImmutable(substr($dataPubliNormalizada, 0, 10));
            $dataExpiracaoDia = new \DateTimeImmutable(substr($dataExpiracaoNormalizada, 0, 10));

            if ($dataExpiracaoDia <= $dataPubliDia && ! isset($errors['data_expiracao'])) {
                $errors['data_expiracao'] = ['A data de expiracao deve ser posterior a data de publicacao.'];
            }
        }

        return [$dataPubliNormalizada, $dataExpiracaoNormalizada, $errors];
    }

    private function normalizeCampaignDate(?string $value, string $field, array &$errors, bool $nullable = false): ?string
    {
        if ($value === null || trim($value) === '') {
            if ($nullable) {
                return null;
            }

            $errors[$field] = ['O campo data Ã© obrigatÃ³rio.'];

            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $value)) {
            try {
                return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                $errors[$field] = ['Data invalida. Use um formato de data valido.'];

                return null;
            }
        }

        if (! preg_match('/^(\d{4,})-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/', $value, $matches)) {
            $errors[$field] = ['Data invalida. Use um formato de data valido.'];

            return null;
        }

        if (strlen($matches[1]) !== 4) {
            $errors[$field] = ['O ano informado deve estar entre 2000 e 2100.'];

            return null;
        }

        $year = (int) $matches[1];

        if ($year < 2000 || $year > 2100) {
            $errors[$field] = ['O ano informado deve estar entre 2000 e 2100.'];

            return null;
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d\TH:i', 'Y-m-d'];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $dateErrors = \DateTimeImmutable::getLastErrors();

            if (
                $date instanceof \DateTimeImmutable
                && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
            ) {
                if ($format === 'Y-m-d') {
                    $date = $date->setTime(0, 0, 0);
                }

                return $date->format('Y-m-d H:i:s');
            }
        }

        $errors[$field] = ['Data invalida. Use um formato de data valido.'];

        return null;
    }
}
