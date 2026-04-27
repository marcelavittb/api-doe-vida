# TAREFAS.MD - Lista de Desenvolvimento Doe Vida API

## 📋 **Metodologia de Priorização**
- **Prioridade 1 (Crítica)**: Funcionalidades core que impedem o funcionamento básico
- **Prioridade 2 (Alta)**: Funcionalidades essenciais para MVP
- **Prioridade 3 (Média)**: Melhorias importantes mas não críticas
- **Prioridade 4 (Baixa)**: Funcionalidades avançadas e otimizações

**Regra de Dependência**: Se tarefa A precisa de tarefa B, então B vem primeiro na lista.

---

## 🩸 **MÓDULO: TRIAGEM MÉDICA**

### **Prioridade 1 (Crítica)**
1. **Criar Migration da Triagem**
   - Analisar e ajustar `2026_04_01_000000_create_triagem_table.php`
   - Garantir campos: user_id, funcionario_id, hemocentro_id, data_triagem, status_triagem, observacoes, apto, motivo_inaptidao
   - Adicionar foreign keys corretas

2. **Criar Model Triagem**
   - Criar `app/Models/Triagem.php`
   - Definir relationships: belongsTo User, belongsTo Hemocentro, belongsTo Funcionario
   - Configurar fillable e casts

3. **Criar TriagemController**
   - Implementar métodos: store, index, show, update
   - Validações: apto/inapto, observações obrigatórias se inapto
   - Middleware auth:sanctum

4. **Adicionar Rotas de Triagem**
   - `POST /api/auth/triagens` - Criar triagem
   - `GET /api/triagens` - Listar triagens (funcionário)
   - `GET /api/triagens/{id}` - Detalhes da triagem
   - `PUT /api/auth/triagens/{id}` - Atualizar triagem

---

## 🩸 **MÓDULO: DOAÇÃO**

### **Prioridade 1 (Crítica)**
5. **Criar Migration da Doação** *(DEPENDE: Triagem)*
   - Analisar e ajustar `2026_04_01_000001_create_doacao_table.php`
   - Garantir campos: user_id, hemocentro_id, funcionario_id, triagem_id, data_doacao, tipo_sangue, volume_ml, bolsas, status_doacao, observacoes
   - Foreign keys para triagem aprovada

6. **Criar Model Doacao** *(DEPENDE: Triagem)*
   - Criar `app/Models/Doacao.php`
   - Relationships: belongsTo User, belongsTo Hemocentro, belongsTo Triagem, belongsTo Funcionario
   - Configurar fillable e casts

7. **Criar DoacaoController** *(DEPENDE: Triagem)*
   - Implementar métodos: store, index, show, update
   - Validação: só permitir doação se triagem aprovada
   - Atualizar estoque automaticamente após doação
   - Middleware auth:sanctum

8. **Adicionar Rotas de Doação** *(DEPENDE: Triagem)*
   - `POST /api/auth/doacoes` - Registrar doação
   - `GET /api/doacoes` - Listar doações (por hemocentro)
   - `GET /api/doacoes/{id}` - Detalhes da doação
   - `PUT /api/auth/doacoes/{id}` - Atualizar doação

---

## 📦 **MÓDULO: ESTOQUE**

### **Prioridade 1 (Crítica)**
9. **Criar Migration do Estoque** *(DEPENDE: Doação)*
   - Criar `2026_04_02_000000_create_estoque_table.php`
   - Campos: hemocentro_id, tipo_sangue, volume_total_ml, bolsas_disponiveis, data_ultima_atualizacao
   - Unique constraint: hemocentro_id + tipo_sangue

10. **Criar Model Estoque** *(DEPENDE: Doação)*
    - Criar `app/Models/Estoque.php`
    - Relationships: belongsTo Hemocentro
    - Métodos: adicionarEstoque(), removerEstoque(), verificarBaixoEstoque()

11. **Criar EstoqueController** *(DEPENDE: Doação)*
    - Implementar métodos: index, show, update (entrada/saída)
    - Validações: não permitir estoque negativo
    - Alertas de baixo estoque
    - Middleware auth:sanctum

