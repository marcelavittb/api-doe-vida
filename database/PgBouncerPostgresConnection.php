<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection;

/**
 * Conexao PostgreSQL adaptada para o pooler do Supabase (PgBouncer/Supavisor).
 *
 * Contexto do problema:
 * O pooler exige PDO::ATTR_EMULATE_PREPARES => true. Com essa opcao ligada, o
 * PDO serializa um booleano PHP (true/false) como o inteiro 1/0 ao montar o SQL.
 * O PostgreSQL e estritamente tipado e recusa gravar um integer numa coluna
 * boolean, lancando: "column X is of type boolean but expression is of type
 * integer" (SQLSTATE 42804).
 *
 * Solucao:
 * Sobrescrevemos prepareBindings para converter qualquer binding booleano no
 * literal de texto que o PostgreSQL aceita nativamente ('true'/'false') antes
 * de a query ser executada. Assim os models, casts e mutators continuam usando
 * booleanos PHP normalmente, sem precisar de DB::raw espalhado pelo codigo.
 */
class PgBouncerPostgresConnection extends PostgresConnection
{
    /**
     * Prepara os bindings da query para execucao.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $bindings = parent::prepareBindings($bindings);

        foreach ($bindings as $key => $value) {
            if (is_bool($value)) {
                $bindings[$key] = $value ? 'true' : 'false';
            }
        }

        return $bindings;
    }
}