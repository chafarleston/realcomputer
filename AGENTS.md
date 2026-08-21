# Chatarra — AGENTS.md

> **Repo independiente.** Este proyecto deriva de FacturaFácil pero es ahora un sistema autónomo (repo `realcomputer`) enfocado en **compra/venta de chatarra y reciclaje** (POS Chatarra, multiprecio, estaciones Pesador→Cajero). La infraestructura SUNAT/impresión/caja se conserva de su origen. Algunas vistas pueden conservar el branding "FacturaFácil" por herencia.

## Stack
- Laravel 13.x, PHP 8.2+, MySQL 8.0
- Greenter 5.x (SUNAT XML/SOAP), mpdf (PDF), Endroid QR Code
- Print Server Node.js (localhost:9100), Vite + Tailwind CSS + AdminLTE
- No broadcasting in dev (BROADCAST_DRIVER=log)

## Commands
- `php artisan serve` — dev server
- `php artisan migrate` — run pending migrations
- `php artisan schedule:work` — required for print queue + SUNAT tasks
- `php artisan print:process-queue` — process pending print jobs (runs every min via scheduler)
- `php artisan sunat:send-daily-summary` — batch boletas into daily summary
- `php artisan sunat:check-summaries` — check pending summary tickets
- `php artisan sunat:retry-pending` — retry PENDIENTE/RECHAZADO invoices (boletas→summary, facturas→sendInvoice)
- `php artisan sunat:download-padron` — download+extract SUNAT padrón (deletes the ZIP after extracting; scheduled weekly Sunday 02:00 in Kernel.php)
- `php artisan cache:clear && php artisan view:clear && php artisan route:clear` — full cache flush (do this after any route/view change)
- `php -l path/to/file.php` — PHP syntax check (no linter configured)
- `php artisan tinker --execute="..."` — inline tinker (avoid heredoc in PowerShell)
- `php artisan db:seed --class=ScrapSetupSeeder` — crea pisos/estaciones (Venta 1-5, Compra 1-5) y serie COM (idempotente; el POS los auto-crea igual al primer acceso)
- Tests: `php artisan test` (uses SQLite :memory:, no DB needed)

## Architecture notes
- **Routes**: `web.php` ~250 lines. Public routes (no auth) at top, then `auth` group, then `admin` sub-group. Restaurant/restaurante, caja y POS chatarra están en el grupo `auth` (con `authorize` por permiso), no en el grupo admin.
- **POS Chatarra (Venta/Compra)**: `ScrapPosController` + vista `scrap_pos/index.blade.php`. Dos POS: `GET /pos-venta` y `GET /pos-compra`, cada uno con 5 **estaciones fijas** (`restaurant_tables.pos_mode = venta|compra`). Reutiliza `RestaurantOrder`/`RestaurantOrderItem`/`RestaurantTable`.
  - Cantidades **decimales** (kilos o unidades) × **multiprecio** (Nivel 1-4) = total en vivo.
  - Flujo **Pesador→Cajero**: `sendOrder()` → imprime comanda (slot `productos`) + orden `SENT_TO_KITCHEN` (POR COBRAR) + **bloquea edición** (`assertOrderEditable`); `chargeOrder()` solo cobra pedidos **enviados**.
  - **Polling 10s** `pollStations()` → `GET /scrap-pos/{mode}/stations` (estados LIBRE/PESANDO/POR_COBRAR).
  - **Stock conectado**: compra suma stock; venta valida stock (`assertSaleStock`, acumulado en la orden).
  - **Compras = comprobantes `CO`** con serie `COM`, `sunat_estado = NO_ENVIADO`, **nunca a SUNAT**.
