
# Situação Atual do Front-end - DoaVida

Documento atualizado para refletir o estado real do front, os fluxos já ligados na API e os pontos que dependem do backend para ficarem corretos de verdade.

---

## Autenticação

- Login usa `POST /api/auth/login`.
- Sessão usa `GET /api/auth/me`.
- O token é enviado pelo interceptor de `src/services/api.ts`.
- Atenção: o front ainda precisa manter uma única chave de token no `localStorage`. Hoje há histórico de uso entre `token` e `access_token`; isso deve ficar padronizado com o backend.

---

## Cadastro + Elegibilidade

### Estado atual

- O teste de elegibilidade (`EligibilityTestPage.tsx`) é um questionário local.
- Quando o usuário termina como apto, o front grava uma marca temporária em `sessionStorage`.
- A tela de cadastro (`RegistrationDonationPage.tsx`) redireciona para `/teste-elegibilidade` se o usuário não logado tentar cadastrar sem ter feito o teste.

### Limitação importante

Isso não é validação real de negócio. O backend ainda não recebe nem salva o exame de elegibilidade.

Para virar regra segura, precisa de backend com algo como:

- `POST /api/auth/elegibilidade`
- `GET /api/auth/elegibilidade/atual`

E o backend deveria bloquear `POST /api/auth/agendamentos` caso o usuário não tenha elegibilidade válida.

Sem isso, o front só melhora o fluxo visual, mas não impede burla por API, aba nova, limpeza de sessão ou chamada direta.

---

## Painel do Doador

Arquivo principal: `src/components/dashboards/DonorDashboard.tsx`.

### Próxima doação

- Usa `GET /api/agendamentos`.
- Pelo DOC-API, para doador essa rota retorna apenas agendamentos ativos (`AGE`) ou confirmados (`CON`).
- Por isso ela não deve ser usada para histórico concluído.

### Histórico

- Agora deve usar `GET /api/agendamentos/historico`.
- Essa rota é a correta para exibir agendamentos finalizados, cancelados e excluídos.

### Minhas Coletas

- Usa `GET /api/doacoes`.
- Se o funcionário registrou uma doação completa, ela aparecer em "Minhas Coletas" está correto.
- "Histórico" é a visão de agendamentos; "Minhas Coletas" é a visão de doações/coletas registradas.

### Restrição do doador

- O botão "Agendar Doação" não deve ficar desabilitado/translúcido.
- O comportamento melhor é deixar clicável e mostrar mensagem informando que o doador está temporariamente inelegível.

### Meus Certificados

- Atualmente é placeholder visual.
- Não existe rota documentada para listar ou gerar certificado.
- Para funcionar de verdade, precisa de backend, por exemplo:
  - `GET /api/certificados`
  - `GET /api/certificados/{id}/pdf`

---

## Painel do Funcionário

Arquivo principal: `src/components/dashboards/StaffDashboard.tsx`.

### Gestão de Doadores

- Usa `GET /api/users`.
- **Atenção**: Agora o backend filtra automaticamente doadores pelo histórico de doações no hemocentro do funcionário. O front não precisa aplicar filtros extras de hemocentro, mas deve estar preparado para receber apenas doadores aptos a serem vistos pelo funcionário logado.
- Os resultados agora retornam o nome do hemocentro da última doação e a data da última doação, facilitando a exibição.

### Agenda
- Usa `GET /api/agendamentos`.
- Pelo DOC-API, funcionário deve ver todos os agendamentos do seu hemocentro.
- O front filtra pela data selecionada.

### Confirmar presença

- Usa `POST /api/auth/agendamentos/{id}/confirmar`.
- Muda o status para `CON`.

### Cancelar

- Usa `POST /api/auth/agendamentos/{id}/cancelar`.
- Muda o status para `CAN`.
- O front atualiza localmente o item cancelado para não depender de o backend devolver cancelados imediatamente na listagem.

### Reabrir

- Usa `POST /api/auth/agendamentos/{id}/reabrir`.
- O botão aparece somente para status `CAN` e se a data da doação ainda não passou.
- Se o botão não aparecer, as causas mais prováveis são:
  - o backend não está retornando agendamentos cancelados em `GET /api/agendamentos`;
  - o status retornado não é `CAN`;
  - a data do agendamento já passou;
  - algum endpoint auxiliar estava quebrando o carregamento da agenda.

