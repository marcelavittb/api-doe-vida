# Doe Vida API - Documentação Completa

## 📋 Resumo dos Arquivos MD Criados

### Arquivos Existentes:
1. **`README.md`** (este arquivo) - Documentação completa unificada
2. **`DOC-API.md`** - Documentação técnica detalhada dos endpoints

### O que foi Adicionado/Mudado:

#### ✅ **DOC-API.md** (Criado):
- **Documentação técnica completa** dos endpoints
- **Parâmetros esperados** por cada rota (body JSON, path params)
- **Controllers responsáveis** e middlewares
- **Regras especiais** de validação (CPF, responsáveis, restrições)
- **Comportamentos específicos** (soft delete, status changes)

#### 🔄 **README.md** (Unificado):
- **Mapeamento funcional** das telas do front-end
- **Fluxos de usuário** por perfil (Doador, Funcionário, Diretor)
- **Integração API** com detalhes técnicos
- **Regras de negócio** e pendências futuras

---

## 🗺️ Rotas da API (`routes/api.php`)

Todas as rotas da API utilizam o prefixo padrão `/api` (fornecido pelo Laravel).
As rotas que exigem autenticação requerem o envio do token Bearer via header (Sanctum).

### 🔐 Autenticação
- `POST /api/auth/register`: Criação de um novo usuário.
- `POST /api/auth/login`: Realiza o login e retorna o token de acesso.
- `GET /api/auth/me`: Retorna os dados do usuário autenticado atual. *(Requer Autenticação)*

### 👥 Usuários
- `POST /api/auth/users`: Criação de um novo usuário por um administrador/funcionário. *(Requer Autenticação)*
- `GET /api/users`: Lista os usuários.
- `GET /api/users/{id}`: Exibe os detalhes de um usuário específico.
- `PUT /api/users/{id}`: Atualiza os dados de um usuário.
- `DELETE /api/users/{id}`: Remove um usuário do sistema.

### 🏥 Hemocentros
- `GET /api/hemocentros`: Lista todos os hemocentros.
- `GET /api/hemocentros/{hemocentro}`: Exibe os detalhes de um hemocentro específico.
- `POST /api/auth/hemocentros`: Cria um novo hemocentro. *(Requer Autenticação)*
- `PUT /api/auth/hemocentros/{hemocentro}`: Atualiza os dados de um hemocentro. *(Requer Autenticação)*
- `DELETE /api/auth/hemocentros/{hemocentro}`: Remove um hemocentro. *(Requer Autenticação)*

### 📅 Agendamentos
- `POST /api/auth/agendamentos`: Cria um novo agendamento. *(Requer Autenticação)*
- `GET /api/agendamentos`: Lista os agendamentos. *(Requer Autenticação)*

---

## 🖥️ Telas e Integrações (Front-end)

### 1. Cadastro Geral
- **Telas "Cadastre-se" e "Dados Pessoais"**:
  - Utilizam `POST /api/auth/register`.
  - Campos a incluir: Senha, confirmação de senha, campos do responsável (nome, CPF, data de nascimento).
- **Esqueci a Senha**: API externa para envio de e-mail com a senha ou link de recuperação.

### 2. Doador (`/dashboard/donor`)
- **Dashboard**: Consumir e exibir dados das doações do usuário.
- **Agendamento**:
  - Agendar doação (`POST /api/auth/agendamentos`).
  - Confirmar Agendamento.
- **Perfil**:
  - Editar informações (endereço, cidade), senha, avatar.
  - Consome `PUT /api/users/{id}`.
- **Regras de Negócio**:
  - Banco de Dados precisa de coluna (Tempo de Restrição / Tempo de Doação) para validar período de próxima disponibilidade.

### 3. Funcionário (`/dashboard/staff`)
- **Dashboard**: Consumir do banco através de consultas (estatísticas, etc).
- **Agendamentos**:
  - Confirmar/cancelar agendamentos (Consumindo as rotas de Agendamentos).
  - Modal de Doação (tipo sanguíneo do paciente, aptidão, status, ml, bolsas).
  - Cancelamento (informar motivo: não comparecimento, inaptidão na triagem/entrevista, não elegível).
