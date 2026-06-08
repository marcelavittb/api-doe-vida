<?php

namespace App\Providers;

use App\Database\PgBouncerPostgresConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Usa uma conexao PostgreSQL customizada que converte bindings booleanos
        // para os literais 'true'/'false'. Necessario por causa do pooler do
        // Supabase (PgBouncer) com EMULATE_PREPARES, que serializa bool como 1/0
        // e faz o PostgreSQL recusar colunas boolean. Resolve o problema na raiz,
        // sem precisar de DB::raw em cada query/insert.
        Connection::resolverFor('pgsql', function ($connection, $database, $prefix, $config) {
            return new PgBouncerPostgresConnection($connection, $database, $prefix, $config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registra o transport do Brevo (envio via API HTTP, porta 443).
        // Isso permite enviar e-mails contornando o bloqueio de SMTP (porta 587)
        // do Railway. Ativado definindo MAIL_MAILER=brevo e BREVO_API_KEY no .env.
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn(
                    'brevo+api',
                    'default',
                    config('services.brevo.key')
                )
            );
        });
    }
}