O front foi ajustado para a agenda não depender de `/estoque` ou `/estatisticas/funcionario`; se esses endpoints falharem, a agenda ainda deve carregar.

### Registrar triagem e doação

- Triagem usa `POST /api/auth/triagens`.
- Body esperado inclui:

  - `agendamento_id`
  - `user_id`
  - `hemocentro_id`
  - `data_triagem`
  - `apto`
  - `motivo_inaptidao`
  - `observacoes`
- Doação usa `POST /api/auth/doacoes`.
- Body esperado inclui:

  - `agendamento_id`
  - `triagem_id`
  - `user_id`
  - `hemocentro_id`
  - `data_hora_doacao`
  - `tipo_sangue`
  - `quantidade`
  - `data_validade_sangue`

Após sucesso, o front mantém o card visível como "Doação realizada", com visual verde.

---
## Painel do Diretor

Arquivo principal: `src/components/dashboards/DirectorDashboard.tsx`.

### Estatísticas e Indicadores (Ação Necessária)

O dashboard foi limpo de dados mockados e agora depende 100% da API para exibir informações de performance. 

- **Endpoint necessário:** `GET /api/estatisticas/diretor`
- **Comportamento do Front:** Se o valor for `null` ou o array estiver vazio, o card/gráfico correspondente **não será exibido**, evitando desinformação.

**Estrutura de dados esperada (JSON):**

```json
{
  "doacoes_mes": 150,                // Inteiro: Total de doações concluídas no mês
  "crescimento_mes": 8.5,            // Float: % de crescimento vs mês anterior
  "agendamentos_hoje": 12,           // Inteiro: Total de agendamentos para a data atual
  "confirmados_hoje": 10,            // Inteiro: Agendamentos com presença confirmada hoje
  "taxa_comparecimento": 83.3,       // Float: (confirmados / total) * 100
  "media_diaria": 7.2,               // Float: Média de doações por dia útil no mês
  "satisfacao": 4.8,                 // Float: Média de avaliações pós-doação (0 se não houver)
  "estoque_critico": ["O-", "A-"],   // Array: Tipos sanguíneos abaixo do nível mínimo
  "doacoes_por_mes": [               // Array: Dados para o gráfico de evolução (últimos 6-12 meses)
    { "mes": "Jan", "total": 120 },
    { "mes": "Fev", "total": 150 }
  ],
  "doacoes_por_tipo": {              // Objeto: Dados para o gráfico de pizza (mês atual)
    "O+": 45,
    "A+": 38,
    "B+": 20,
    "AB+": 10,
    "O-": 5,
    "A-": 12,
    "B-": 8,
    "AB-": 7
  }
}
```

### Gestão de Hemocentro

- O front-end tenta exibir o nome do hemocentro via `user.hemocentro.nome`. 
- **Necessidade:** O endpoint de autenticação (`/api/auth/me` ou `/api/auth/login`) deve retornar o relacionamento do hemocentro populado para que o diretor veja os dados da sua unidade corretamente.
---

## Pontos que dependem do backend

- Criar/Finalizar endpoint `GET /api/estatisticas/diretor`.
- Persistir exame de elegibilidade no usuário.
- Bloquear agendamento sem elegibilidade válida.
- Gerar/listar certificados.
- Garantir que `GET /api/agendamentos` retorne cancelados para funcionário, se a regra for permitir reabrir pela agenda.
- Padronizar o status de doação concluída: hoje o front aceita `FIN`, `DOA`, `REALIZADA` e presença de `doacao_id`, mas o ideal é o backend ter um padrão único.

---

## Pontos que dependem do backend

- Persistir exame de elegibilidade no usuário.
- Bloquear agendamento sem elegibilidade válida.
- Gerar/listar certificados.
- Garantir que `GET /api/agendamentos` retorne cancelados para funcionário, se a regra for permitir reabrir pela agenda.
- Padronizar o status de doação concluída: hoje o front aceita `FIN`, `DOA`, `REALIZADA` e presença de `doacao_id`, mas o ideal é o backend ter um padrão único.

---

## Observações técnicas

- Base URL: `http://localhost:8000/api`.
- O build pode falhar se `node_modules` estiver quebrado pelo pacote opcional do Rollup. Nesse caso, rodar:

```bash
npm install
npm run build
```
