# SITUAÇÃO BACKEND - Doe Vida API

Este documento detalha o progresso atual do desenvolvimento do backend, seguindo o roteiro do `TAREFAS.md`.

---

## 🔐 **MÓDULO: AUTENTICAÇÃO E USUÁRIOS**
*Estado: **90% Concluído***

- [x] **Registro de Doador**: Ajustado para não exigir hemocentro (Doador Livre).
- [x] **Gestão de Usuários (Admin)**: Validação condicional implementada (Hemocentro obrigatório para Staff, proibido para Doador).
- [x] **Login/Sessão**: Implementado com Sanctum e integrado à lógica de Roles.
- [ ] **Falta**: Implementar permissões granulares (Spatie) nos métodos dos controllers.

---

## 🩸 **MÓDULO: TRIAGEM MÉDICA**
*Estado: **30% Concluído***

- [x] **Migrations**: Tabelas de perguntas, opções, respostas e triagem criadas.
- [x] **Estrutura Básica**: Controller e Models criados.
- [ ] **Falta**: Implementar a lógica de perguntas/respostas dinâmicas nos Models (estão vazios).
- [ ] **Falta**: Validação de inaptidão automática baseada nas respostas.

---

## 🩸 **MÓDULO: DOAÇÃO**
*Estado: **10% Concluído***

- [x] **Migration**: Tabela `doacoes` criada.
- [ ] **Model**: Não iniciado.
- [ ] **Controller**: Não iniciado.
- [ ] **Regra**: Lógica de "Só doa se triagem = Apto" pendente.

---

## 📦 **MÓDULO: ESTOQUE**
*Estado: **0% Concluído***

- [ ] **Migration**: Pendente.
- [ ] **Model/Controller**: Pendente.
- [ ] **Integração**: Lógica de somar ML ao estoque após doação pendente.

---

## 📊 **MÓDULO: DASHBOARDS E ESTATÍSTICAS**
*Estado: **0% Concluído***

- [ ] **Controllers**: Pendente.
- [ ] **Queries**: Necessário consolidar dados de doações e estoque.

---

## 📧 **MÓDULO: NOTIFICAÇÕES E MAIL**
*Estado: **20% Concluído***

- [x] **Password Reset**: Estrutura inicial existente (via Supabase no front/API no back).
- [ ] **Lembretes**: Pendente (Lembrete de agendamento e fim de restrição).

---

## 🎯 **MÓDULO: CAMPANHAS**
*Estado: **20% Concluído***

- [x] **Migration**: Tabela `campanhas` criada.
- [ ] **Gestão**: Controller para vincular hemocentros às campanhas pendente.

---

## ⚙️ **MODIFICAÇÕES RECENTES (CRÍTICAS)**
1. **Desacoplamento Doador/Hemocentro**: Removido o vínculo fixo de `users.hemocentro_id` para o Role Doador.
2. **Documentação**: `DOC-API.md` atualizado com as novas regras de request/response.

---

## 🚀 **PRÓXIMOS PASSOS IMEDIATOS**
1. Preencher a lógica interna dos Models de **Triagem**.
2. Criar o Model e Controller de **Doação** (conectando com a triagem).
3. Iniciar o módulo de **Estoque** para receber os dados das doações.