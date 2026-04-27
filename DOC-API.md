# DOC-API

## Visão geral
API construída com Laravel usando rotas em `routes/api.php`.
Autenticação com `sanctum` em endpoints específicos.

## Prefixo padrão
Os caminhos abaixo são relativos ao prefixo padrão `/api` do Laravel.

---

## Autenticação (Parcialmente em Supabase)

**Nota:** As funcionalidades de login, cadastro, e recuperação de senha estão implementadas via **Supabase Edge Functions**, não diretamente nas rotas Laravel abaixo. A documentação a seguir descreve os endpoints esperados.

### POST /api/auth/register
- **Controller**: `AuthController@register`
- **Utilizado em**: `RegistrationDonationPage.tsx`
- **Body JSON esperado**:
  - `name`: string, obrigatório
  - `email`: email válido, obrigatório, único
  - `password`: string, obrigatório, min 6
  - `password_confirmation`: deve ser igual a `password`
  - `cpf`: string de 11 dígitos, obrigatório, único
  - `telefone`: opcional, formato `(XX) 9XXXX-XXXX`
  - `tipo_sang`: opcional, valores `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`
  - `sexo`: obrigatório, valores `M`, `F`, `Outro`, `Prefiro não informar`
  - `data_nasc`: obrigatório, formato `d/m/Y`
  - `cep`: obrigatório, formato `99999-999`
  - `rua`: obrigatório
  - `numero`: obrigatório
  - `bairro`: opcional
  - `cidade`: obrigatório
  - `uf`: opcional, sigla BR válida
  - `responsavel_nome`: requerido se menor de 18
  - `responsavel_cpf`: requerido se menor de 18, tamanho 11
  - `responsavel_data_nasc`: requerido se menor de 18, formato `d/m/Y`
- **Regras especiais**:
  - **Vínculo**: Doador não tem hemocentro fixo. O campo `hemocentro_id` foi removido deste endpoint.
  - **Perfil**: O sistema define automaticamente `role_id = 1` (Doador).
  - Menor de 18 precisa de responsável.
  - `cpf` e `responsavel_cpf` passam por validação de dígitos.

### POST /api/auth/login
- **Controller**: `AuthController@login`
- **Utilizado em**: `LoginPage.tsx`, `RegistrationDonationPage.tsx`
- **Body JSON esperado**:
  - `email`: email válido, obrigatório
  - `password`: string, obrigatório, min 6

### GET /api/auth/me
- **Middleware**: `auth:sanctum`
- **Utilizado em**: `AuthContext.tsx`
- **Retorna usuário autenticado**

### POST /api/auth/forgot-password
- **Implementação**: Supabase Edge Function
- **Body JSON**: `{"email": "..."}`
- **Comportamento**: Gera código de 6 dígitos e envia por e-mail.

---

## Usuários (Gestão Administrativa)

### POST /api/auth/users
- **Controller**: `UserController@store`
- **Middleware**: `auth:sanctum`
- **Body JSON esperado**:
  - `name`: obrigatório
  - `email`: obrigatório, único
  - `password`: obrigatório, min 6
  - `cpf`: obrigatório, único, 11 dígitos
  - `role_id`: obrigatório (1=Doador, 2=Funcionário, 3=Diretor)
  - `hemocentro_id`: **Obrigatório se role_id for 2 ou 3**. Ignorado se role_id for 1.
- **Validações**: CPF real, unicidade de e-mail/cpf.

### GET /api/users
- **Controller**: `UserController@index`
- **Retorna todos os usuários.**

### GET /api/users/{id}
- **Controller**: `UserController@show`

### PUT /api/users/{id}
- **Controller**: `UserController@update`
- **Regras de Vínculo**: Se alterar o cargo para Funcionário/Diretor, exige `hemocentro_id`. Se o cargo for Doador, o `hemocentro_id` é limpo (`NULL`).

### DELETE /api/users/{id}
- **Controller**: `UserController@destroy`
- **Comportamento**: Soft delete (status = 0).

---

## Hemocentros

### POST /api/auth/hemocentros
- **Controller**: `HemocentroController@store`
- **Middleware**: `auth:sanctum`
- **Body JSON esperado**:
  - `nome`, `telefone`, `email`, `bairro`, `uf`, `endereco`, `cidade`, `numero`, `razao_social`, `cnpj`, `status_agendamento`, `status`.

### GET /api/hemocentros
- **Retorna lista de hemocentros ativos.**

### GET /api/hemocentros/{hemocentro}
- **Exibe detalhes de um hemocentro específico.**

---

## Agendamentos

### POST /api/auth/agendamentos
- **Controller**: `AgendamentoController@store`
- **Middleware**: `auth:sanctum`
- **Body JSON esperado**:
  - `hemocentro_id`: obrigatório (Onde a doação será realizada)
  - `data_hora_doacao`: obrigatório, data futura
- **Regras**: Verifica restrição biológica, inativa agendamentos antigos.

### GET /api/agendamentos
- **Retorna agendamentos do usuário autenticado.**

---

## Triagens

### POST /api/auth/triagens
- **Controller**: `TriagemController@store`
- **Middleware**: `auth:sanctum`
- **Body JSON esperado**:
  - `user_id`, `funcionario_id`, `hemocentro_id`, `data_triagem`, `apto` (boolean), `motivo_inaptidao`, `observacoes`.

### GET /api/triagens
- **Lista triagens ativas (status != 'E').**

### PUT /api/auth/triagens/{id}
- **Controller**: `TriagemController@update`
- **Body**: `apto`, `motivo_inaptidao`, `observacoes`, `status_triagem` (P, C, E).

---

## Estrutura de Tabelas (Triagem)

### triagem_perguntas
- `id`, `pergunta`, `obrigatoria`, `status`.

### triagem_opcoes
- `pergunta_id`, `texto_opcao`, `gera_inaptidao`, `dias_inaptidao`.

### triagens
- `user_id`, `funcionario_id`, `hemocentro_id`, `data_triagem`, `status_triagem` (P, C, E), `apto`, `motivo_inaptidao`.

---

## Observações Gerais
- **Doador**: Não possui hemocentro fixo no perfil. Escolhe no agendamento.
- **Staff**: Obrigatoriamente vinculado a um hemocentro.
- **Datas**: Formato brasileiro `d/m/Y` no input, salvo como `Y-m-d`.
- **Telefones**: Máscara `(XX) XXXXX-XXXX`.