- **Multiprecio (Product)**: `precio` = venta N1, `precio_compra` = compra N1; +`precio_venta_n2..n4`, `precio_compra_n2..n4`. Helpers `priceVenta($nivel)` / `priceCompra($nivel)`. Está en formularios (create/edit), detalle (`products/show`), lista (`/products` → columnas Precio Venta y Precio Compra N1-N4) y **import/export** (`precio_venta_n2..n4`, `precio_compra_n2..n4` en colMap/plantilla/export; `exportSpreadsheet()` usa autoajuste dinámico).
- **Ingresos y Gastos**: módulo `/ingresos-gastos` (`CashMovementController` + modelo `CashMovement`, tabla `cash_movements`). INGRESO suma, GASTO resta del cuadre de caja en vivo.
- **Flujo de Caja (cierre)**: `saldo_final = monto_apertura + ventas + ingresos − compras − gastos`. `close()` excluye compras (CO) de las ventas (`paymentBuckets()` compartido). Resumen = Flujo de Caja del Día + Sobrante/Faltante. Caja abierta muestra saldo en vivo.
- **Kiosko**: Mesa virtual en `restaurant_tables` con `is_for_kiosko=true`. No aparece en floor plan ni gestión. Usa `scopeExcludeKiosko()`.
  - Flujo de 2 pasos: "Enviar a Cocina" → `SENT_TO_KITCHEN`, luego "Cobrar" → `COMPLETED`
  - Numeración `A-001` ligada a caja abierta actual (resetea al cerrar/abrir caja)
  - `confirmOrder()` valida que haya caja abierta antes de crear pedido
  - Botón "Eliminar" disponible; pide admin password si ya fue enviado a cocina
- **SUNAT**: Boletas (03), NC/ND de boletas, and boleta voids go via **Resumen Diario** (SummaryService). Facturas (01) and their NC/ND go via **BillSender** (GreenterService). NV never sent to SUNAT.
- **Permisos (roles)**: `cajero` tiene `view_invoices`, `create_invoices`, `send_sunat` (envía a SUNAT) y `view/open/close_cashregister` (abre y cierra caja). El rol `user` NO tiene `view_invoices` ni `view_cashregisters` (no ve Comprobantes ni Caja). `superadmin` es un valor reservado en la lógica (`isSuperAdmin()`, `hasPermission`, `IsAdmin`), NO un rol de la BD; el acceso total efectivo es vía `admin`.
- **Rutas por permiso (no middleware admin)**: las rutas de comprobantes (`/invoices*`, `/sunat-summaries*`, `/documents/{tipo}`) y de caja (`/cashregisters*`, `/cashregister/open|close`) se protegen con `$this->authorize('permission', 'send_sunat'|'view_invoices'|'create_invoices'|'view_cashregisters'|'open_cashregister'|'close_cashregister')`. El middleware `admin` (IsAdmin) se usa para productos/usuarios/series/empresas/etc.
- **Dividir Cuenta**: modal en el pedido (no-mozo). Reparte items por cantidad en 2+ comprobantes (NV/Boleta/Factura), cada división con su cliente ("Clientes Varios" DNI 88888888 por defecto), tipo doc, método de pago y solo consumo. Marca items pagados con `paid_invoice_id`; el remanente se cobra con "Cobrar"; si no quedan items sin pagar → orden COMPLETED + mesa AVAILABLE.
- **PEM-first certificate**: All Greenter services (`setupSee()`) search for `.pem` file first (OpenSSL 3.0 compatible). Falls back to PKCS12. PEM extracted at upload via OpenSSL 1.1.1 CLI (Git Bash).
- **SOAP username**: Must be only the user part (e.g. `FACTURA1`) without RUC prefix. Greenter concatenates RUC+user automatically (`$ruc.$user`).
- **Series numbering**: Always use `Serie::getNextNumber()`, never query last invoice+1.
- **Clientes Varios**: Fallback DNI 88888888, name "CLIENTES VARIOS" when no customer selected.
- **Búsqueda de productos**: en el Restaurante es SOLO por descripción/nombre (no por código); la búsqueda por `codigo`/`codigo_barras` existe solo en el POS.
- **POS → caja**: `PosController::store()` actualiza la caja en vivo (`cantidad_ventas`, `total_ventas`, campo del método de pago). La apertura automática del cajón ocurre en pagos EFECTIVO (server-side `GET http://localhost:9100/open-drawer`); también hay botón manual "Caja" en POS y Restaurante.
- **Precuenta IGV**: `PlainTextTicket::prebillTicket()` usa `Company::getActiveIgvPercent()` (dinámico: 18% general / 10.5% restaurante), NO un valor fijo.
- **removeItem()**: item PENDING (no enviado a cocina) se borra físicamente; SENT/READY/DELIVERED se marca CANCELLED con `cancelled_from/at/by` y requiere password admin.
- **Polling**: `pollActiveOrders` + `pollTableLocks` every 10s, `pollPrintServer` every 10s, `loadKitchenOrders` every 5s, `pollStations` (POS chatarra) every 10s. Silent `.catch()` for polling, `showError()` for user actions.
- **3 printer slots**: productos, precuenta, caja (se renombró `cocina-1`→`productos` y se eliminaron cocina-2, bar-1, autopedido, precuenta2, precuenta3 vía migración `2026_08_07_000005`; los slots `despacho_compra`/`despacho_venta` añadidos en `000010` se eliminaron en `2026_08_07_000012`). **Destino único**: `print_destination = 'productos'`; la comanda del POS siempre imprime en el slot `productos` con cabecera "PRODUCTOS".

