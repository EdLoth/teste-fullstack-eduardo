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
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Vue.js 3 (Composition API), Tailwind CSS |
| Banco de dados | MySQL 8 |
| Cache e filas | Redis 7 |
| Containers | Docker + Docker Compose |
| Automação | N8N |
| Testes | Pest (PHP) |

---

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e rodando
- Git

> Não é necessário ter PHP, Node.js ou MySQL instalados localmente. Tudo roda dentro dos containers — incluindo o build do frontend.

---

## Instalação e execução

### 1. Clone o repositório

```bash
git clone https://github.com/EdLoth/teste-fullstack-eduardo.git
cd teste-fullstack-eduardo
```

### 2. Configure as variáveis de ambiente

```bash
cp .env.example .env
```

Os valores do `.env.example` já funcionam com o Docker Compose sem alterações. As variáveis marcadas com `CHANGE_ME_IN_PRODUCTION` devem ser trocadas antes de ir para produção.

### 3. Suba os containers

```bash
docker compose up -d
```

Isso inicializa automaticamente os 7 serviços:
- `app` — Laravel (PHP 8.4-FPM)
- `nginx` — Servidor web (porta 8000)
- `mysql` — MySQL 8
- `redis` — Redis 7 (cache e filas)
- `worker` — Fila de jobs (`php artisan queue:work`)
- `node` — Build do frontend Vue.js (roda `npm install && npm run build`)
- `n8n` — Plataforma de automação (porta 5678)

> O container `node` compila o frontend automaticamente. Aguarde até ele finalizar (1-2 minutos na primeira vez) antes de acessar a aplicação.

### 4. Gere a application key e rode as migrations

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. Importe os pedidos da API externa

```bash
docker compose exec app php artisan orders:sync
```

A aplicação estará disponível em:
- **Dashboard:** http://localhost:8000
- **API Docs:** http://localhost:8000/api/docs
- **N8N:** http://localhost:5678

---

## Como rodar os testes

```bash
# Todos os testes
docker compose exec app php artisan test

# Filtrar por suíte específica
docker compose exec app php artisan test --filter=StateMachineTest
docker compose exec app php artisan test --filter=MetricsTest
docker compose exec app php artisan test --filter=OrdersSyncTest
```

### Suítes disponíveis

| Filtro | O que testa |
|---|---|
| `StateMachineTest` | Transições válidas e inválidas da máquina de estados (7 testes) |
| `MetricsTest` | Endpoint `/api/orders/metrics` com e sem cache Redis (5 testes) |
| `OrdersSyncTest` | Integração do comando `orders:sync` com API mockada (2 testes) |

---

## Endpoints da API

> Documentação interativa disponível em **http://localhost:8000/api/docs**

### Pedidos

```
GET  /api/orders
```
Lista paginada de pedidos (20 por página).

**Query params disponíveis:**

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `status` | string | `pending`, `approved`, `cancelled`, `refunded` |
| `affiliate_id` | integer | Filtrar por afiliado |
| `search` | string | Buscar por nome do afiliado |
| `date_from` | date | `YYYY-MM-DD` |
| `date_to` | date | `YYYY-MM-DD` |
| `min_value` | numeric | Valor mínimo do pedido |
| `max_value` | numeric | Valor máximo do pedido |
| `sort_by` | string | `id`, `total`, `status`, `created_at` |
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
Métricas agregadas. Cache de 5 minutos via Redis. Invalidado automaticamente ao atualizar qualquer status. Aceita `date_from` e `date_to` para filtrar por período.

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

Toda transição é registrada em `order_status_logs` com timestamp e usuário responsável. Após cada transição bem-sucedida, um evento `OrderStatusChanged` é disparado e o N8N é notificado via webhook assíncrono.

---

## Importação de pedidos

