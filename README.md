# 🛒 Order Management System — Teste Full Stack Pleno

Sistema de gestão de pedidos para e-commerce com múltiplos afiliados. Consome a [Fake Store API](https://fakestoreapi.com), processa dados de forma assíncrona com filas, persiste localmente e expõe um dashboard com métricas, filtros e controle de status.

---

## 📋 Índice

- [Visão geral](#visão-geral)
- [Tecnologias](#tecnologias)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e execução](#instalação-e-execução)
- [Como rodar os testes](#como-rodar-os-testes)
- [Endpoints da API](#endpoints-da-api)
- [Máquina de estados](#máquina-de-estados)
- [Importação de pedidos](#importação-de-pedidos)
- [Workflows N8N](#workflows-n8n)
- [Decisões técnicas](#decisões-técnicas)
- [O que ficou fora do escopo](#o-que-ficou-fora-do-escopo)

---

## Visão geral

O sistema trata cada `cart` da Fake Store API como um **pedido** e cada `user` como um **afiliado**. A importação é feita de forma assíncrona via filas do Laravel (Redis), com Jobs por página e suporte a retry automático. O frontend em Vue.js consome a API REST e exibe um dashboard com métricas em tempo real.

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2, Laravel 11 |
| Frontend | Vue.js 3 (Composition API), Tailwind CSS |
| Banco de dados | MySQL 8 |
| Cache e filas | Redis |
| Containers | Docker + Docker Compose |
| Automação | N8N |
| Testes | Pest (PHP) |

---

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e rodando
- Git

> Não é necessário ter PHP, Node.js ou MySQL instalados localmente. Tudo roda dentro dos containers.

---

## Instalação e execução

### 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/teste-fullstack-seu-nome.git
cd teste-fullstack-seu-nome
```

### 2. Configure as variáveis de ambiente

```bash
cp .env.example .env
```

Abra o `.env` e ajuste os valores marcados com `CHANGE_ME_IN_PRODUCTION` conforme necessário para o ambiente local. Os valores do `.env.example` já funcionam com o Docker Compose sem alterações.

### 3. Suba os containers

```bash
docker compose up -d
```

Isso inicializa os serviços:
- `app` — Laravel (PHP 8.2-FPM)
- `mysql` — MySQL 8
- `redis` — Redis 7
- `worker` — Fila de jobs (`php artisan queue:work`)
- `n8n` — Plataforma de automação (porta 5678)

### 4. Instale as dependências e prepare o banco

```bash
# Instalar dependências PHP
docker compose exec app composer install

# Gerar a application key
docker compose exec app php artisan key:generate

# Rodar as migrations
docker compose exec app php artisan migrate

# (Opcional) Popular com dados de seed
docker compose exec app php artisan db:seed
```

### 5. Instale as dependências do frontend

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

### 6. Importe os pedidos da API externa

```bash
docker compose exec app php artisan orders:sync
```

A aplicação estará disponível em:
- **Frontend/API:** http://localhost:8000
- **N8N:** http://localhost:5678

---

## Como rodar os testes

```bash
# Todos os testes
docker compose exec app php artisan test

# Com cobertura de código
docker compose exec app php artisan test --coverage

# Apenas um grupo específico
docker compose exec app php artisan test --group=state-machine
docker compose exec app php artisan test --group=metrics
docker compose exec app php artisan test --group=sync
```

### Suítes disponíveis

| Grupo | O que testa |
|---|---|
| `state-machine` | Transições válidas e inválidas da máquina de estados |
| `metrics` | Endpoint `/api/orders/metrics` com e sem cache Redis |
| `sync` | Integração do comando `orders:sync` (API mockada) |

---

## Endpoints da API

### Pedidos

```
GET  /api/orders
```
Lista paginada de pedidos (20 por página).

**Query params disponíveis:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `affiliate_id` | integer | Filtrar por afiliado |
| `status` | string | `pending`, `approved`, `cancelled`, `refunded` |
| `date_from` | date | `YYYY-MM-DD` |
| `date_to` | date | `YYYY-MM-DD` |
| `min_value` | numeric | Valor mínimo do pedido |
| `max_value` | numeric | Valor máximo do pedido |
| `sort_by` | string | Campo de ordenação (ex: `created_at`, `total`) |
| `sort_dir` | string | `asc` ou `desc` |

---

```
GET  /api/orders/{id}
```
Detalhe do pedido com itens e histórico completo de status.

---

```
GET  /api/orders/metrics
```
Métricas agregadas. Cache de 5 minutos via Redis. Invalidado automaticamente ao atualizar qualquer status.

---

```
POST /api/orders/{id}/status
```
Atualiza o status de um pedido.

**Body:**
```json
{
  "status": "approved",
  "reason": "Pagamento confirmado"
}
```

Transições inválidas retornam `422 Unprocessable Entity` com mensagem descritiva.

---

```
GET  /api/affiliates/{id}/summary
```
Resumo do afiliado: total de pedidos, receita, ticket médio e taxa de cancelamento.

---

```
GET  /api/health
```
Status dos serviços dependentes (MySQL, Redis e worker ativo).

**Resposta exemplo:**
```json
{
  "status": "ok",
  "services": {
    "mysql": "ok",
    "redis": "ok",
    "worker": "ok"
  }
}
```

---

## Máquina de estados

Os pedidos seguem o seguinte fluxo de estados:

```
pending ──► approved ──► refunded
   │
   └────► cancelled
```

| De | Para | Permitido |
|---|---|---|
| `pending` | `approved` | ✅ |
| `pending` | `cancelled` | ✅ |
| `approved` | `refunded` | ✅ |
| `approved` | `cancelled` | ❌ |
| `cancelled` | qualquer | ❌ |
| `refunded` | qualquer | ❌ |

Toda transição é registrada em `order_status_logs` com timestamp e usuário responsável (auditoria).

---

## Importação de pedidos

O comando `php artisan orders:sync` busca dados da [Fake Store API](https://fakestoreapi.com) e os persiste localmente de forma assíncrona.

**Funcionamento:**

1. O comando principal dispara um `SyncOrdersJob` por página de resultados da API.
2. Cada Job processa e faz `upsert` dos pedidos da sua página, evitando duplicatas.
3. Jobs com falha são reprocessados automaticamente até **3 tentativas** com backoff exponencial.
4. O comando é **idempotente**: pode ser rodado múltiplas vezes sem gerar inconsistências.
5. Rate limiting de 10 requisições por segundo nas chamadas HTTP à API externa.

**Reprocessar jobs com falha manualmente:**

```bash
docker compose exec app php artisan queue:retry all
```

---

## Workflows N8N

Os workflows estão em `n8n/workflows/` e podem ser importados pelo painel do N8N.

### Como importar localmente

1. Acesse http://localhost:5678
2. Faça login com as credenciais do `.env` (`N8N_USER` e `N8N_PASSWORD`)
3. Vá em **Workflows → Import from file**
4. Importe os arquivos `.json` da pasta `n8n/workflows/`

### Workflow 1 — Pedido aprovado (`order-approved.json`)

- **Gatilho:** `POST /webhook/order-approved`
- Filtra eventos com `new_status = "approved"`
- Formata mensagem: `✅ Pedido #ID aprovado — R$ VALOR | Afiliado #ID`
- Envia para webhook (configurado via `webhook.site` por padrão — substituir por Slack/Discord/Gmail em produção)
- Registra o evento em Google Sheets

### Workflow 2 — Pedido cancelado (`order-cancelled.json`)

- **Gatilho:** `POST /webhook/order-cancelled`
- Filtra eventos com `new_status = "cancelled"`
- Consulta `/api/affiliates/{affiliate_id}/summary`
- Se taxa de cancelamento > 30%: dispara alerta adicional marcando o afiliado como "atenção"
- Registra o cancelamento no Google Sheets

> **Nota:** Os workflows usam `webhook.site` como destino de notificação para facilitar o teste sem credenciais reais. Para produção, substituir pelos nós de Slack, Discord ou Gmail conforme documentado nos comentários de cada workflow.

---

## Decisões técnicas

### Arquitetura backend

**Service + Repository pattern:** A lógica de negócio fica no `OrderService`, as queries no `OrderRepository`, e os controllers apenas recebem a request e devolvem a response. Essa separação facilita testes unitários e mantém o código coeso — o controller não sabe como o dado é buscado, e o service não sabe como o dado é serializado.

**Jobs idempotentes:** O `upsert` nas operações de sync garante que rodar o comando mais de uma vez nunca gera registros duplicados. A chave de unicidade usada é o ID externo da API (`external_id`).

**Cache com invalidação explícita:** O cache de métricas tem TTL de 5 minutos, mas é invalidado imediatamente sempre que um status muda. Optei por essa abordagem em vez de depender apenas do TTL para garantir que os dados do dashboard reflitam o estado real do sistema logo após uma atualização.

### Banco de dados

**Índice composto `(affiliate_id, status, created_at)`:** A ordem dos campos no índice segue a seletividade dos filtros mais comuns (filtrar por afiliado e status primeiro, depois ordenar por data). Um índice separado por campo seria menos eficiente para queries que combinam os três.

**`softDeletes` em `orders`:** Pedidos nunca são apagados fisicamente. O soft delete preserva o histórico e facilita auditoria. A `order_status_logs` registra cada mudança com timestamp e usuário responsável.

**`cascadeOnDelete` seletivo:** Usado entre `order_items → orders` e `order_status_logs → orders` (os logs só fazem sentido com o pedido). Não usado entre `orders → affiliates` — um afiliado pode ser desativado sem apagar o histórico de pedidos dele.

### Rate limiting na importação

Usado o pacote `spatie/laravel-rate-limited-job-middleware` para limitar as chamadas à Fake Store API a 10 requisições por segundo. Isso evita que um sync de grande volume queime o rate limit da API externa.

### Frontend

**URL como fonte de verdade dos filtros:** Os filtros da tabela de pedidos são sincronizados com a URL via query params usando Vue Router. Isso permite que o usuário copie e cole a URL com os filtros aplicados, e que o browser back/forward funcione corretamente.

**Debounce de 400ms nos filtros de texto:** Evita disparar uma requisição a cada tecla digitada. O valor de 400ms é um equilíbrio entre responsividade e número de chamadas à API.

### N8N desacoplado

O Laravel dispara o evento `OrderStatusChanged` após cada transição bem-sucedida. Um listener dedicado enfileira um `SendWebhookToN8NJob`, que faz o HTTP POST para o N8N com retry e backoff exponencial. Uma falha no N8N não afeta o fluxo principal — o pedido já foi atualizado antes de o Job ser enfileirado.

---

## O que ficou fora do escopo

### Autenticação e autorização
Não foi implementado sistema de login ou controle de acesso por papel (RBAC). Com mais tempo, implementaria autenticação via Laravel Sanctum (tokens de API) e policies do Eloquent para controlar quem pode atualizar status de pedidos.

### Paginação no cursor
A paginação atual usa `offset/limit`. Para 500 mil pedidos, o ideal seria migrar para **cursor-based pagination** com o campo `id` ou `created_at`, eliminando o custo crescente do `OFFSET` em páginas avançadas.

### Testes de frontend
Não foram escritos testes de componentes Vue. Com mais tempo, usaria **Vitest + Vue Test Utils** para cobrir os componentes críticos: tabela com filtros, drawer de status e cards de métrica.

### Monitoramento de filas
O `/api/health` verifica se o worker está ativo, mas não há dashboard de filas. Em produção, integraria **Laravel Horizon** para visualização em tempo real dos jobs pendentes, falhos e processados.

### CI/CD
Não há pipeline de integração contínua. Com mais tempo, adicionaria um workflow GitHub Actions para rodar os testes automaticamente a cada push na branch `develop`.

---

## Variáveis de ambiente

Consulte o `.env.example` na raiz do projeto. Todas as variáveis estão documentadas com descrição e valor de exemplo.

As variáveis críticas que precisam ser alteradas antes de ir para produção estão marcadas com o comentário `# CHANGE IN PRODUCTION`.

---

## Estrutura de pastas relevante

```
├── app/
│   ├── Console/Commands/       # Comando orders:sync
│   ├── Events/                 # OrderStatusChanged
│   ├── Http/
│   │   ├── Controllers/        # Controllers finos
│   │   └── Requests/           # Form Requests de validação
│   ├── Jobs/                   # SyncOrdersJob, SendWebhookToN8NJob
│   ├── Listeners/              # SendOrderStatusWebhook
│   ├── Repositories/           # OrderRepository
│   └── Services/               # OrderService
├── database/
│   └── migrations/             # Todas as migrations
├── n8n/
│   └── workflows/              # Workflows exportados em JSON
├── queries.sql                 # Queries SQL avançadas (A–E)
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## Autor

Desenvolvido como teste técnico para vaga de Desenvolvedor Full Stack Pleno.

Repositório: `https://github.com/seu-usuario/teste-fullstack-seu-nome`