- **Estoque**: Atualizar estoque e somar ml de acordo com as bolsas, informando descrição do processo.
- **Busca de Doador**: Buscar somente doadores do *seu* hemocentro. Pode alterar tipo sanguíneo, telefone, hemocentro, inativar ou restringir usuário (`PUT /api/users/{id}`).
- **Perfil**: Editar o próprio cadastro (`PUT /api/users/{id}`).

### 4. Diretor (`/dashboard/director`)
- **Dashboard**:
  - Remover Dashboard de Satisfação (se não usado).
  - Revisar estatísticas da equipe.
- **Gestão de Funcionários**:
  - Criar funcionário com cargo (senha, endereço, id do hemocentro fixo, telefone, etc) (`POST /api/auth/users`).
  - Editar informações do funcionário (nome, cargo, telefone, status, permissões) (`PUT /api/users/{id}`).
  - Adicionar coluna/funcionalidade de criar e editar permissões usando Spatie Permissions.
- **Estoque e Relatórios (Exportação em PDF)**:
  - Relação de doações realizadas no dia/semana/mês por tipo sanguíneo.
  - Relatório Mensal: Gráfico de Torre/Linha em modal.
  - Relatório de Doadores: Ativos, inaptos (e motivos), concluídas.
  - Relatório de Estoque: Torre/Linha com possibilidade de filtrar os tipos.
  - Relatório de Desempenho: Cadastros, atendimentos, satisfação.

### 5. Administrador
- **Gestão Global**:
  - Cria campanhas e designa hemocentros para elas.
  - Edita hemocentros e seus status (`PUT /api/auth/hemocentros/{hemocentro}`).
  - Pode criar doadores e editar/alterar permissões de usuários globais.

### 6. Hemocentro (Configurações)
- **Detalhes**:
  - Horário de funcionamento e datas disponíveis para agendar.
  - Limite de doações por dia e máximo de estoque (definido pelo admin).
  - Cadastrar novo hemocentro (`POST /api/auth/hemocentros`) adicionando campos específicos.
  - Adicionar responsável pelo hemocentro.

### 7. Pendências (Próximas Reuniões)
- **Agendamento (Reagendar)**:
  - Ao invés de alterar o registro original, mudar o status do agendamento atual para "E" (Excluído/Cancelado) e criar um novo agendamento.

---

## 📖 Documentação Técnica Detalhada

### Autenticação

#### POST /api/auth/register
- **Controller**: `AuthController@register`
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
  - `hemocentro_id`: obrigatório, existe em `hemocentros` e `status = 1`
  - `responsavel_nome`: requerido se menor de 18
  - `responsavel_cpf`: requerido se menor de 18, tamanho 11
  - `responsavel_data_nasc`: requerido se menor de 18, formato `d/m/Y`
- **Regras especiais**:
  - menor de 18 precisa de responsável
  - `cpf` e `responsavel_cpf` passam por validação de dígitos

#### POST /api/auth/login
- **Controller**: `AuthController@login`
- **Body JSON esperado**:
  - `email`: email válido, obrigatório
  - `password`: string, obrigatório, min 6

#### GET /api/auth/me
- **Middleware**: `auth:sanctum`
- **Sem body**
- **Retorna usuário autenticado**

### Usuários

#### POST /api/auth/users
- **Controller**: `UserController@store`
- **Middleware**: `auth:sanctum`
- **Body JSON esperado**:
  - `name`: obrigatório
  - `email`: email válido, obrigatório, único
  - `password`: obrigatório, min 6
  - `cpf`: obrigatório, único
- **Valida CPF real**

#### GET /api/users
- **Controller**: `UserController@index`
- **Sem parâmetros**

#### GET /api/users/{id}
- **Controller**: `UserController@show`
- **Path param**: `id`

#### PUT /api/users/{id}
- **Controller**: `UserController@update`
- **Path param**: `id`
- **Body JSON aceito**:
  - `password`: min 6
  - `name`: string
  - `email`: email válido, único exceto o próprio usuário
  - `hemocentro_id`: deve existir em `hemocentros`
  - `cpf`: único exceto o próprio usuário
  - `status`: boolean
  - `tipo_sang`: `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`
  - `sexo`: `M`, `F`
  - `telefone`: string

