# Doe Vida API & Front-end Map

Este documento descreve as rotas da API e o mapeamento das telas do sistema (Front-end) com as suas respectivas funcionalidades e consumos de API.

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