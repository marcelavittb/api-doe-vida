<?php

namespace Database\Seeders;

use App\Models\Hemocentro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HemocentroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Hemocentro::create([
            'nome'          =>'Hemepar Curitiba',           
            'telefone'      =>'(41) 3281-4000',
            'email'         =>'hemeparcuritiba@gmail.com'      ,
            'bairro'        =>'Alto da XV',
            'uf'            => 'PR',
            'endereco'      => 'Rua XV de Novembro',
            'cidade'        => 'Curitiba',
            'numero'        => '200',          
            'razao_social'       =>'Centro de Hematologia e Hemoterapia do Parana',
            'cnpj'               =>'56904583000130',
            'status_agendamento' => 'ativo',
            'status'             => '1'
        ]);

        Hemocentro::create([
            'nome'          =>'Hemocentro hospital de clinicas',           
            'telefone'      =>'(41) 3281-1020',
            'email'         =>'hemocentrogc@gmail.com'      ,
            'bairro'        =>'Centro',
            'uf'            => 'PR',
            'endereco'      => 'João Prodoscimo',
            'cidade'        => 'Curitiba',
            'numero'        => '18',          
            'razao_social'       =>'Hemocentro dos Hospitais de Clínicas',
            'cnpj'               =>'569045830010395',
            'status_agendamento' => 'ativo',
            'status'             => '1'
        ]);
    }
}
