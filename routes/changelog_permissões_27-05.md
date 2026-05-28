# Doe Vida — Sistema de Cargos e Permissões

**Implementado em:** 27/05/2026  
**Arquivos alterados:** `RoleController.php`, `RoleOrPermissionMiddleware.php`, `bootstrap/app.php`, `routes/api.php`, `UserController.php`, `AdminDashboard.tsx`

---

## 1. Contexto — por que esse sistema foi criado

Antes desta implementação, o controle de acesso do sistema era feito **apenas por role** (cargo fixo). Isso significava que somente o `admin` podia gerenciar campanhas, e não havia forma de criar novos cargos com permissões personalizadas pelo painel.

O problema: se o hemocentro precisasse de um cargo como `marketing_campanhas` — que pode criar e disparar campanhas mas não acessa dados clínicos — não havia como criar isso sem mexer no código.

A solução implementada mantém os **5 cargos fixos do sistema intactos** e adiciona a capacidade de criar **cargos personalizados com permissões granulares** pelo Admin Dashboard.

---

## 2. Arquitetura do sistema

### Dois tipos de cargo

| Tipo | Exemplos | Pode ser criado pelo Admin? | Pode ser editado? | Pode ser excluído? |
|---|---|---|---|---|
| **Sistema** | doador, funcionario, diretor, admin, enfermeiro | Não | Só permissões | Não |
| **Personalizado** | marketing_campanhas, supervisor_clinico | Sim | Nome + permissões | Sim (se sem usuários) |

### Cargos do sistema — comportamento preservado

Os 5 cargos fixos continuam funcionando **exatamente como antes**. Nenhuma lógica existente foi alterada. Eles são identificados pela constante `ROLES_SISTEMA` no `RoleController`:

```php
private const ROLES_SISTEMA = ['doador', 'funcionario', 'diretor', 'admin', 'enfermeiro'];
```

- **doador** — perfil padrão do cadastro público
- **funcionario** — vinculado a um hemocentro, acesso operacional
- **diretor** — gestão do hemocentro
- **admin** — acesso global ao sistema
- **enfermeiro** — cargo do sistema preservado da configuração anterior

---

## 3. Novo middleware — RoleOrPermissionMiddleware

O ponto central da implementação. Antes, as rotas usavam apenas `role:admin`, que bloqueava qualquer usuário sem aquele cargo fixo. Agora as rotas sensíveis usam um middleware que aceita **role OU permissão**:

```
Acesso liberado se:
  usuário tem o cargo fixo (ex: admin)
  OU
  usuário tem a permissão específica (ex: gerenciar_campanhas)
```

Isso garante que:
- O `admin` continua acessando campanhas mesmo sem permissões explícitas no banco
- Um cargo personalizado com `gerenciar_campanhas` também consegue acessar
- Usuários sem nem o cargo nem a permissão recebem 403

### Como as rotas de campanhas ficaram

| Rota | Middleware aplicado |
|---|---|
| `GET /api/campanhas` | `auth:sanctum` (qualquer logado) |
| `POST /api/auth/campanhas` | `admin` ou `gerenciar_campanhas` |
| `PUT /api/auth/campanhas/{id}` | `admin` ou `gerenciar_campanhas` |
| `DELETE /api/auth/campanhas/{id}` | `admin` ou `gerenciar_campanhas` |
| `POST /api/auth/campanhas/{id}/disparar` | `admin` ou `disparar_campanhas` |

---

## 4. Permissões disponíveis no sistema

Organizadas por módulo. Essas permissões podem ser atribuídas a qualquer cargo personalizado:

