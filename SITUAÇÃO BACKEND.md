# SITUAÇÃO BACKEND - Doe Vida API

Este documento detalha o progresso atual do desenvolvimento do backend, seguindo o roteiro do `TAREFAS.md`.

---

## 🔐 **MÓDULO: AUTENTICAÇÃO E USUÁRIOS**
*Estado: **95% Concluído***

- [x] **Registro de Doador**: Ajustado para não exigir hemocentro (Doador Livre).
- [x] **Gestão de Usuários (Admin)**: Validação condicional implementada (Hemocentro obrigatório para Staff, proibido para Doador).
- [x] **Login/Sessão**: Implementado com Sanctum e integrado à lógica de Roles.
- [x] **Roles**: Implementado via `role_id` no banco com filtragem nos controllers.

---

## 📅 **MÓDULO: AGENDAMENTOS**
*Estado: **100% Concluído***

- [x] **Criação**: Fluxo com validação de restrição biológica (90/120 dias).
- [x] **Gestão Doador**: Histórico completo e cancelamento próprio.
- [x] **Gestão Funcionário**: Confirmação de presença e cancelamento por horário.
- [x] **Rotas**: Implementado endpoints explícitos `/confirmar` e `/cancelar`.

---

## 🩺 **MÓDULO: TRIAGEM MÉDICA**
*Estado: **80% Concluído***

- [x] **Migrations**: Tabelas de perguntas, opções, respostas e triagem criadas.
- [x] **Efetivação**: Controller agora permite concluir a triagem definindo aptidão e observações.
- [x] **Filtros de Segurança**: Doador vê apenas suas triagens; Funcionário vê as do seu hemocentro.
- [ ] **Falta**: Integrar a inaptidão automática baseada no sistema de perguntas/respostas (atualmente manual via Controller).

---

## 🩸 **MÓDULO: DOAÇÃO**
*Estado: **100% Concluído***

- [x] **Model & Controller**: Criados com relacionamentos (Doador, Funcionário, Hemocentro).
- [x] **Registro de Coleta**: Implementado registro de tipo sanguíneo, quantidade (ml) e validade.
- [x] **Histórico**: Listagem filtrada por papel de usuário (Doador/Staff).

---

## 📦 **MÓDULO: ESTOQUE**
*Estado: **0% Concluído***

- [ ] **Migration**: Pendente.
- [ ] **Integração**: Lógica de somar ML ao estoque automaticamente após o registro de uma doação.

---

## 📊 **MÓDULO: DASHBOARDS E ESTATÍSTICAS**
*Estado: **0% Concluído***

- [ ] **Controllers**: Pendente.
- [ ] **Queries**: Necessário consolidar dados de doações e estoque para gráficos.

---

## 📧 **MÓDULO: NOTIFICAÇÕES E MAIL**
*Estado: **20% Concluído***

- [x] **Password Reset**: Estrutura inicial existente.
- [ ] **Lembretes**: Pendente (Lembrete de agendamento e fim de restrição).

---

## 🎯 **MÓDULO: CAMPANHAS**
*Estado: **20% Concluído***

- [x] **Migration**: Tabela `campanhas` criada.
- [ ] **Gestão**: Controller para gerenciar campanhas ativas nos hemocentros.

---

## ⚙️ **MODIFICAÇÕES RECENTES (CRÍTICAS)**
1. **Hierarquia de Fluxo**: Implementada a lógica Agendamento -> Triagem -> Doação.
2. **Endpoints Semânticos**: Criado POSTs específicos para ações de status (confirmar/cancelar) para facilitar o Front.
3. **Documentação**: `DOC-API.md` totalmente atualizado com os novos módulos.

---

## 🚀 **PRÓXIMOS PASSOS IMEDIATOS**
1. Implementar o módulo de **Estoque** (atualização automática ao registrar doação).
2. Criar as queries de Dashboard para o Diretor/Admin.