## Repo quirks
- Print server requires Node.js (see `print-server-node/`). The `scheduler.vbs` starts both Laravel scheduler and print server on Windows.
- All JS fetch calls must include `Accept: application/json` and `X-Requested-With: XMLHttpRequest` (silent redirect-to-login otherwise).
- Certificate upload must NOT use `mimes:p12,pfx` validation (rejects valid files). Use OpenSSL 1.1.1 CLI to verify.
- Table locks expire after 5 minutes. `unlockAllTables` endpoint available for admins.
- KDS has separate sections: "MOZO — Pedidos de Mesas" vs "KIOSKO — Autoservicio". Determined by `order_type` field.
- The `PENDING_PAYMENT` status for kiosko orders is in the `status` ENUM of `restaurant_orders` (added via migration, not in original ENUM).
- **Elementos Auxiliares**: New module with CRUD at Restaurante → Elementos Auxiliares. Chips appear in product modal (POS + autopedido). Stored as JSON array in `restaurant_order_items.auxiliary_items`. Displayed in KDS and kitchen tickets.
- **Autopedido modal**: Product selection opens a modal with quantity (+/−), kitchen notes (with virtual keyboard), and auxiliary items chips. Cart stores notes + aux items per product.
- **Virtual keyboard**: Used for search input AND modal notes textarea. Driven by `activeInput` variable — `openKeyboard(input)` sets it, `pressKey`/`pressBackspace` write to `activeInput.value` using `selectionStart/End`.
- **Emojis in thermal tickets**: Do NOT use emojis (🧾, ✅, etc.) in ESC/POS tickets. Printers use CP850 encoding which garbles UTF-8 emojis. Use plain text alternatives.
- **Print autopedido ticket**: `PrintService::printAutoPedidoTicket()` was missing `$this->processQueue()` — always verify that print methods call `processQueue()` after `queuePrint()`.
- **PlainTextTicket::kitchenTicket**: Had a broken `$dests` filter that skipped ALL items (`$dests = ['cocina'=>'', ...]` where `$dest !== ''` was always true). Removed since `printKitchenOrder` already groups by destination.
- **PrintService::printInvoice()**: `invoiceTicket()` is a stub (returns `''`); `printInvoice()` does NOT queue empty data (`if ($data === '') return;`). El comprobante se imprime por PDF de Greenter (`/pos/print/{id}/{format}`).
- **buildInvoice()**: private helper con firma `($invoice, $company)` — no es parte de la API pública.
- **cancelNotificationGrouped()**: imprime "Anulado por" (usa `cancelledBy` del primer item del grupo). Firma: `($order, $format='text', $dest='cocina')`.
- **Extensions**: composer.json requiere `ext-openssl`, `ext-xml`, `ext-zip`, `ext-soap`, `ext-intl` (soap = SUNAT SOAP; intl = NumberFormatter del total en letras).
- **Kernel.php**: `print:process-queue` agendado UNA vez (`everyMinute()`). `schedule:run` se invoca vía `scheduler.vbs` o una tarea de Windows creada fuera del repo.
- **`sunat:download-padron`**: elimina el ZIP tras extraer (el `.txt` del padrón se conserva).
- `DOCUMENTACION_SISTEMA.md` contains detailed docs (~3860 lines). Read it for SUNAT error codes, module docs, and troubleshooting. La auditoría doc↔código está en `INFORME_DISCREPANCIAS.md` (42 ítems, todos los accionables resueltos).
- **`invoices.sunat_estado`** es un ENUM: `PENDIENTE|ENVIADO|ACEPTADO|RECHAZADO|ANULADO|NO_ENVIADO` (NO_ENVIADO agregado en `2026_08_07_000007` para compras CO).
- **Serie COM**: `series.serie` es `varchar(4)`; el fallback de serie para compras usa `'COM'` (3 chars). Nunca generar `NV001` (5 chars) en el fallback automático — usar `NV01`/`F001`/`B001`/`COM`.