| Módulo | Permissão | Descrição |
|---|---|---|
| **Agendamentos** | `ver_agendamentos` | Ver agendamentos |
| | `criar_agendamentos` | Criar agendamentos |
| | `confirmar_agendamentos` | Confirmar / reabrir agendamentos |
| | `cancelar_agendamentos` | Cancelar agendamentos |
| **Doações** | `ver_doacoes` | Ver doações |
| | `registrar_doacoes` | Registrar doações |
| **Triagem** | `ver_triagens` | Ver triagens |
| | `registrar_triagem` | Registrar triagem clínica |
| **Estoque** | `ver_estoque` | Ver estoque |
| | `gerenciar_estoque` | Gerenciar estoque |
| **Alertas Médicos** | `ver_alertas_medicos` | Ver alertas médicos |
| | `gerenciar_alertas_medicos` | Criar / editar alertas médicos |
| **Usuários** | `ver_usuarios` | Ver usuários |
| | `criar_usuarios` | Criar usuários |
| | `excluir_usuarios` | Excluir usuários |
| **Hemocentros** | `ver_hemocentros` | Ver hemocentros |
| | `gerenciar_hemocentros` | Gerenciar hemocentros |
| **Campanhas** | `ver_campanhas` | Ver campanhas |
| | `gerenciar_campanhas` | Criar / editar campanhas |
| | `disparar_campanhas` | Disparar campanhas |
| **Estatísticas** | `ver_estatisticas_hemocentro` | Ver estatísticas do hemocentro |
| | `ver_estatisticas_globais` | Ver estatísticas globais |
| **Relatórios** | `exportar_relatorios` | Exportar relatórios PDF |

---

## 5. Como funciona no Admin Dashboard

### Aba "Permissões"

A aba exibe todos os cargos do sistema — fixos e personalizados. Para cada cargo são mostrados:
- Nome do cargo
- Badge "sistema" para os 5 cargos fixos
- Contador de usuários vinculados

**Cargos do sistema:** botões de editar e excluir ficam desabilitados. Não podem ser renomeados nem excluídos.

**Cargos personalizados:** podem ser editados (nome + permissões) e excluídos (desde que não tenham usuários vinculados).

### Criar um novo cargo (botão "Nova Role")

1. Admin clica em **Nova Role**
2. Preenche o nome do cargo (ex: `marketing_campanhas`) — letras minúsculas e underscores
3. Seleciona as permissões por módulo via checkboxes
4. Clica em **Criar Role** — o botão mostra quantas permissões foram selecionadas

O cargo é criado via `POST /api/auth/roles` e aparece imediatamente na lista.

### Editar um cargo personalizado

1. Clica no botão de editar ao lado do cargo
2. Pode alterar o nome (se não for cargo do sistema)
3. Pode marcar/desmarcar permissões — checkboxes pré-marcados com as permissões atuais
4. Para cargos do sistema: nome fica bloqueado, mas permissões podem ser editadas

### Excluir um cargo personalizado

Só é possível excluir se não houver usuários vinculados. O botão fica desabilitado com tooltip explicativo se houver usuários.

---

## 6. Cargos personalizados na criação de usuários

Ao criar ou editar um usuário no Admin Dashboard, o campo **Perfil** agora carrega todos os cargos disponíveis — fixos e personalizados:

- Na criação: o select busca via `GET /api/roles` e exibe todos exceto `doador` (que é atribuído no cadastro público)
- Na edição: exibe todos os cargos sem exceção
- Se o cargo tem um label fixo (ex: "Funcionário", "Diretor"), ele é exibido; caso contrário, exibe o nome do cargo como cadastrado

---