#### DELETE /api/users/{id}
- **Controller**: `UserController@destroy`
- **Path param**: `id`
- **Não exclui fisicamente**: define `status = 0`

### Hemocentros

#### POST /api/auth/hemocentros
- **Controller**: `HemocentroController@store`
- **Middleware**: `auth:sanctum`
- **Body JSON esperado**:
  - `nome`: obrigatório
  - `telefone`: obrigatório, formato `(XX) XXXX-XXXX` ou `(XX) XXXXX-XXXX`
  - `email`: email válido, obrigatório, único
  - `bairro`: obrigatório
  - `uf`: obrigatório, 2 caracteres
  - `endereco`: obrigatório
  - `cidade`: obrigatório
  - `numero`: obrigatório, integer
  - `complemento`: opcional
  - `razao_social`: obrigatório
  - `cnpj`: obrigatório, único
  - `status_agendamento`: obrigatório, `ativo` ou `inativo`
  - `status`: obrigatório, `0` ou `1`
  - `criado_por`: opcional, default `usuario_teste_12`

#### GET /api/hemocentros
- **Controller**: `HemocentroController@index`
- **Sem parâmetros**

#### GET /api/hemocentros/{hemocentro}
- **Controller**: `HemocentroController@show`
- **Path param**: `hemocentro`

#### PUT /api/auth/hemocentros/{hemocentro}
- **Controller**: `HemocentroController@update`
- **Middleware**: `auth:sanctum`
- **Path param**: `hemocentro`
- **Body**: aceita `request->all()` sem validação específica no controller

#### DELETE /api/auth/hemocentros/{hemocentro}
- **Controller**: `HemocentroController@destroy`
- **Middleware**: `auth:sanctum`
- **Path param**: `hemocentro`
- **Marca o hemocentro como inativo e faz soft delete**

### Agendamentos

#### POST /api/auth/agendamentos
- **Controller**: `AgendamentoController@store`
- **Middleware**: `auth:sanctum`
- **Body JSON esperado**:
  - `hemocentro_id`: obrigatório, existe em `hemocentros` e `status_agendamento = ativo`
  - `data_hora_doacao`: obrigatório, data futura
- **Regras adicionais**:
  - inativa agendamentos futuros antigos do mesmo usuário
  - verifica restrição biológica pelo campo `tempo_restricao`
  - alerta se idade entre 16 e 17

#### GET /api/agendamentos
- **Controller**: `AgendamentoController@index`
- **Middleware**: `auth:sanctum`
- **Sem body**
- **Retorna agendamentos do usuário autenticado com `status_agendamento` em `AGE` ou `CON`**

---

## ⚙️ Configurações Técnicas

### Autenticação
- **Laravel Sanctum** para autenticação stateless
- **Token Bearer** no header `Authorization`
- **Rotas protegidas** usam middleware `auth:sanctum`

### Banco de Dados
- **MySQL/MariaDB**
- **Foreign Keys** com `cascade` ou `nullOnDelete`
- **Soft Deletes** em algumas tabelas
- **Spatie Permission** para controle de permissões

### Validações
- **CPF brasileiro** com algoritmo de validação
- **Datas** no formato brasileiro `d/m/Y`
- **Telefones** com máscara `(XX) XXXXX-XXXX`
- **CEPs** no formato `99999-999`

---

## 📝 Observações Importantes

- **Endpoints com `/auth/`** normalmente exigem `auth:sanctum`
- **`GET /api/users`** e **`GET /api/hemocentros`** são públicos
- **`PUT /api/auth/hemocentros/{hemocentro}`** não valida os campos antes de atualizar
- **Menores de 18 anos** precisam de responsável para cadastro
- **Agendamentos** verificam restrições biológicas e idade
- **Soft deletes** são usados para manter integridade referencial

---

## 🚀 Próximos Passos

1. **Implementar** os endpoints restantes (triagem, doação, campanhas)
2. **Configurar** permissões Spatie no front-end
3. **Criar** sistema de notificações por e-mail
4. **Implementar** relatórios em PDF
5. **Desenvolver** dashboard com gráficos e estatísticas
6. **Testar** todas as validações e regras de negócio