## Testing
- `php artisan test` — Unit + Feature (SQLite in-memory)
- No end-to-end or integration tests against real SUNAT
- Print queue not testable without a running print server

<!-- gitnexus:start -->
# GitNexus — Code Intelligence

This project is indexed by GitNexus as **facturafacil** (2898 symbols, 5611 relationships, 222 execution flows). Use the GitNexus MCP tools to understand code, assess impact, and navigate safely.

> Index stale? Run `node .gitnexus/run.cjs analyze` from the project root — it auto-selects an available runner. No `.gitnexus/run.cjs` yet? `npx gitnexus analyze` (npm 11 crash → `npm i -g gitnexus`; #1939).

## Always Do

- **MUST run impact analysis before editing any symbol.** Before modifying a function, class, or method, run `impact({target: "symbolName", direction: "upstream"})` and report the blast radius (direct callers, affected processes, risk level) to the user.
- **MUST run `detect_changes()` before committing** to verify your changes only affect expected symbols and execution flows. For regression review, compare against the default branch: `detect_changes({scope: "compare", base_ref: "main"})`.
- **MUST warn the user** if impact analysis returns HIGH or CRITICAL risk before proceeding with edits.
- When exploring unfamiliar code, use `query({search_query: "concept"})` to find execution flows instead of grepping. It returns process-grouped results ranked by relevance.
- When you need full context on a specific symbol — callers, callees, which execution flows it participates in — use `context({name: "symbolName"})`.
- For security review, `explain({target: "fileOrSymbol"})` lists taint findings (source→sink flows; needs `analyze --pdg`).

## Never Do

- NEVER edit a function, class, or method without first running `impact` on it.
- NEVER ignore HIGH or CRITICAL risk warnings from impact analysis.
- NEVER rename symbols with find-and-replace — use `rename` which understands the call graph.
- NEVER commit changes without running `detect_changes()` to check affected scope.

## Resources

| Resource | Use for |
|----------|---------|
| `gitnexus://repo/facturafacil/context` | Codebase overview, check index freshness |
| `gitnexus://repo/facturafacil/clusters` | All functional areas |
| `gitnexus://repo/facturafacil/processes` | All execution flows |
| `gitnexus://repo/facturafacil/process/{name}` | Step-by-step execution trace |

## CLI

| Task | Read this skill file |
|------|---------------------|
| Understand architecture / "How does X work?" | `.claude/skills/gitnexus/gitnexus-exploring/SKILL.md` |
| Blast radius / "What breaks if I change X?" | `.claude/skills/gitnexus/gitnexus-impact-analysis/SKILL.md` |
| Trace bugs / "Why is X failing?" | `.claude/skills/gitnexus/gitnexus-debugging/SKILL.md` |
| Rename / extract / split / refactor | `.claude/skills/gitnexus/gitnexus-refactoring/SKILL.md` |
| Tools, resources, schema reference | `.claude/skills/gitnexus/gitnexus-guide/SKILL.md` |
| Index, status, clean, wiki CLI commands | `.claude/skills/gitnexus/gitnexus-cli/SKILL.md` |

<!-- gitnexus:end -->
