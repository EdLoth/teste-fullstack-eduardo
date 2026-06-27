-- Query A — Top 10 afiliados por receita líquida
-- Uso de CTE para agregar por afiliado e RANK() para rankear
-- sem precisar de subquery aninhada no ORDER BY.
-- Receita líquida = approved apenas (refunded saiu do caixa).

WITH affiliate_revenue AS (
    SELECT
        a.id                                            AS affiliate_id,
        a.name                                          AS affiliate_name,
        COUNT(o.id)                                     AS total_orders,
        SUM(CASE WHEN o.status IN ('approved', 'refunded')
            THEN o.total ELSE 0 END)                    AS gross_revenue,
        SUM(CASE WHEN o.status = 'refunded'
            THEN o.total ELSE 0 END)                    AS refunded_amount,
        SUM(CASE WHEN o.status = 'approved'
            THEN o.total ELSE 0 END)                    AS net_revenue
    FROM affiliates a
    LEFT JOIN orders o
        ON o.affiliate_id = a.id
        AND o.deleted_at IS NULL
    GROUP BY a.id, a.name
)
SELECT
    RANK() OVER (ORDER BY net_revenue DESC)     AS ranking,
    affiliate_id,
    affiliate_name,
    total_orders,
    ROUND(gross_revenue, 2)                     AS gross_revenue,
    ROUND(refunded_amount, 2)                   AS refunded_amount,
    ROUND(net_revenue, 2)                       AS net_revenue
FROM affiliate_revenue
ORDER BY ranking
LIMIT 10;


-- Query B — Cohort mensal dos últimos 6 meses
-- CTE recursiva para gerar os meses garante que meses sem
-- pedidos apareçam com zeros — LEFT JOIN cuida do resto.
-- Evitei subqueries aninhadas conforme especificado.

WITH RECURSIVE months AS (
    SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01') AS month_start
    UNION ALL
    SELECT DATE_SUB(month_start, INTERVAL 1 MONTH)
    FROM months
    WHERE month_start > DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
),
order_stats AS (
    SELECT
        DATE_FORMAT(created_at, '%Y-%m-01')     AS month_start,
        COUNT(*)                                 AS total_orders,
        SUM(CASE WHEN status = 'approved'
            THEN 1 ELSE 0 END)                  AS approved_orders,
        SUM(CASE WHEN status = 'cancelled'
            THEN 1 ELSE 0 END)                  AS cancelled_orders
    FROM orders
    WHERE
        deleted_at IS NULL
        AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-01')
)
SELECT
    DATE_FORMAT(m.month_start, '%Y-%m')         AS month,
    COALESCE(o.total_orders, 0)                 AS total_orders,
    COALESCE(o.approved_orders, 0)              AS approved_orders,
    COALESCE(o.cancelled_orders, 0)             AS cancelled_orders,
    CASE
        WHEN COALESCE(o.total_orders, 0) = 0 THEN 0
        ELSE ROUND(
            COALESCE(o.approved_orders, 0) * 100.0
            / COALESCE(o.total_orders, 0),
        2)
    END                                         AS approval_rate
FROM months m
LEFT JOIN order_stats o ON o.month_start = m.month_start
ORDER BY m.month_start DESC;


-- Query C — Pedidos suspeitos de duplicidade
-- Mesmo affiliate, mesmo valor (calculado dos itens), mesmo dia.
-- GROUP_CONCAT para trazer os IDs envolvidos no grupo.

WITH order_totals AS (
    SELECT
        o.id,
        o.affiliate_id,
        DATE(o.created_at)              AS order_date,
        SUM(oi.quantity * oi.price)     AS calculated_total
    FROM orders o
    INNER JOIN order_items oi ON oi.order_id = o.id
    WHERE o.deleted_at IS NULL
    GROUP BY o.id, o.affiliate_id, DATE(o.created_at)
),
duplicates AS (
    SELECT
        affiliate_id,
        order_date,
        ROUND(calculated_total, 2)              AS duplicated_value,
        COUNT(*)                                AS occurrences,
        GROUP_CONCAT(id ORDER BY id ASC)        AS order_ids
    FROM order_totals
    GROUP BY affiliate_id, order_date, ROUND(calculated_total, 2)
    HAVING COUNT(*) > 1
)
SELECT
    affiliate_id,
    order_date,
    duplicated_value,
    occurrences,
    order_ids
FROM duplicates
ORDER BY occurrences DESC, duplicated_value DESC;


-- Query D — Produto mais vendido por afiliado
-- ROW_NUMBER() particionado por afiliado para pegar só o top 1.
-- Desempate por valor total quando a quantidade for igual.

WITH product_sales AS (
    SELECT
        o.affiliate_id,
        a.name                                      AS affiliate_name,
        p.id                                        AS product_id,
        p.title                                     AS product_name,
        SUM(oi.quantity)                            AS total_quantity,
        ROUND(SUM(oi.quantity * oi.price), 2)       AS total_value
    FROM order_items oi
    INNER JOIN orders o
        ON o.id = oi.order_id
        AND o.deleted_at IS NULL
    INNER JOIN affiliates a ON a.id = o.affiliate_id
    INNER JOIN products p   ON p.id = oi.product_id
    GROUP BY o.affiliate_id, a.name, p.id, p.title
),
ranked_sales AS (
    SELECT
        *,
        ROW_NUMBER() OVER (
            PARTITION BY affiliate_id
            ORDER BY total_quantity DESC, total_value DESC
        ) AS rn
    FROM product_sales
)
SELECT
    affiliate_id,
    affiliate_name,
    product_id,
    product_name,
    total_quantity,
    total_value
FROM ranked_sales
WHERE rn = 1
ORDER BY total_value DESC;


-- Query E — Reescrita da query problemática de produção
--
-- Problemas da versão original:
-- 1. Subquery correlacionada no WHERE roda uma vez por linha (O(n²))
-- 2. DATE(created_at) impede uso do índice na coluna
-- 3. IN + subquery pode degradar para full scan em affiliates
--
-- Original (mantida comentada para referência):
-- SELECT * FROM orders o
-- WHERE o.affiliate_id IN (SELECT id FROM affiliates WHERE status = 'active')
-- AND DATE(o.created_at) >= '2024-01-01'
-- AND (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) > 100
-- ORDER BY o.created_at DESC;
--
-- Correções aplicadas:
-- - CTE pré-agrega order_items uma vez só (O(n))
-- - INNER JOIN em affiliates aproveita índice em affiliate_id
-- - Range direto em created_at usa o índice composto (affiliate_id, status, created_at)
-- - SELECT explícito em vez de SELECT *

WITH order_totals AS (
    SELECT
        order_id,
        SUM(quantity * price) AS total_value
    FROM order_items
    GROUP BY order_id
    HAVING SUM(quantity * price) > 100
)
SELECT
    o.id,
    o.affiliate_id,
    o.status,
    o.total,
    o.created_at
FROM orders o
INNER JOIN affiliates a
    ON a.id = o.affiliate_id
    AND a.status = 'active'
INNER JOIN order_totals ot
    ON ot.order_id = o.id
WHERE
    o.created_at >= '2024-01-01 00:00:00'
    AND o.deleted_at IS NULL
ORDER BY o.created_at DESC;