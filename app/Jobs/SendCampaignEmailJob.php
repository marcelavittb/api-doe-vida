<?php

namespace App\Jobs;

use App\Models\Campanha;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCampaignEmailJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public int $campanhaId,
        public int $doadorId,
    ) {
        $this->onQueue('campaign-emails');
    }

    public function handle(): void
    {
        $campanha = Campanha::find($this->campanhaId);
        $doador = User::find($this->doadorId);

        if (! $campanha || ! $doador || ! $doador->email) {
            Log::warning('JOB CAMPANHA IGNORADO', [
                'campanha_id' => $this->campanhaId,
                'doador_id' => $this->doadorId,
            ]);

            return;
        }

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        Mail::send(
            ['html' => 'emails.campanha', 'text' => 'emails.campanha-text'],
            [
                'campanha' => $campanha,
                'doador' => $doador,
                'ctaUrl' => "{$frontendUrl}/login",
                'preheader' => $campanha->subtitulo ?: 'Sua doacao pode ajudar a manter os estoques de sangue seguros.',
                'bloodType' => $campanha->tipo_sangue ?: null,
                'publishDate' => optional($campanha->data_publi)->format('d/m/Y'),
                'expireDate' => optional($campanha->data_expiracao)->format('d/m/Y'),
            ],
            function ($message) use ($doador, $campanha) {
                $message->to($doador->email)
                    ->subject($campanha->titulo);
            }
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('FALHA AO ENVIAR EMAIL DA CAMPANHA', [
            'campanha_id' => $this->campanhaId,
            'doador_id' => $this->doadorId,
            'erro' => $e->getMessage(),
        ]);
    }
}
