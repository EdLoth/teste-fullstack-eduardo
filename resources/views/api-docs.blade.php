@verbatim
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management API — Documentação</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #0f0f11; color: #e4e4e7; line-height: 1.6; }
        a { color: #60a5fa; text-decoration: none; }

        .layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; background: #18181b; border-right: 1px solid #27272a; padding: 24px 0; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-logo { padding: 0 24px 24px; border-bottom: 1px solid #27272a; margin-bottom: 16px; }
        .sidebar-logo h1 { font-size: 15px; font-weight: 600; color: #fff; }
        .sidebar-logo p { font-size: 12px; color: #71717a; margin-top: 2px; }
        .sidebar-section { padding: 8px 24px 4px; font-size: 11px; font-weight: 600; color: #52525b; text-transform: uppercase; letter-spacing: 0.08em; }
        .sidebar a { display: block; padding: 6px 24px; font-size: 13px; color: #a1a1aa; transition: color 0.15s; }
        .sidebar a:hover { color: #fff; }
        .sidebar a.active { color: #60a5fa; }

        /* Main */
        .main { margin-left: 260px; flex: 1; padding: 48px; max-width: 900px; }

        /* Endpoint */
        .endpoint { margin-bottom: 48px; border: 1px solid #27272a; border-radius: 12px; overflow: hidden; }
        .endpoint-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; background: #18181b; cursor: pointer; }
        .method { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.05em; }
        .method.get    { background: #1d4ed8; color: #bfdbfe; }
        .method.post   { background: #15803d; color: #bbf7d0; }
        .endpoint-path { font-family: monospace; font-size: 14px; color: #e4e4e7; }
        .endpoint-desc { font-size: 13px; color: #71717a; margin-left: auto; }
        .endpoint-body { padding: 20px; border-top: 1px solid #27272a; }

        /* Params */
        .section-label { font-size: 11px; font-weight: 600; color: #52525b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
        th { text-align: left; padding: 8px 12px; background: #27272a; color: #a1a1aa; font-weight: 500; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px 12px; border-bottom: 1px solid #27272a; color: #d4d4d8; }
        td code { font-family: monospace; background: #27272a; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #a5b4fc; }

        /* Response */
        pre { background: #18181b; border: 1px solid #27272a; border-radius: 8px; padding: 16px; font-size: 12px; overflow-x: auto; color: #86efac; font-family: monospace; line-height: 1.6; }

        /* Badge */
        .badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 99px; font-weight: 500; }
        .badge.required { background: #7f1d1d; color: #fca5a5; }
        .badge.optional { background: #27272a; color: #71717a; }

        /* Hero */
        .hero { margin-bottom: 48px; }
        .hero h2 { font-size: 28px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .hero p { font-size: 15px; color: #71717a; max-width: 600px; }
        .base-url { display: inline-flex; align-items: center; gap-8px; background: #18181b; border: 1px solid #27272a; border-radius: 8px; padding: 10px 16px; margin-top: 16px; font-family: monospace; font-size: 13px; color: #60a5fa; }
    </style>
</head>
<body>
<div class="layout">

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-logo">
            <h1>Order Management API</h1>
            <p>v1.0.0 — REST</p>
        </div>
        <div class="sidebar-section">Orders</div>
        <a href="#list-orders">GET /api/orders</a>
        <a href="#get-order">GET /api/orders/{id}</a>
        <a href="#metrics">GET /api/orders/metrics</a>
        <a href="#update-status">POST /api/orders/{id}/status</a>
        <div class="sidebar-section">Affiliates</div>
        <a href="#affiliate-summary">GET /api/affiliates/{id}/summary</a>
        <div class="sidebar-section">System</div>
        <a href="#health">GET /api/health</a>
    </nav>

    <!-- Main -->
    <main class="main">

        <div class="hero">
            <h2>API Documentation</h2>
            <p>API REST para gestão de pedidos de e-commerce com múltiplos afiliados. Todas as respostas seguem o padrão <code style="background:#27272a;padding:2px 6px;border-radius:4px;font-size:12px">{ data, meta, errors }</code>.</p>
            <div class="base-url">Base URL: http://localhost:8000</div>
        </div>

        <!-- GET /api/orders -->
        <div class="endpoint" id="list-orders">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/orders</span>
                <span class="endpoint-desc">Lista paginada de pedidos</span>
            </div>
            <div class="endpoint-body">
                <div class="section-label">Query Parameters</div>
                <table>
                    <tr><th>Parâmetro</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr>
                    <tr><td><code>status</code></td><td>string</td><td><span class="badge optional">opcional</span></td><td>pending, approved, cancelled, refunded</td></tr>
                    <tr><td><code>affiliate_id</code></td><td>integer</td><td><span class="badge optional">opcional</span></td><td>Filtrar por afiliado</td></tr>
                    <tr><td><code>search</code></td><td>string</td><td><span class="badge optional">opcional</span></td><td>Buscar por nome do afiliado</td></tr>
                    <tr><td><code>date_from</code></td><td>date</td><td><span class="badge optional">opcional</span></td><td>Data inicial (YYYY-MM-DD)</td></tr>
                    <tr><td><code>date_to</code></td><td>date</td><td><span class="badge optional">opcional</span></td><td>Data final (YYYY-MM-DD)</td></tr>
                    <tr><td><code>min_value</code></td><td>number</td><td><span class="badge optional">opcional</span></td><td>Valor mínimo do pedido</td></tr>
                    <tr><td><code>max_value</code></td><td>number</td><td><span class="badge optional">opcional</span></td><td>Valor máximo do pedido</td></tr>
                    <tr><td><code>sort_by</code></td><td>string</td><td><span class="badge optional">opcional</span></td><td>id, total, status, created_at</td></tr>
                    <tr><td><code>sort_dir</code></td><td>string</td><td><span class="badge optional">opcional</span></td><td>asc ou desc</td></tr>
                    <tr><td><code>page</code></td><td>integer</td><td><span class="badge optional">opcional</span></td><td>Número da página (padrão: 1)</td></tr>
                </table>
                <div class="section-label">Response 200</div>
                <pre>{{
  "data": [
    {
      "id": 1,
      "external_id": 1,
      "affiliate_id": 1,
      "status": "pending",
      "total": "798.04",
      "affiliate": { "id": 1, "name": "john doe" },
      "items": [...]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 7
  }
}}</pre>
            </div>
        </div>

        <!-- GET /api/orders/{id} -->
        <div class="endpoint" id="get-order">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/orders/{id}</span>
                <span class="endpoint-desc">Detalhe do pedido com itens e histórico</span>
            </div>
            <div class="endpoint-body">
                <div class="section-label">Path Parameters</div>
                <table>
                    <tr><th>Parâmetro</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr>
                    <tr><td><code>id</code></td><td>integer</td><td><span class="badge required">obrigatório</span></td><td>ID do pedido</td></tr>
                </table>
                <div class="section-label">Response 200</div>
                <pre>{{
  "data": {
    "id": 1,
    "status": "pending",
    "total": "798.04",
    "affiliate": { "id": 1, "name": "john doe" },
    "items": [
      { "id": 1, "quantity": 4, "price": "109.95", "product": { "title": "Fjallraven Backpack" } }
    ],
    "status_logs": [
      { "previous_status": null, "new_status": "pending", "changed_by": "system" }
    ]
  }
}}</pre>
                <div class="section-label" style="margin-top:16px">Response 404</div>
                <pre>{{ "data": null, "errors": ["Pedido não encontrado."] }}</pre>
            </div>
        </div>

        <!-- GET /api/orders/metrics -->
        <div class="endpoint" id="metrics">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/orders/metrics</span>
                <span class="endpoint-desc">Métricas agregadas — cache Redis 5 min</span>
            </div>
            <div class="endpoint-body">
                <div class="section-label">Query Parameters</div>
                <table>
                    <tr><th>Parâmetro</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr>
                    <tr><td><code>date_from</code></td><td>date</td><td><span class="badge optional">opcional</span></td><td>Filtrar métricas a partir desta data</td></tr>
                    <tr><td><code>date_to</code></td><td>date</td><td><span class="badge optional">opcional</span></td><td>Filtrar métricas até esta data</td></tr>
                </table>
                <div class="section-label">Response 200</div>
                <pre>{{
  "data": {
    "total_orders": 7,
    "total_revenue": "798.04",
    "pending_revenue": "3893.23",
    "refunded_revenue": "0.00",
    "average_ticket": "798.040000",
    "pending_count": 6,
    "approved_count": 1,
    "cancelled_count": 0,
    "refunded_count": 0
  }
}}</pre>
            </div>
        </div>

        <!-- POST /api/orders/{id}/status -->
        <div class="endpoint" id="update-status">
            <div class="endpoint-header">
                <span class="method post">POST</span>
                <span class="endpoint-path">/api/orders/{id}/status</span>
                <span class="endpoint-desc">Atualiza status — máquina de estados</span>
            </div>
            <div class="endpoint-body">
                <div class="section-label">Path Parameters</div>
                <table>
                    <tr><th>Parâmetro</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr>
                    <tr><td><code>id</code></td><td>integer</td><td><span class="badge required">obrigatório</span></td><td>ID do pedido</td></tr>
                </table>
                <div class="section-label">Request Body</div>
                <table>
                    <tr><th>Campo</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr>
                    <tr><td><code>status</code></td><td>string</td><td><span class="badge required">obrigatório</span></td><td>approved, cancelled ou refunded</td></tr>
                    <tr><td><code>reason</code></td><td>string</td><td><span class="badge optional">opcional</span></td><td>Motivo da mudança de status</td></tr>
                </table>
                <div class="section-label">Transições válidas</div>
                <table>
                    <tr><th>De</th><th>Para</th></tr>
                    <tr><td><code>pending</code></td><td><code>approved</code> ou <code>cancelled</code></td></tr>
                    <tr><td><code>approved</code></td><td><code>refunded</code></td></tr>
                </table>
                <div class="section-label" style="margin-top:16px">Response 200</div>
                <pre>{{ "data": { "id": 1, "status": "approved", ... } }}</pre>
                <div class="section-label" style="margin-top:16px">Response 422</div>
                <pre>{{ "data": null, "errors": ["Transição inválida: não é possível mover de 'approved' para 'cancelled'."] }}</pre>
            </div>
        </div>

        <!-- GET /api/affiliates/{id}/summary -->
        <div class="endpoint" id="affiliate-summary">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/affiliates/{id}/summary</span>
                <span class="endpoint-desc">Resumo do afiliado</span>
            </div>
            <div class="endpoint-body">
                <div class="section-label">Path Parameters</div>
                <table>
                    <tr><th>Parâmetro</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr>
                    <tr><td><code>id</code></td><td>integer</td><td><span class="badge required">obrigatório</span></td><td>ID do afiliado</td></tr>
                </table>
                <div class="section-label">Response 200</div>
                <pre>{{
  "data": {
    "affiliate": { "id": 1, "name": "john doe", "email": "john@gmail.com" },
    "total_orders": 2,
    "total_revenue": "798.04",
    "average_ticket": "798.040000",
    "cancellation_rate": 0
  }
}}</pre>
            </div>
        </div>

        <!-- GET /api/health -->
        <div class="endpoint" id="health">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/health</span>
                <span class="endpoint-desc">Status dos serviços dependentes</span>
            </div>
            <div class="endpoint-body">
                <div class="section-label">Response 200 — Todos operacionais</div>
                <pre>{{
  "status": "ok",
  "services": {
    "mysql": "ok",
    "redis": "ok",
    "worker": "ok"
  }
}}</pre>
                <div class="section-label" style="margin-top:16px">Response 503 — Algum serviço com problema</div>
                <pre>{{
  "status": "degraded",
  "services": {
    "mysql": "ok",
    "redis": "ok",
    "worker": "error"
  }
}}</pre>
            </div>
        </div>

    </main>
</div>
</body>
</html>
@endverbatim