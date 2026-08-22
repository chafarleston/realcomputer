# Chatarra — Sistema de Compra/Venta de Chatarra y Reciclaje

Sistema independiente para la **compra y venta de chatarra y materiales reciclables**, con control de estaciones (Pesador → Cajero), multiprecio por niveles, stock integrado y facturación electrónica SUNAT (heredada de su origen FacturaFácil).

El proyecto deriva de **FacturaFácil** (sistema de facturación electrónica y restaurante), pero es ahora un repositorio independiente con foco en el negocio de chatarra.

---

## Módulos del Sistema

### POS Chatarra (Venta / Compra)
- **Dos POS**: `/pos-venta` y `/pos-compra`, cada uno con **5 estaciones fijas** (Venta 1-5, Compra 1-5)
- Flujo **Pesador → Cajero**: el pesador pesa y envía el pedido, el cajero cobra
- Cantidades **decimales** (kilos) × **multiprecio** (Nivel 1-4) = total en vivo
- **Stock conectado**: la compra suma stock; la venta valida stock disponible
- **Compras** = comprobantes `CO` con serie `COM`, `sunat_estado = NO_ENVIADO`, nunca se envían a SUNAT
- Polling de estaciones cada 10s (estados LIBRE/PESANDO/POR_COBRAR)

### Multiprecio
- Cada producto tiene precio por niveles: **Venta N1-N4** y **Compra N1-N4**
- Helpers `priceVenta($nivel)` / `priceCompra($nivel)`
- Incluido en formularios, detalle, listado e import/export de productos

### Facturación Electrónica SUNAT
- Emisión de **Facturas** (01), **Boletas** (03), **Notas de Venta** (NV), **Notas de Crédito** (07), **Notas de Débito** (08) y **Compras** (CO)
- Envío a SUNAT por tipo de documento: **Facturas** → Greenter (BillSender); **Boletas** → Resumen Diario (`SummaryService`); **NV** no se envían a SUNAT; **CO** nunca se envían
- Firma digital con certificado **PEM-first** (busca `{ruc}_certificate.pem`; fallback a `.p12/.pfx` con contraseña)
- PDF A4 y Ticket 80mm con código QR, descarga de XML firmado y CDR
- Series configurables por tipo de documento

### Caja Registradora
- Apertura y cierre con **Nombre de referencia** (ej: "25-05-mañana", "25-05-tarde")
- Resumen en vivo durante el turno: ventas + ingresos − compras − gastos
- **Ingresos y Gastos** (`/ingresos-gastos`) modifican el cuadre en vivo
- **Bloqueo de cierre** si hay estaciones/mesas abiertas
- Resumen = Flujo de Caja del Día, con monto de cierre calculado automáticamente (sin ingreso manual)

### Inventario
- Productos **simples** y **compuestos** (un compuesto descuenta stock de sus componentes; no maneja stock propio)
- **Cantidades decimales** (kilos) con stock a 4 decimales
- Compras con proveedores que actualizan **stock** y **precio de compra**
- **Consumo Interno** (salidas de stock): mermas, sin generar venta
- Reporte de Inventario con totales a precio venta/costo y exportación Excel/PDF
- Importación/exportación de productos y duplicado con código secuencial

### Reportes de Compras y Ventas
- Módulo `/reportes` para ventas (≠CO) y compras (CO)
- Periodos: **diario**, **semanal** y **mensual** con navegación ±
- Selección: **todos los productos**, **por categoría** o **varios productos** (multiselección)
- KPIs (documentos, cantidad, importe, IGV), detalle por producto, resumen diario y lista de comprobantes
- Exportación a **Excel**

### Control de Personal (Asistencia)
- Personal con horarios, marcación sin login (kiosco `/marcar`)
- Reglas de tardanza, faltas graves, suspensión, descuentos
- Reportes PDF/Excel

### Impresión Térmica ESC/POS
- **Arquitectura híbrida**: Laravel encola los trabajos y los envía vía HTTP al Print Server local (Node.js)
- **Slots de impresora**: `productos`, `precuenta`, `caja`
- **Comprobante del POS Chatarra**: tras cobrar, el botón **80mm** imprime automático en el slot `caja` (ESC/POS, `invoiceThermalTicket`, sin QR); el **A4** abre el PDF (Nota de Compra o Greenter según modo)
- Encoding CP850 (ñ, tildes), cola con reintentos (hasta 3), apertura del cajón de efectivo

### Roles y Permisos
- Roles: **Administrador**, **Cajero**, **Mozo**, **Usuario** (`superadmin` es un valor reservado en la lógica, no un rol de la BD)
- Permisos por ruta: comprobantes y caja se protegen por permiso (`send_sunat`, `view_invoices`, `open/close_cashregister`, etc.), no por middleware admin
- El rol `cajero` abre/cierra caja y envía a SUNAT; el rol `usuario` no ve Comprobantes ni Caja

### Gestión de Empresas
- Soporte multi-empresa con series separadas
- Configuración de **IGV**: General (18%) o Reducido Restaurante (10.5%), ambos editables
- Certificado digital por empresa, datos SUNAT, logotipo

---

## Requisitos

- **PHP** 8.2+
- **MySQL** 8.0+ / MariaDB 10.4+
- **Composer**
- **Node.js** 18+ (para Print Server)
- Extensiones PHP: `openssl`, `xml`, `zip`, `soap`, `intl`, `mbstring`, `pdo_mysql`, `curl`

---

## Instalación

```bash
# 1. Dependencias PHP
composer install

# 2. Configurar .env
cp .env.example .env
# Editar DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Generar key
php artisan key:generate

# 3. Migrar y seedear
php artisan migrate
php artisan db:seed
php artisan db:seed --class=ScrapSetupSeeder   # crea pisos/estaciones y serie COM (idempotente)

# 4. Link storage
php artisan storage:link

# 5. Print Server (en cada máquina cliente)
cd print-server-node
npm install
```

## Comandos Útiles

```bash
php artisan serve                  # dev server
php artisan migrate                # migraciones pendientes
php artisan schedule:work          # cola de impresión + tareas SUNAT
php artisan print:process-queue    # procesar cola de impresión
php artisan sunat:send-daily-summary
php artisan sunat:retry-pending
php artisan sunat:download-padron  # padrón SUNAT (semanal)
php artisan db:seed --class=ScrapSetupSeeder
php artisan test                   # Unit + Feature (SQLite en memoria)
```

---

## Licencia

MIT