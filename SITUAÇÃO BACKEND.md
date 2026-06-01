# Situação Atual do Backend - DoaVida

Este documento resume as regras de negócio, fluxos de integração e a documentação das APIs implementadas para o ecossistema DoaVida.

## 1. Painel do Diretor e Estatísticas (NOVO)

O backend agora fornece indicadores reais e calculados para a tomada de decisão gerencial.

### Indicadores Implementados:
- **Crescimento Mensal (`crescimento_mes`)**: Cálculo percentual comparando o volume de doações do mês atual com o mês anterior.
- **Taxa de Comparecimento (`taxa_comparecimento`)**: Relação entre agendamentos confirmados (`CON`) e o total de agendamentos do dia atual.
- **Média Diária (`media_diaria`)**: Média de doações realizadas por dia decorrido no mês atual.
- **Estoque Crítico**: Listagem automática de tipos sanguíneos que estão abaixo do nível mínimo de segurança.

### Identificação da Unidade:
- O objeto `user` retornado no **Login** e no **/me** agora inclui o relacionamento `hemocentro` populado. Isso permite que o Diretor e o Funcionário vejam imediatamente o nome e os dados da unidade em que estão alocados.

---

## 2. Fluxo de Elegibilidade (Autoexame)

O backend exige que o doador tenha um teste de elegibilidade válido para permitir agendamentos.

### Regras:
- **Validade:** O teste tem validade de **7 dias**.
- **Endpoints:** 
    - `POST /api/auth/elegibilidade`: Salva o resultado.
    - `GET /api/auth/elegibilidade/atual`: Verifica se o usuário está apto e dentro do prazo.

---

## 3. LGPD e Cadastro Unificado

Garantia de conformidade legal e portabilidade de dados.

- **Registro:** O `POST /api/auth/register` agora aceita o aceite da LGPD e as respostas de pré-triagem simultaneamente.
- **Anonimização:** O `DELETE /api/auth/minha-conta` anonimiza os dados conforme o Art. 18 da LGPD, preservando a integridade histórica das doações (sem dados pessoais).
- **Portabilidade:** `GET /api/auth/meus-dados` exporta todo o histórico do doador em formato JSON.

---

## 4. Triagem Clínica e Doação

Fluxo padronizado para garantir a segurança do sangue coletado.
- **CON (Confirmado):** Doador presente, aguardando triagem.
- **FIN (Finalizado):** Status automático gerado após o sucesso do `POST /api/doacoes`.

---

## 5. Resumo de Endpoints Relevantes

```http
// Dashboards (Estatísticas Reais)
GET /api/estatisticas/diretor      // Dados gerenciais para diretores
GET /api/estatisticas/funcionario  // Dados operacionais para staff
GET /api/estatisticas/admin        // Visão global do sistema

// Gestão de Conta e LGPD
GET    /api/auth/me                // Agora inclui "hemocentro": {...}
GET    /api/auth/meus-dados        // Portabilidade de dados
DELETE /api/auth/minha-conta       // Exclusão/Anonimização LGPD

// Certificados
GET /api/certificados              // Lista doações elegíveis
GET /api/certificados/{id}/pdf      // Download do certificado oficial
```

---
**Status:** Sincronizado com a demanda "Painel do Diretor" de 21/05/2026.
