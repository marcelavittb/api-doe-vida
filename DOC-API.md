# DOC-API

## Visão geral
API construída com Laravel usando rotas em `routes/api.php`.
Autenticação com `sanctum` em endpoints específicos.

## Prefixo padrão
Os caminhos abaixo são relativos ao prefixo padrão `/api` do Laravel.

---

## Autenticação

### POST /api/auth/register
- **Controller**: `AuthController@register`
- **Body JSON esperado**: `name`, `email`, `password`, `cpf`, `sexo`, `data_nasc`, `cep`, `rua`, `numero`, `cidade`, etc.
- **Perfil**: O sistema define automaticamente `role_id = 1` (Doador).

### POST /api/auth/login
- **Controller**: `AuthController@login`
- **Body JSON esperado**: `email`, `password`.
- **Retorna**: Token de acesso Sanctum.

### GET /api/auth/me
- **Middleware**: `auth:sanctum`
- **Retorna usuário autenticado e seus papéis (roles)**.

---

## Usuários (Gestão Administrativa)

### POST /api/auth/users
- **Middleware**: `auth:sanctum`
- **Body**: `name`, `email`, `password`, `cpf`, `role_id`, `hemocentro_id`.
- **Regra**: `hemocentro_id` é obrigatório para funcionários (2) e diretores (3).

---

## Hemocentros

### GET /api/hemocentros
- **Retorna lista de hemocentros.**

### GET /api/hemocentros/{id}
- **Exibe detalhes de um hemocentro específico.**

---

## Agendamentos

### GET /api/agendamentos
- **Filtro**: 
  - **Doador**: Vê apenas seus agendamentos ativos (`AGE`) ou confirmados (`CON`).
  - **Funcionário**: Vê todos os agendamentos do seu hemocentro vinculado.

### GET /api/agendamentos/historico
- **Controller**: `AgendamentoController@historico`
- **Doador**: Retorna todos os agendamentos já feitos pelo usuário (incluindo cancelados e excluídos).

### POST /api/auth/agendamentos
- **Body**: `hemocentro_id`, `data_hora_doacao`.
- **Regras**: Valida restrição de dias (90/120), idade (16-18 requer alerta) e inativa agendamentos pendentes anteriores.

### POST /api/auth/agendamentos/{id}/confirmar
- **Ação**: Muda o status para `CON`. 
- **Público**: Doador (confirmação de ida) ou Funcionário (registro de presença).

### POST /api/auth/agendamentos/{id}/cancelar
- **Ação**: Muda o status para `CAN`.
- **Público**: Doador ou Funcionário.

---

## Triagens

### GET /api/triagens
- **Filtro**: Doador vê as suas; Funcionário vê as do seu hemocentro.

### POST /api/auth/triagens
- **Ação**: Efetiva a triagem de um doador.
- **Body**: `user_id`, `hemocentro_id`, `data_triagem`, `apto` (bool), `motivo_inaptidao`, `observacoes`.
- **Status inicial**: `C` (Concluída).

### DELETE /api/auth/triagens/{id}
- **Ação**: Muda o status para `E` (Excluída).

---

## Doações

### GET /api/doacoes
- **Filtro**: Doador vê seu histórico; Funcionário vê as doações do hemocentro.

### POST /api/auth/doacoes
- **Controller**: `DoacaoController@store`
- **Body JSON esperado**:
  - `user_id`: ID do doador.
  - `hemocentro_id`: ID do hemocentro.
  - `data_hora_doacao`: Data e hora da coleta.
  - `tipo_sangue`: `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`.
  - `quantidade`: Volume em ml.
  - `data_validade_sangue`: Data de validade da bolsa.
- **Regra**: O `funcionario_id` é preenchido automaticamente com o usuário logado.

---

## Status e Enums

### Status Agendamento
- `AGE`: Agendado (Pendente)
- `CON`: Confirmado
- `CAN`: Cancelado
- `EXC`: Excluído (por reagendamento)

### Status Triagem
- `P`: Pendente
- `C`: Concluída
- `E`: Excluída

---

## Observações Gerais
- **Segurança**: Rotas sob `/auth/` exigem token Sanctum.
- **Hierarquia**: O fluxo ideal é Agendamento -> Triagem -> Doação.