O comando `php artisan orders:sync` busca dados da [Fake Store API](https://fakestoreapi.com) e os persiste localmente de forma assíncrona.

**Funcionamento:**

1. O comando busca carts, users e products da API com rate limiting de 5 requisições por segundo via `RateLimiter` nativo do Laravel.
2. Os carts são divididos em chunks de 5 e um `SyncOrdersJob` é enfileirado por chunk.
3. Cada Job processa e faz `upsert` dos afiliados, produtos, pedidos e itens, evitando duplicatas.
4. Jobs com falha são reprocessados automaticamente até **3 tentativas** com backoff de 5 segundos.
5. O comando é **idempotente** — pode ser rodado múltiplas vezes sem gerar inconsistências.

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
3. Clique em **+** → **New workflow** → **⋮** → **Import from file**
4. Importe os arquivos `.json` da pasta `n8n/workflows/`

### Workflow 1 — Pedido aprovado (`order-approved.json`)

- **Gatilho:** `POST /webhook/order-approved`
- Filtra eventos com `new_status = "approved"`
- Formata mensagem: `✅ Pedido #ID aprovado — R$ VALOR | Afiliado #ID`
- Envia para webhook (configurado via `webhook.site` — substituir por Slack/Discord/Gmail em produção)
- Registra o evento em Google Sheets

### Workflow 2 — Pedido cancelado (`order-cancelled.json`)

- **Gatilho:** `POST /webhook/order-cancelled`
- Filtra eventos com `new_status = "cancelled"`
- Consulta `/api/affiliates/{affiliate_id}/summary`
- Se taxa de cancelamento > 30%: dispara alerta adicional marcando o afiliado como "atenção"
- Registra o cancelamento no Google Sheets

> Os workflows usam `webhook.site` como destino de notificação para facilitar o teste sem credenciais reais. Para produção, substituir pelos nós de Slack, Discord ou Gmail.

---

## Decisões técnicas

**Service + Repository pattern:** A lógica de negócio fica no `OrderService`, as queries no `OrderRepository`, e os controllers apenas recebem a request e devolvem a response. Essa separação facilita testes unitários e mantém o código coeso.

**Jobs idempotentes:** O `upsert` nas operações de sync garante que rodar o comando mais de uma vez nunca gera registros duplicados. A chave de unicidade é o `external_id` vindo da Fake Store API.

**Cache com invalidação explícita:** O cache de métricas tem TTL de 5 minutos e é invalidado imediatamente via `Cache::forget()` sempre que um status muda. Métricas com filtro de data não são cacheadas — são específicas demais para aproveitar cache compartilhado.

**Rate limiting nativo:** O rate limiting nas chamadas HTTP usa o `RateLimiter` nativo do Laravel, limitado a 5 requisições por segundo, sem dependência de pacotes externos.

**Índice composto `(affiliate_id, status, created_at)`:** A ordem dos campos segue a seletividade dos filtros mais comuns. Um índice separado por campo seria menos eficiente para queries que combinam os três.

**`softDeletes` em `orders`:** Pedidos nunca são apagados fisicamente. O soft delete preserva o histórico e facilita auditoria.

**`cascadeOnDelete` seletivo:** Usado entre `order_items → orders` e `order_status_logs → orders`. Não usado entre `orders → affiliates` — um afiliado pode ser desativado sem apagar o histórico de pedidos.

**URL como fonte de verdade dos filtros:** Os filtros da tabela são sincronizados com a URL via query params, permitindo copiar e colar a URL com os filtros aplicados.

**Build do frontend via container Node:** O `docker-compose.yml` inclui um container Node que roda `npm install && npm run build` automaticamente, garantindo que `docker compose up -d` deixe o frontend funcional sem passos manuais.

**N8N desacoplado:** O Laravel dispara o evento `OrderStatusChanged` após cada transição. Um listener enfileira um `SendWebhookToN8NJob` com retry e backoff exponencial. Falhas no N8N não afetam o fluxo principal.

---

## O que ficou fora do escopo

**Autenticação e autorização:** Não foi implementado sistema de login ou RBAC. Com mais tempo, implementaria autenticação via Laravel Sanctum e policies do Eloquent.

**Paginação no cursor:** A paginação atual usa `offset/limit`. Para 500 mil pedidos, o ideal seria cursor-based pagination com `id` ou `created_at`, eliminando o custo crescente do `OFFSET`.

**Testes de frontend:** Não foram escritos testes de componentes Vue. Com mais tempo, usaria Vitest + Vue Test Utils para cobrir tabela com filtros, drawer e cards de métrica.

**Monitoramento de filas:** O `/api/health` verifica se o worker está ativo, mas não há dashboard de filas. Em produção, integraria Laravel Horizon.

**CI/CD:** Sem pipeline de integração contínua. Com mais tempo, adicionaria GitHub Actions para rodar os testes automaticamente a cada push.

---

## Variáveis de ambiente

Consulte o `.env.example` na raiz do projeto. Todas as variáveis estão documentadas com valores de exemplo. As variáveis marcadas com `CHANGE_ME_IN_PRODUCTION` devem ser alteradas antes de ir para produção.

---

## Estrutura de pastas relevante

```
├── app/
│   ├── Console/Commands/       # Comando orders:sync (com rate limiting)
│   ├── Events/                 # OrderStatusChanged
│   ├── Http/
│   │   ├── Controllers/        # Controllers finos
│   │   └── Requests/           # Form Requests de validação
│   ├── Jobs/                   # SyncOrdersJob, SendWebhookToN8NJob
│   ├── Listeners/              # SendOrderStatusWebhook
│   ├── Repositories/           # OrderRepository
│   └── Services/               # OrderService
├── database/
│   ├── factories/              # AffiliateFactory, OrderFactory
│   └── migrations/             # Todas as migrations
├── n8n/
│   └── workflows/              # Workflows exportados em JSON
├── resources/
│   ├── js/                     # Vue.js 3 — App, componentes, table/
│   └── views/                  # welcome.blade.php, api-docs.blade.php
├── tests/
│   ├── Feature/                # MetricsTest, OrdersSyncTest
│   └── Unit/                   # StateMachineTest
├── queries.sql                 # Queries SQL avançadas (A–E)
├── docker-compose.yml
├── Dockerfile
├── .env.example
└── README.md
```

---

## Autor

Desenvolvido por **Eduardo Ramos** como teste técnico para vaga de Desenvolvedor Full Stack Pleno.

Repositório: `https://github.com/EdLoth/teste-fullstack-eduardo`