12. **Adicionar Rotas de Estoque** *(DEPENDE: Doação)*
    - `GET /api/estoque` - Ver estoque do hemocentro
    - `PUT /api/auth/estoque/{id}` - Atualizar estoque manualmente
    - `GET /api/estoque/alertas` - Ver alertas de baixo estoque

13. **Integrar Estoque com Doação** *(DEPENDE: Doação + Estoque)*
    - Modificar DoacaoController para atualizar estoque automaticamente
    - Criar triggers ou observers para manter consistência

---

## 📊 **MÓDULO: DASHBOARDS E ESTATÍSTICAS**

### **Prioridade 2 (Alta)**
14. **Criar Controller de Estatísticas** *(DEPENDE: Doação + Estoque)*
    - Criar `app/Http/Controllers/EstatisticaController.php`
    - Métodos: dashboardDoador(), dashboardFuncionario(), dashboardDiretor()
    - Queries para contar doações, estoque, agendamentos por período

15. **Adicionar Rotas de Estatísticas** *(DEPENDE: Estatísticas Controller)*
    - `GET /api/estatisticas/donor` - Dashboard doador
    - `GET /api/estatisticas/staff` - Dashboard funcionário
    - `GET /api/estatisticas/director` - Dashboard diretor
    - `GET /api/estatisticas/admin` - Dashboard admin

16. **Implementar Gráficos Básicos** *(DEPENDE: Estatísticas)*
    - Doações por mês/tipo sanguíneo
    - Estoque por tipo sanguíneo
    - Agendamentos por status
    - Retornar dados JSON para gráficos no front-end

---

## 📄 **MÓDULO: RELATÓRIOS PDF**

### **Prioridade 2 (Alta)**
17. **Instalar Biblioteca PDF** *(INDEPENDENTE)*
    - Instalar `composer require barryvdh/laravel-dompdf`
    - Configurar service provider

18. **Criar Controller de Relatórios** *(DEPENDE: Estatísticas + Doação)*
    - Criar `app/Http/Controllers/RelatorioController.php`
    - Métodos: relatorioDoacoes(), relatorioEstoque(), relatorioDoadores(), relatorioDesempenho()
    - Gerar PDFs com dados consolidados

19. **Adicionar Rotas de Relatórios** *(DEPENDE: Relatórios Controller)*
    - `GET /api/relatorios/doacoes` - Relatório de doações
    - `GET /api/relatorios/estoque` - Relatório de estoque
    - `GET /api/relatorios/doadores` - Relatório de doadores
    - `GET /api/relatorios/desempenho` - Relatório de desempenho

---

## 📧 **MÓDULO: NOTIFICAÇÕES POR E-MAIL**

### **Prioridade 2 (Alta)**
20. **Configurar Mail do Laravel** *(INDEPENDENTE)*
    - Atualizar `.env` com configurações SMTP
    - Criar templates de e-mail em `resources/views/mail`

21. **Implementar Recuperação de Senha** *(DEPENDE: Mail)*
    - Melhorar `PasswordResetController.php`
    - Criar template de e-mail de recuperação
    - Adicionar rota `POST /api/auth/forgot-password`

22. **Notificações de Agendamento** *(DEPENDE: Mail + Agendamento)*
    - Criar notificações: confirmação, lembrete, cancelamento
    - Templates para cada tipo de notificação
    - Métodos no AgendamentoController para enviar e-mails

23. **Alertas de Restrição** *(DEPENDE: Mail + Doação)*
    - Notificar doador quando período de restrição terminar
    - Alertas de baixo estoque para funcionários
    - Lembretes de agendamentos próximos

---

## 🎯 **MÓDULO: CAMPANHAS**

### **Prioridade 2 (Alta)**
24. **Criar Migration de Campanhas** *(INDEPENDENTE)*
    - Criar `2026_04_03_000000_create_campanhas_table.php`
    - Campos: titulo, descricao, data_inicio, data_fim, hemocentros (JSON/array), status, criado_por

25. **Criar Model Campanha** *(INDEPENDENTE)*
    - Criar `app/Models/Campanha.php`
    - Relationships: belongsToMany Hemocentros
    - Métodos: campanhasAtivas(), campanhasPorHemocentro()