## 7. API — endpoints do módulo de cargos

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/api/roles` | Qualquer logado | Lista todos os cargos com permissões e contador de usuários |
| `GET` | `/api/permissions` | Qualquer logado | Retorna mapa de permissões disponíveis por módulo |
| `POST` | `/api/auth/roles` | `role:admin` | Cria novo cargo personalizado com permissões |
| `PUT` | `/api/auth/roles/{role}` | `role:admin` | Atualiza nome e/ou permissões de um cargo |
| `DELETE` | `/api/auth/roles/{role}` | `role:admin` | Remove cargo personalizado (protegido contra cargos do sistema e com usuários) |

### Formato de resposta do GET /api/roles

```json
[
  {
    "id": 1,
    "name": "doador",
    "guard_name": "api",
    "users_count": 312,
    "sistema": true,
    "permissions": []
  },
  {
    "id": 6,
    "name": "marketing_campanhas",
    "guard_name": "api",
    "users_count": 2,
    "sistema": false,
    "permissions": ["ver_campanhas", "gerenciar_campanhas", "disparar_campanhas"]
  }
]
```

---

## 8. Regras de segurança implementadas

- Cargos do sistema (`doador`, `funcionario`, `diretor`, `admin`, `enfermeiro`) **nunca podem ser excluídos** — o backend retorna 422 com mensagem explicativa
- Cargos do sistema **não podem ser renomeados** — apenas as permissões podem ser atualizadas
- Um cargo personalizado com usuários vinculados **não pode ser excluído** — o backend retorna 422 e o frontend desabilita o botão
- Permissões são criadas automaticamente no banco via `Permission::firstOrCreate()` ao serem associadas a um cargo — não é necessário seedar manualmente cada permissão
- O `admin` mantém acesso a todas as rotas independente de ter permissões explícitas no banco, graças ao `RoleOrPermissionMiddleware`

---

## 9. Como o sistema se integra com o fluxo existente

O sistema foi projetado para **não interferir** no que já funciona:

- As rotas que usavam `role:funcionario,diretor,admin` continuam funcionando da mesma forma para esses cargos
- O `role_id` numérico no banco e nos controllers não foi alterado — o Spatie e o campo `role_id` seguem sendo mantidos em paralelo como antes
- Usuários criados com cargo personalizado recebem a role do Spatie mas podem não ter `role_id` numérico correspondente — os controllers que dependem de `role_id == 1` (doador) continuam funcionando porque cargo personalizado nunca terá `role_id == 1`
- O fallback no frontend (`DEFAULT_PERMISSIONS`) garante que a aba de Permissões funciona mesmo se `GET /api/permissions` falhar — sem quebrar o dashboard

---

## 10. Correções e melhorias no sistema de relatórios — 27/05/2026

**Arquivos alterados:** `RelatorioController.php`, `routes/api.php`, `resources/views/relatorios/doacoes.blade.php`, `resources/views/relatorios/estoque.blade.php`, `resources/views/relatorios/doadores.blade.php`

### 10.1 Bug crítico corrigido — DomPDF (Classe não encontrada)

O autoloader do Composer estava sem o mapeamento de namespace do pacote `barryvdh/laravel-dompdf`. Todos os endpoints de PDF retornavam HTTP 500 com o erro:

```
Class "Barryvdh\DomPDF\ServiceProvider" not found
```

**Causa:** o arquivo `vendor/composer/autoload_psr4.php` não continha a entrada `Barryvdh\\DomPDF\\`, embora o pacote estivesse no `composer.lock` e os arquivos existissem no `vendor/`. O composer havia instalado os arquivos mas não regenerado o autoloader corretamente.

**Correção:** executado `composer install --no-scripts` seguido de `php artisan package:discover` e `php artisan config:clear`. O autoloader foi regenerado e o ServiceProvider passou a ser descoberto automaticamente.

### 10.2 Rotas JSON ausentes — adicionadas

Os métodos `donationsSummary`, `bloodStock` e `performanceMonthly` existiam no `RelatorioController` mas **não estavam registrados em nenhuma rota**. Foram adicionados os seguintes endpoints:

| Método | Rota | Controller | Descrição |
|---|---|---|---|
| `GET` | `/api/relatorios/resumo` | `resumo()` | **Novo** — Dashboard executivo completo |
| `GET` | `/api/relatorios/doacoes/json` | `donationsSummary()` | Agendamentos por status |
| `GET` | `/api/relatorios/estoque/json` | `bloodStock()` | Estoque por tipo sanguíneo |
| `GET` | `/api/relatorios/performance/mensal` | `performanceMonthly()` | Doações por mês |
| `GET` | `/api/relatorios/doacoes/pdf` | `pdfDoacoes()` | PDF doações (nova URL) |
| `GET` | `/api/relatorios/estoque/pdf` | `pdfEstoque()` | PDF estoque (nova URL) |
| `GET` | `/api/relatorios/doadores/pdf` | `pdfDoadores()` | PDF doadores (nova URL) |

As URLs legadas (`/api/relatorios/doacoes`, `/estoque`, `/doadores`) foram mantidas como aliases para não quebrar clientes existentes.

### 10.3 Novo endpoint — `GET /api/relatorios/resumo`

Retorna todos os KPIs em uma única chamada. Aceita os parâmetros:
- `?dias=30` — janela de análise (padrão: 30 dias)
- `?hemocentro_id=X` — filtra por unidade (só para admin; ignorado para diretor/funcionário)

**Estrutura da resposta:**

```json
{
  "periodo_dias": 30,
  "gerado_em": "2026-05-27T...",
  "kpis": {
    "total_doacoes": 47,
    "volume_total_ml": 21150,
    "volume_total_litros": 21.15,
    "media_volume_ml": 450,
    "doadores_unicos": 41,
    "taxa_conversao_pct": 73.2,
    "taxa_cancelamento_pct": 12.5,
    "tipo_mais_doado": "O+",
    "taxa_aptidao_pct": 88.0,
    "variacao_volume_pct": 14.3,
    "estoques_criticos": 3
  },
  "agendamentos": { "total": 64, "concluidos": 47, "cancelados": 8, "pendentes": 9 },
  "distribuicao_tipo_sanguineo": { "A+": 8, "O+": 25, ... },
  "estoque_critico": [{ "tipo_sangue": "AB-", "quantidade": 0.5, "deficit": 1.5, "hemocentro": "..." }],
  "performance_mensal": [{ "mes": "2026-05", "label": "mai/26", "total": 47, "volume_ml": 21150 }],
  "doacoes_por_dia_semana": [{ "dia": "Seg", "total": 9 }, ...]
}
```

### 10.4 Melhorias nos PDFs

Os três templates Blade de relatório PDF foram reescritos com layout profissional:

**Relatório de Doações** (`doacoes.blade.php`):
- 5 cards KPI no topo: total de doações, volume em litros, volume médio/doação, doadores únicos, tipo mais doado
- Gráfico SVG de barras verticais com doações por dia do período
- Barras horizontais com distribuição por tipo sanguíneo
- Tabela detalhada com data/hora, doador, tipo, volume, unidade, responsável e validade da bolsa
- Orientação paisagem (`A4 landscape`)

**Relatório de Estoque** (`estoque.blade.php`):
- Box de alerta vermelho destacado com todos os tipos em nível crítico (ou mensagem verde se tudo estável)
- 4 cards KPI: volume total, críticos, estáveis, unidades monitoradas
- Barras horizontais por tipo sanguíneo com linha vertical indicando o mínimo exigido
- Tabela com % do mínimo, barra de nível visual inline e coluna de déficit
- Status em 3 níveis: CRÍTICO (< 50%), ATENÇÃO (50–79%), ESTÁVEL (≥ 80%)

**Relatório de Doadores** (`doadores.blade.php`):
- 4 cards KPI: total, ativos, inativos, tipo mais comum
- Distribuição por tipo sanguíneo em barras horizontais
- Distribuição por faixa etária (18-25, 26-35, 36-45, 46-55, 56+)
- Distribuição por sexo
- Tabela com CPF mascarado (ex: `123.***.***-**`) para conformidade com LGPD
- Nota de confidencialidade no rodapé

### 10.5 Melhorias no `RelatorioController`

- `bloodStock()` enriquecido: agora retorna `minimo`, `critico` (bool) e `pct_nivel` além da quantidade
- `performanceMonthly()` enriquecido: agora retorna `volume_ml` por mês além do contador de doações. Preenche todos os meses mesmo sem doações (sem lacunas no gráfico)
- `pdfDoacoes()` passa para o template: distribuição por tipo, máximo para escala de barras, agrupamento por dia
- `pdfEstoque()` passa para o template: estoques críticos e estáveis separados, dados por tipo agregados, máximo para escala
- `pdfDoadores()` passa para o template: distribuição por tipo sanguíneo, faixa etária calculada e distribuição por sexo