26. **Criar CampanhaController** *(INDEPENDENTE)*
    - Implementar CRUD completo
    - Vincular/desvincular hemocentros
    - Middleware auth:sanctum com permissões admin

27. **Adicionar Rotas de Campanhas** *(INDEPENDENTE)*
    - `POST /api/auth/campanhas` - Criar campanha
    - `GET /api/campanhas` - Listar campanhas
    - `PUT /api/auth/campanhas/{id}` - Atualizar campanha
    - `DELETE /api/auth/campanhas/{id}` - Remover campanha

---

## 🔐 **MÓDULO: PERMISSÕES SPATIE**

### **Prioridade 3 (Média)**
28. **Configurar Spatie Permission** *(INDEPENDENTE)*
    - Verificar se migration `create_permission_tables.php` está correta
    - Executar migration se necessário
    - Configurar models User, Role, Permission

29. **Criar Roles e Permissions Iniciais** *(DEPENDE: Spatie Configurado)*
    - Criar seeder para roles: doador, funcionario, diretor, admin
    - Criar permissions: criar_usuario, editar_usuario, ver_relatorios, etc.
    - Vincular permissions às roles

30. **Implementar Middleware de Permissões** *(DEPENDE: Roles/Permissions)*
    - Criar middleware personalizado para verificar permissions
    - Aplicar em rotas específicas
    - Proteger endpoints sensíveis

31. **Atualizar Controllers com Permissões** *(DEPENDE: Middleware)*
    - Modificar controllers para verificar permissions
    - Ex: só diretor pode criar funcionários
    - Ex: só funcionário pode fazer triagem

---

## ⚙️ **MÓDULO: CONFIGURAÇÕES AVANÇADAS**

### **Prioridade 3 (Média)**
32. **Configurações de Hemocentro** *(INDEPENDENTE)*
    - Adicionar campos: horario_funcionamento, dias_uteis, limite_doacoes_diarias, limite_estoque
    - Criar migration adicional ou modificar existente
    - Controller para atualizar configurações

33. **Reagendamento Inteligente** *(DEPENDE: Agendamento)*
    - Modificar AgendamentoController
    - Lógica: marcar agendamento antigo como "EXC" e criar novo
    - Evitar conflitos de horário

34. **Validações Avançadas** *(DEPENDE: Todos os módulos)*
    - Melhorar validações em HemocentroController (PUT sem validação)
    - Adicionar validações de negócio complexas
    - Criar custom validation rules

---

## 🧪 **MÓDULO: TESTES E QUALIDADE**

### **Prioridade 4 (Baixa)**
35. **Criar Testes Unitários** *(INDEPENDENTE)*
    - Testes para models e validações
    - Testes para controllers
    - Testes para regras de negócio

36. **Criar Testes de Integração** *(DEPENDE: Testes Unitários)*
    - Testes end-to-end das APIs
    - Testes de autenticação
    - Testes de workflows completos

37. **Documentação de Testes** *(DEPENDE: Testes)*
    - Criar coleção Postman
    - Documentar cenários de teste
    - Guias de uso da API

---

## 📈 **MÓDULO: OTIMIZAÇÕES E PERFORMANCE**

### **Prioridade 4 (Baixa)**
38. **Otimizar Queries** *(DEPENDE: Todos os módulos implementados)*
    - Adicionar índices nas migrations
    - Implementar eager loading
    - Otimizar queries N+1

39. **Cache de Dados** *(DEPENDE: Dashboards)*
    - Implementar cache nas estatísticas
    - Cache de configurações
    - Invalidar cache quando necessário

40. **Logs e Monitoramento** *(INDEPENDENTE)*
    - Implementar logging detalhado
    - Monitorar performance das APIs
    - Alertas de erro

---

## 🎯 **RESUMO DE DEPENDÊNCIAS CRÍTICAS**

**Sequência obrigatória para MVP:**
1. Triagem → 2. Doação → 3. Estoque → 4. Dashboards → 5. Relatórios

**Pode ser paralelo:**
- Campanhas (independente)
- Permissões (independente, mas afeta todos)
- Notificações (depende de mail + outros módulos)

**Próximo passo recomendado:** Começar implementando o **Módulo Triagem** (tarefa 1-4) para ter o fluxo básico funcionando.