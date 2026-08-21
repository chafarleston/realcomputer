# TRD — Technical Requirements Document

## Chatarra: Especificación Técnica del Sistema

> Sistema independiente derivado de **FacturaFácil** (repo `realcomputer`), enfocado en compra/venta de chatarra y reciclaje. Conserva la infraestructura SUNAT/impresión/caja de su origen.

**Versión:** 3.0  
**Fecha:** Agosto 2026  
**Stack:** PHP 8.2+ / Laravel 13.x / MySQL 8.0 / Node.js 18+

---

## 1. Arquitectura del Sistema

```
�"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�
�",                    Navegador (Cliente)                     �",
�",  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"� �",
�",  �",Restaurant�",  �",   POS    �",  �",Facturación�", �",Admin Panel�", �",
�",  �", (Blade)  �",  �", (Blade)  �",  �", (Blade)   �", �",  (Blade)  �", �",
�",  �""�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"~  �""�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"~  �""�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"~  �""�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"~ �",
�",       �", localStorage �",              �",              �",       �",
�",       �",  (POS tabs)  �",              �",              �",       �",
�""�"?�"?�"?�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"��"?�"?�"?�"?�"?�"?�"~
        �",              �",              �",              �",
        �-�              �-�              �-�              �-�
�"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�
�",              Laravel 13.x (Servidor PHP)                   �",
�",  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"� �",
�",  �", Controllers  �",  �",   Services   �",  �",  Models/Eloquent �", �",
�",  �",  �?� Restaurant�",  �", �?� Greenter   �",  �",  �?� Product       �", �",
�",  �",  �?� POS       �",  �", �?� Summary    �",  �",  �?� Invoice       �", �",
�",  �",  �?� CashReg.  �",  �", �?� Print      �",  �",  �?� CashRegister  �", �",
�",  �",  �?� Invoice   �",  �", �?� PlainText  �",  �",  �?� RestaurantO.  �", �",
�",  �",  �?� Product   �",  �", �?� SunatQr    �",  �",  �?� User          �", �",
�",  �""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~  �""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~  �""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~ �",
�",                          �",                                �",
�",  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�-��"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �",
�",  �",               MySQL 8.0 (Base de Datos)              �",  �",
�",  �""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~  �",
�""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~
        �",
        �", HTTP (solo impresión)
        �-�
�"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�
�",           Print Server Node.js (localhost:9100)            �",
�",  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"�  �"O�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"� �",
�",  �",Impresora  �",  �", Impresora    �",  �", Cajón de Efectivo  �", �",
�",  �", USB Local �",  �", Red (IP:9100)�",  �", (Drawer Kick)      �", �",
�",  �""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~  �""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~  �""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~ �",
�""�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"?�"~
```

**Comunicación entre componentes:**
- Cliente �?" Laravel: HTTP/HTTPS (REST + Blade SSR)
- Laravel �?' Print Server: HTTP POST (localhost:9100)
- Print Server �?' Impresora: raw-print.ps1 (USB) / Socket TCP (Red)
- Laravel �?' SUNAT: SOAP vía Greenter 5.x

---

## 2. Base de Datos

### 2.1 Tablas del Sistema (29 tablas)

| # | Tabla | Propósito | Registros típicos |
|---|-------|-----------|-------------------|
| 1 | `companies` | Empresa (RUC, certificado, IGV) | 1 |
| 2 | `users` | Usuarios del sistema | 5-10 |
| 3 | `roles` | Roles (admin, cajero, mozo, user) | 4 |
| 4 | `permissions` | Permisos del sistema | 46 |
| 5 | `role_user` | Pivot: rol �?" usuario | 5-10 |
| 6 | `role_permission` | Pivot: permiso �?" rol | 200+ |
| 7 | `customers` | Clientes | 100-1000 |
| 8 | `categories` | Categorías de productos | 20-50 |
| 9 | `products` | Productos (catálogo) | 100-500 |
| 10 | `product_components` | Componentes de prod. compuestos | 0-100 |
| 11 | `invoices` | Comprobantes emitidos (incluye compras CO) | 1000-5000/mes |
| 12 | `invoice_items` | Items de comprobantes | 5000-20000/mes |
| 13 | `series` | Series documentales (F001, B001, COM) | 5-10 |
| 14 | `floors` | Pisos (incluye pisos Venta/Compra del POS) | 1-3 |
| 15 | `restaurant_tables` | Mesas + estaciones fijas del POS (pos_mode) | 10-50 |
| 16 | `restaurant_orders` | Pedidos / operaciones POS | 100-500/día |
| 17 | `restaurant_order_items` | Items de pedidos (price_level) | 500-3000/día |
| 18 | `cashregisters` | Apertura/cierre de caja (ventas + compras + saldo) | 1-2/día |
| 19 | `purchases` | Compras a proveedores | 10-50/mes |
| 20 | `purchase_items` | Items de compras | 50-200/mes |
| 21 | `suppliers` | Proveedores | 5-20 |
| 22 | `printers` | Configuración de impresoras | 3 |
| 23 | `print_jobs` | Cola de impresión | 100-500/día |
| 24 | `ubigeos` | Catálogo ubigeos SUNAT | 1874 |
| 25 | `sunat_products` | Catálogo productos SUNAT | ~5000 |
| 26 | `sunat_summaries` | Resúmenes diarios enviados | 1-2/día |
| 27 | `auxiliary_items` | Elementos auxiliares | 0-50 |
| 28 | `special_documents` | Docs especiales (guía, retención) | 0-10/mes |
| 29 | `cash_movements` | **Ingresos y Gastos** (tipo INGRESO/GASTO) | 5-50/día |

### 2.2 Estructura Detallada de Tablas Clave

#### `products`
```sql
CREATE TABLE products (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    codigo VARCHAR(50) NOT NULL,
    codigo_barras VARCHAR(50) NULL,
    descripcion VARCHAR(255) NOT NULL,
    codigo_sunat VARCHAR(8) NULL,
    umedida_codigo VARCHAR(3) DEFAULT 'NIU',
    precio DECIMAL(12,2) DEFAULT 0,
    precio_minimo DECIMAL(12,2) NULL,
    precio_compra DECIMAL(12,4) DEFAULT 0,
    precio_venta_n2 DECIMAL(12,4) DEFAULT 0,
    precio_venta_n3 DECIMAL(12,4) DEFAULT 0,
    precio_venta_n4 DECIMAL(12,4) DEFAULT 0,
    precio_compra_n2 DECIMAL(12,4) DEFAULT 0,
    precio_compra_n3 DECIMAL(12,4) DEFAULT 0,
    precio_compra_n4 DECIMAL(12,4) DEFAULT 0,
    tipo_afectacion ENUM('GRA','EXO','INA','EXE') DEFAULT 'GRA',
    igv_percent DECIMAL(5,2) DEFAULT 18,
    estado ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    category_id BIGINT NULL,
    stock DECIMAL(12,4) DEFAULT 0,
    print_destination VARCHAR(20) NOT NULL DEFAULT 'productos',
    is_composite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    UNIQUE KEY (company_id, codigo),
    INDEX idx_products_company_estado (company_id, estado),
    INDEX idx_products_barcode (codigo_barras)
);
```

#### `product_components`
```sql
CREATE TABLE product_components (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    parent_product_id BIGINT NOT NULL,
    component_product_id BIGINT NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (parent_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (component_product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_component (parent_product_id, component_product_id)
);
```

#### `restaurant_orders`
```sql
CREATE TABLE restaurant_orders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    table_id BIGINT NOT NULL,
    user_id BIGINT NULL,
    order_number VARCHAR(20) NOT NULL,
    status ENUM('OPEN','SENT_TO_KITCHEN','READY','DELIVERED','COMPLETED','CANCELLED','PENDING_PAYMENT'),
    order_type VARCHAR(20) DEFAULT 'mozo',
    subtotal DECIMAL(12,2) DEFAULT 0,
    igv DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_orders_company_status (company_id, status),
    INDEX idx_orders_type (order_type)
);
```

#### `restaurant_order_items`
```sql
CREATE TABLE restaurant_order_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    restaurant_order_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    price_level TINYINT DEFAULT 1,   -- Multiprecio: nivel 1-4 elegido en el POS chatarra
    total DECIMAL(12,2) NOT NULL,
    kitchen_status ENUM('PENDING','SENT','READY','DELIVERED','CANCELLED') DEFAULT 'PENDING',
    notes TEXT NULL,
    auxiliary_items JSON NULL,
    print_destination VARCHAR(20) NOT NULL DEFAULT 'productos',
    sent_to_kitchen_at TIMESTAMP NULL,
    cancelled_from VARCHAR(20) NULL,
    cancelled_at TIMESTAMP NULL,
    cancelled_by BIGINT NULL,
    paid_invoice_id BIGINT NULL,   -- Dividir Cuenta: invoice que pagó el item
    FOREIGN KEY (restaurant_order_id) REFERENCES restaurant_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id),
    INDEX idx_items_order_status (restaurant_order_id, kitchen_status)
);
```

#### `invoices`
```sql
CREATE TABLE invoices (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    customer_id BIGINT NULL,
    tipo_documento ENUM('01','03','NV','CO') NOT NULL,   -- CO = compra de chatarra (nunca a SUNAT)
    serie VARCHAR(10) NOT NULL,
    numero INT NOT NULL,
    full_number VARCHAR(30) NOT NULL,
    fecha_emision DATE NOT NULL,
    hora_emision TIME NULL,
    fecha_vencimiento DATE NULL,
    moneda VARCHAR(3) DEFAULT 'PEN',
    subtotal DECIMAL(12,2) DEFAULT 0,
    gravado DECIMAL(12,2) DEFAULT 0,
    igv DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    total_letras VARCHAR(255) NULL,
    metodo_pago VARCHAR(100) DEFAULT 'EFECTIVO',
    referencia_pago VARCHAR(100) NULL,
    sunat_estado VARCHAR(20) DEFAULT 'PENDIENTE',   -- incluye NO_ENVIADO para CO
    order_source VARCHAR(20) NULL,   -- pos_venta | pos_compra | kiosko | ...
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX idx_invoices_company_tipo_fecha (company_id, tipo_documento, fecha_emision),
    INDEX idx_invoices_sunat_estado (sunat_estado)
);
```

#### `invoice_items`
```sql
CREATE TABLE invoice_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT NOT NULL,
    product_id BIGINT NULL,
    codigo VARCHAR(50) NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    umedida VARCHAR(3) DEFAULT 'NIU',
    precio_unitario DECIMAL(12,2) NOT NULL,
    precio_venta DECIMAL(12,2) NOT NULL,
    igv DECIMAL(12,2) NOT NULL,
    tipo_afectacion VARCHAR(5) DEFAULT '10',
    igv_percent DECIMAL(5,2) DEFAULT 18,
    detalle_consumo JSON NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### `cashregisters`
```sql
CREATE TABLE cashregisters (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    monto_apertura DECIMAL(12,2) DEFAULT 0,
    monto_cierre DECIMAL(12,2) NULL,
    ventas_efectivo DECIMAL(12,2) DEFAULT 0,
    ventas_tarjeta DECIMAL(12,2) DEFAULT 0,
    ventas_yape DECIMAL(12,2) DEFAULT 0,
    ventas_plin DECIMAL(12,2) DEFAULT 0,
    ventas_otro DECIMAL(12,2) DEFAULT 0,
    cantidad_ventas INT DEFAULT 0,
    total_ventas DECIMAL(12,2) DEFAULT 0,
    compras_efectivo DECIMAL(12,2) DEFAULT 0,    -- compras por método (debitan caja)
    compras_tarjeta DECIMAL(12,2) DEFAULT 0,
    compras_yape DECIMAL(12,2) DEFAULT 0,
    compras_plin DECIMAL(12,2) DEFAULT 0,
    compras_otro DECIMAL(12,2) DEFAULT 0,
    cantidad_compras INT DEFAULT 0,
    total_compras DECIMAL(12,2) DEFAULT 0,
    ingresos_total DECIMAL(12,2) DEFAULT 0,
    gastos_total DECIMAL(12,2) DEFAULT 0,
    saldo_final DECIMAL(12,2) DEFAULT 0,   -- apertura + ventas + ingresos - compras - gastos
    estado ENUM('ABIERTA','CERRADA') DEFAULT 'ABIERTA',
    fecha_apertura TIMESTAMP NULL,
    fecha_cierre TIMESTAMP NULL,
    observaciones TEXT NULL,
    referencia VARCHAR(255) NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_cash_company_estado (company_id, estado)
);
```

#### `cash_movements` (Ingresos y Gastos)
```sql
CREATE TABLE cash_movements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    cash_register_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    tipo ENUM('INGRESO','GASTO') DEFAULT 'INGRESO',
    monto DECIMAL(12,2) DEFAULT 0,
    concepto VARCHAR(255) NULL,
    fecha DATETIME NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (cash_register_id) REFERENCES cashregisters(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

> **Estaciones fijas del POS**: `restaurant_tables.pos_mode VARCHAR(10) NULL` (`venta`|`compra`). `ScrapPosController::ensureStations()` las auto-crea (Venta 1-5 / Compra 1-5) al primer acceso.

### 2.3 Migraciones del Sistema

| Migración | Descripción |
|-----------|-------------|
| `2024_01_01_000001` | Tabla companies |
| `2024_01_01_000002` | Tabla customers |
| `2024_01_01_000003` | Tabla products |
| `2024_01_01_000005` | Tabla invoices |
| `2024_01_01_000006` | Tabla invoice_items |
| `2026_04_27_000002` | Stock en products, suppliers, purchases |
| `2026_05_12_104536` | Tablas restaurant_tables |
| `2026_05_12_104627` | Tablas restaurant_orders/items |
| `2026_05_13_000001` | Roles y permisos |
| `2026_05_21_083855` | Config IGV en companies |
| `2026_05_31_000001` | stock_outputs tables |
| `2026_06_10_000001` | Bloqueo de mesas |
| `2026_06_19_000001` | order_source y order_type |
| `2026_07_05_000002` | is_composite en products |
| `2026_07_05_000003` | Tabla product_components |
| `2026_07_07_181559` | precio_compra en products |
| `2026_07_08_000001` | detalle_consumo en invoice_items |
| `2026_08_07_000001` | Multiprecio: precio_venta_n2..n4 y precio_compra_n2..n4 en products |
| `2026_08_07_000002` | pos_mode en restaurant_tables (estaciones del POS) |
| `2026_08_07_000003` | Compras por método, ingresos/gastos y saldo_final en cashregisters |
| `2026_08_07_000004` | Tabla cash_movements |
| `2026_08_07_000005` | Renombra slot cocina-1�?'productos y elimina slots sobrantes |
| `2026_08_07_000006` | price_level en restaurant_order_items |
| `2026_08_07_000007` | Amplía ENUM sunat_estado con NO_ENVIADO |
| `2026_08_07_000008` | Renombra kds_destination→print_destination (despacho_compra/despacho_venta) |
| `2026_08_07_000010` | Añade slots de impresora despacho_compra/despacho_venta (luego eliminados) |
| `2026_08_07_000012` | print_destination='productos' (destino único) y elimina slots de despacho |

### 2.4 Índices Críticos

| Tabla | Índice | Justificación |
|-------|--------|---------------|
| `invoices` | `(company_id, tipo_documento, fecha_emision, sunat_estado)` | Reportes, dashboard, cierre caja |
| `restaurant_orders` | `(company_id, status)` | Polling cada 10s |
| `restaurant_orders` | `(order_type)` | Filtro kiosko vs mozo |
| `restaurant_order_items` | `(restaurant_order_id, kitchen_status)` | KDS polling cada 5s |
| `products` | `(company_id, estado)` | Búsquedas en POS/Restaurante |
| `cashregisters` | `(company_id, estado)` | Apertura/cierre |
| `print_jobs` | `(status, attempts)` | Procesamiento de cola |
| `series` | `(company_id, tipo_documento, estado)` | Búsqueda de serie al crear documento |

---

## 3. Modelos Eloquent

### 3.1 Product

```php
// Relaciones
public function components(): HasMany        // ProductComponent
public function category(): BelongsTo         // Category
public function invoiceItems(): HasMany       // InvoiceItem

// Scopes
public function scopeSimple($query)           // is_composite = false
public function scopeComposite($query)         // is_composite = true

// Métodos multiprecio
public function priceVenta(int $nivel = 1): float   // N1=precio, N2-N4=precio_venta_n2..n4
public function priceCompra(int $nivel = 1): float  // N1=precio_compra, N2-N4=precio_compra_n2..n4

// Accessors
public function isComposite(): bool           // is_composite == true

// Casts
protected $casts = [
    'stock' => 'decimal:4',
    'precio' => 'decimal:2',
    'precio_compra' => 'decimal:4',
    'is_composite' => 'boolean',
];

// Fillable
'company_id', 'codigo', 'codigo_barras', 'descripcion', 'codigo_sunat',
'umedida_codigo', 'precio', 'precio_minimo', 'precio_compra',
'precio_venta_n2', 'precio_venta_n3', 'precio_venta_n4',
'precio_compra_n2', 'precio_compra_n3', 'precio_compra_n4',
'tipo_afectacion', 'igv_percent', 'estado', 'category_id',
'stock', 'print_destination', 'is_composite'
```

### 3.2 ProductComponent

```php
// Relaciones
public function parent(): BelongsTo       // Product (producto compuesto)
public function component(): BelongsTo    // Product (producto componente)

// Casts
protected $casts = ['quantity' => 'decimal:2'];

// Fillable
'parent_product_id', 'component_product_id', 'quantity'
```

### 3.3 RestaurantOrder

```php
// Constantes de estado
const STATUS_OPEN = 'OPEN';
const STATUS_SENT_TO_KITCHEN = 'SENT_TO_KITCHEN';
const STATUS_READY = 'READY';
const STATUS_DELIVERED = 'DELIVERED';
const STATUS_COMPLETED = 'COMPLETED';
const STATUS_CANCELLED = 'CANCELLED';

// Relaciones
public function table(): BelongsTo
public function user(): BelongsTo
public function items(): HasMany

// Métodos
public static function generateOrderNumber(): string       // P-YYYYMMDD-NNNN
public static function generateKioskoOrderNumber(): string // A-NNN
public function statusLabel(): string

// Casts
protected $casts = [
    'subtotal' => 'decimal:2',
    'igv' => 'decimal:2',
    'total' => 'decimal:2',
];

// Fillable
'company_id', 'table_id', 'user_id', 'order_number', 'status',
'order_type', 'subtotal', 'igv', 'total', 'notes'
```

### 3.4 RestaurantOrderItem

```php
// Relaciones
public function order(): BelongsTo
public function product(): BelongsTo
public function cancelledBy(): BelongsTo  // User

// Fillable
'restaurant_order_id', 'product_id', 'product_name', 'quantity',
'unit_price', 'price_level', 'total', 'kitchen_status', 'notes', 'auxiliary_items',
'print_destination', 'sent_to_kitchen_at', 'cancelled_from',
'cancelled_at', 'cancelled_by', 'paid_invoice_id'
```

### 3.5 InvoiceItem

```php
// Fillable
'invoice_id', 'product_id', 'codigo', 'descripcion', 'cantidad',
'umedida', 'precio_unitario', 'precio_venta', 'igv',
'tipo_afectacion', 'igv_percent', 'detalle_consumo'

// Casts
protected $casts = [
    'cantidad' => 'decimal:4',
    'precio_unitario' => 'decimal:4',
    'precio_venta' => 'decimal:2',
    'igv' => 'decimal:2',
    'detalle_consumo' => 'array',
];
```

### 3.6 CashRegister

```php
// Estados: ABIERTA | CERRADA
// Fillable: company_id, user_id, monto_apertura, monto_cierre,
//   ventas_efectivo, ventas_tarjeta, ventas_yape, ventas_plin, ventas_otro,
//   cantidad_ventas, total_ventas,
//   compras_efectivo, compras_tarjeta, compras_yape, compras_plin, compras_otro,
//   cantidad_compras, total_compras, ingresos_total, gastos_total, saldo_final,
//   estado, fecha_apertura, fecha_cierre, observaciones, referencia

// Relaciones
public function movements(): HasMany   // CashMovement (ingresos y gastos)
```

### 3.7 Invoice

```php
// Fillable incluye: order_source
// Helpers
public function isPurchase(): bool   // tipo_documento === 'CO' (compra de chatarra)
// sunat_estado incluye 'NO_ENVIADO' (compras, nunca a SUNAT)
```

### 3.8 CashMovement

```php
// Fillable: company_id, cash_register_id, user_id, tipo, monto, concepto, fecha
// Relaciones: company(), cashRegister(), user()
// Helpers
public function isIngreso(): bool   // tipo === 'INGRESO'
```

---

## 4. Controladores y Rutas

### 4.1 CashRegisterController

| Método | Ruta | Método HTTP | Auth |
|--------|------|-------------|------|
| `index()` | `/cashregisters` | GET | auth + permission `view_cashregisters` |
| `open()` | `/cashregister/open` | POST | auth + permission `open_cashregister` |
| `close()` | `/cashregister/close` | POST | auth + permission `close_cashregister` |
| `show()` | `/cashregisters/{id}` | GET | auth + permission `view_cashregisters` |
| `pdf()` | `/cashregisters/{id}/pdf` | GET | auth + permission `view_cashregisters` |
| `ticketPdf()` | `/cashregisters/{id}/ticket` | GET | auth + permission `view_cashregisters` |
| `printCaja()` | `/cashregisters/{id}/print-caja` | POST | auth + permission `view_cashregisters` |

**Lógica de cierre (`close()`):**
```
1. Validar: cashregister_id; monto_cierre OPCIONAL (si se omite = saldo resultante); observaciones
2. Verificar: caja no cerrada ya
3. Contar operaciones abiertas (order_type != 'kiosko': mesas + estaciones POS)
4. Contar pedidos kiosko (order_type = 'kiosko')
5. Si hay abiertos �?' mensaje específico
6. Ventas  = invoices tipo_documento != 'CO'  (BETWEEN apertura Y cierre)
7. Compras = invoices tipo_documento = 'CO'
8. paymentBuckets() desglosa ventas y compras por método de pago
9. Ingresos/gastos = SUM(cash_movements.tipo) de la caja
10. saldo_final = monto_apertura + ventas + ingresos - compras - gastos
11. monto_cierre = input del usuario, o saldo_final si se omitió
12. Actualizar cash register �?' estado CERRADA �?' resumen "Flujo de Caja del Día" + Sobrante/Faltante
```

**`index()` (saldo en vivo):** con caja abierta calcula el mismo flujo (ventas �^' compras + ingresos �^' gastos) desde la apertura hasta ahora y lo pasa a la vista.

### 4.2 ScrapPosController (POS Venta / POS Compra)

| Método | Ruta | Propósito |
|--------|------|-----------|
| `index($mode)` | GET `/pos-venta` / `/pos-compra` | Grilla de estaciones; exige caja abierta; `ensureStations()` auto-crea las 5 estaciones |
| `openStation($mode, $station)` | POST `/scrap-pos/{mode}/open/{station}` | Abre la estación (crea/reutiliza operación OPEN) |
| `addItem` | POST `/scrap-pos/orders/{id}/items` | Agrega material: product_id, quantity (decimal), price_level (1-4), notes; valida stock si venta |
| `updateItem` | PUT `/scrap-pos/items/{item}` | Cambia cantidad (decimal) |
| `removeItem` | DELETE `/scrap-pos/items/{item}` | Elimina item (borra físicamente; bloqueado si no OPEN) |
| `sendOrder` | POST `/scrap-pos/orders/{id}/send` | **Enviar a Caja**: guarda vendedor en `notes`, items PENDING�?'SENT, orden �?' SENT_TO_KITCHEN, imprime comanda (slot `productos`), bloquea edición |
| `chargeOrder` | POST `/scrap-pos/orders/{id}/charge` | Cobra SOLO órdenes SENT_TO_KITCHEN; venta: 01/03/NV; compra: CO serie COM; actualiza caja y stock |
| `printList` | POST `/scrap-pos/orders/{id}/print-list` | Reimprime lista (comanda) |
| `printPrecuenta` | POST `/scrap-pos/orders/{id}/precuenta` | Precuenta (slot `precuenta`) |
| `stations($mode)` | GET `/scrap-pos/{mode}/stations` | Polling: estado por estación (LIBRE/PESANDO/POR_COBRAR), total, vendedor |
| `getOrder` | GET `/scrap-pos/orders/{id}` | Devuelve operación con items, estado y vendedor |

**Helpers privados:**
```php
assertOrderEditable($order)   // Lanza RuntimeException si status !== 'OPEN' ("ya fue enviado a caja")
assertSaleStock($order, $product, $qty)  // Valida stock en venta (acumulado en la orden; componentes si es compuesto)
ensureStations($companyId, $mode)        // Auto-crea las 5 estaciones fijas
createInvoiceFromItems(..., $mode)       // Venta: descuenta stock + suma a caja; Compra: suma stock + debita caja (CO)
```

### 4.3 CashMovementController (Ingresos y Gastos)

| Método | Ruta | Propósito |
|--------|------|-----------|
| `index()` | GET `/ingresos-gastos` | Listado + formulario (permiso `view_cashregisters`) |
| `store()` | POST `/ingresos-gastos` | Registra INGRESO/GASTO y recalcula `ingresos_total`/`gastos_total` de la caja abierta |
| `destroy()` | DELETE `/ingresos-gastos/{id}` | Elimina movimiento y recalcula |

**Lógica de métodos de pago (compartida por `show()` y `printCaja()`):**
```php
foreach ($ventas as $venta) {
    $pago = $venta->metodo_pago;  // "YAPE/80 + EFECTIVO/15"
    if (str_contains($pago, ' + ')) {
        foreach (explode(' + ', $pago) as $part) {
            if (str_contains($part, '/')) {
                [$metName, $metAmt] = explode('/', $part);  // Lee monto real
                $amt = min((float) $metAmt, $venta->total);
            } else {
                $metName = $part;
                $amt = round($venta->total / count($parts), 2);
            }
            $key = strtoupper($metName);
            // match: str_starts_with EFECT/TARJ, $key === YAPE/PLIN
        }
    } else {
        $key = strtoupper(explode('/', $pago)[0]);
        // match directo con el total
    }
}
```

### 4.4 RestaurantController (métodos principales)

| Método | Ruta | Propósito |
|--------|------|-----------|
| `index()` | GET `/restaurant` | Vista principal con pisos y mesas |
| `openTable($id)` | POST `/restaurant/tables/{id}/open` | Abrir mesa (crea orden) |
| `addItem($id)` | POST `/restaurant/orders/{id}/items` | Agregar producto al pedido |
| `updateItem($id)` | PUT `/restaurant/orders/items/{id}` | Modificar cantidad/notas |
| `removeItem($id)` | DELETE `/restaurant/orders/items/{id}` | Eliminar item (PENDING�?'delete; SENT/READY/DELIVERED�?'CANCELLED con password admin) |
| `sendToKitchen($id)` | POST `/restaurant/orders/{id}/send-to-kitchen` | Enviar a cocina |
| `chargeOrder($id)` | POST `/restaurant/orders/{id}/charge` | Cobrar pedido |
| `splitChargeOrder($id)` | POST `/restaurant/orders/{id}/split-charge` | Dividir cuenta en 2+ comprobantes |
| `cancelOrder($id)` | POST `/restaurant/orders/{id}/cancel` | Anular pedido completo |
| `getActiveOrders()` | GET `/restaurant/active-orders` | Polling |
| `getTableLocks()` | GET `/restaurant/locks` | Polling bloqueos |
| `getKitchenOrders()` | GET `/restaurant/kitchen-orders` | Polling KDS |

**Lógica de `chargeOrder()`:**
```
1. Verificar: usuario no mozo
2. Verificar: caja abierta
3. Validar: orden no OPEN, tiene items
4. $soloConsumo = $request->boolean('solo_consumo')
5. Calcular IGV: $igvRate = $company->getIgvRate()
6. Crear Invoice
7. Si soloConsumo: 1 InvoiceItem "POR CONSUMO" + detalle_consumo (JSON)
8. Si no: N InvoiceItems individuales
9. Descontar stock (productos compuestos: descuentan componentes)
10. Incrementar serie, marcar orden COMPLETED, liberar mesa
```

### 4.5 PosController

| Método | Ruta | Propósito |
|--------|------|-----------|
| `index()` | GET `/pos` | Vista POS |
| `store()` | POST `/pos` | Procesar venta |
| `success($id)` | GET `/pos/success/{id}` | Página de éxito |
| `sendToSunat($id)` | POST `/pos/sunat/{id}` | Enviar a SUNAT |
| `printInvoice($id,$format)` | GET `/pos/print/{id}/{format}` | Imprimir |
| `openDrawer()` | POST `/pos/open-drawer` | Abrir cajón |

### 4.6 ProductController

| Método | Ruta | Propósito |
|--------|------|-----------|
| `index()` | GET `/products` | Lista con filtros (columnas Precio Venta y Precio Compra N1-N4) |
| `store()` / `update()` | POST/PUT | CRUD (validación y guardado de los 8 precios multiprecio) |
| `createComposite()` | GET `/products/composite/create` | Form producto compuesto |
| `storeComposite()` | POST `/products/composite/store` | Guardar compuesto |
| `editComposite($p)` | GET `/products/{p}/composite/edit` | Editar compuesto |
| `updateComposite($p)` | PUT `/products/{p}/composite/update` | Actualizar compuesto |
| `inventoryReport()` | GET `/products/inventory-report` | Reporte inventario |
| `inventoryReportExcel()` | GET `/products/inventory-report/excel` | Excel |
| `inventoryReportPdf()` | GET `/products/inventory-report/pdf` | PDF |
| `importForm()` | GET `/products/import` | Vista de importación (documenta columnas multiprecio) |
| `previewImport()` | POST `/products/import-preview` | Previsualiza (incluye Precio Venta/Compra N2-N4) |
| `importStore()` | POST `/products/import` | Importar Excel (colMap + parseo + guardado de `precio_venta_n2..n4` y `precio_compra_n2..n4`) |
| `downloadTemplate()` | GET `/products/import/template` | Plantilla con columnas multiprecio |
| `export()` | GET `/products/export` | Exporta Venta N1-N4 y Compra N1-N4 |
| `exportSpreadsheet()` | (privado) | Escribe XLSX; autoajuste dinámico por nº de columnas (`Coordinate::stringFromColumnIndex`) |

**Vistas de productos con multiprecio:**
- `products/create.blade.php` / `edit.blade.php`: tabla **Multiprecio** (Venta × Nivel 1-4 / Compra × Nivel 1-4).
- `products/show.blade.php`: tabla Multiprecio (venta y compra) usando `priceVenta()`/`priceCompra()`.
- `products/index.blade.php`: columnas "Precio Venta" y "Precio Compra" (N1 + N2-N4 si >0).
- `products/preview.blade.php`: columnas "Venta N1 / Venta N2-N4 / Compra N1 / Compra N2-N4".

---

## 5. Servicios

### 5.1 GreenterService (`app/Services/GreenterService.php`)

| Método | Propósito | SUNAT |
|--------|-----------|-------|
| `sendInvoice($invoice)` | Envía factura (01) | BillSender SOAP |
| `sendCreditNote($invoice, ...)` | Nota de crédito (07) | BillSender / Summary |
| `sendDebitNote($invoice, ...)` | Nota de débito (08) | BillSender / Summary |
| `voidInvoice($invoice)` | Baja de factura | Voided |
| `setupSee($company)` | Configura certificado | PEM-first |
| `buildInvoice($invoice, $company)` | Construye XML Greenter (helper privado) | - |
| `generatePdf($invoice)` | PDF A4 | mPDF |
| `getClientData($invoice)` | Datos del cliente | - |

**Ruteo SUNAT:**
```
Factura (01) �?' sendInvoice() �?' BillSender SOAP
Boleta (03)  �?' sendBoletaToSummary() �?' SummarySender
NC Factura   �?' sendCreditNote() �?' BillSender SOAP
NC Boleta    �?' sendNoteViaSummary() �?' SummarySender
ND Factura   �?' sendDebitNote() �?' BillSender SOAP
ND Boleta    �?' sendNoteViaSummary() �?' SummarySender
Baja Factura �?' voidInvoice() �?' Voided
Baja Boleta  �?' voidBoleta() �?' Summary estado=3
```

### 5.2 SummaryService (`app/Services/SummaryService.php`)

| Método | Propósito |
|--------|-----------|
| `setupSee($company)` | Configura certificado (PEM-first) |
| `sendBoletaToSummary($invoice)` | Agrega boleta a resumen diario |
| `sendDailySummary()` | Agrupa y envía boletas del día |
| `voidBoleta($invoice)` | Anula boleta (estado=3 en summary) |
| `sendNoteToSummary($note, ...)` | NC/ND de boleta por summary |
| `checkTicketStatus($ticket)` | Consulta estado del ticket |
| `getNextCorrelativo($company)` | Correlativo RC-YYYYMMDD-NNN |

### 5.3 PrintService (`app/Services/PrintService.php`)

| Método | Propósito |
|--------|-----------|
| `printKitchenOrder($order, $items)` | Comanda de cocina (slot `productos`) |
| `printPrebill($order, $key)` | Precuenta |
| `printScrapOrder($order, $mode)` | **Comanda del POS** (siempre slot `productos`, cabecera "PRODUCTOS") |
| `printScrapPrebill($order, $mode)` | **Precuenta del POS chatarra** (slot `precuenta`) |
| `printCancelNotification($order, $item)` | Anulación individual |
| `printCancelNotificationGrouped($order, $items)` | Anulación agrupada |
| `printInvoice($invoice)` | No-op (invoiceTicket es stub; comprobante por PDF Greenter) |
| `printAutoPedidoTicket($order)` | Ticket kiosko |
| `processQueue()` | Procesa cola de impresión |
| `queuePrint($printer, $data, ...)` | Encola trabajo |

**Flujo de impresión:**
```
1. Controlador �?' PrintService::printXxx()
2. Genera texto ESC/POS vía PlainTextTicket
3. queuePrint() crea PrintJob (status: pending)
4. processQueue() envía HTTP POST a localhost:9100/print
5. Print Server recibe y envía a impresora
6. �?xito: completed | Falla: failed (reintentos < 3)
```

### 5.4 PlainTextTicket (`app/Services/PlainTextTicket.php`)

Genera tickets en texto plano con formato ESC/POS.

| Método | Destino | Contenido |
|--------|---------|-----------|
| `kitchenTicket($order, $format, $dest)` | cocina-1/2, bar-1 | Header + items |
| `prebillTicket($order, $format)` | precuenta | Items + total + IGV |
| `scrapOrderTicket($order, $mode, ...)` | productos | Comanda venta/compra: estación, nro, Cliente (notes), items con nivel × precio, total |
| `cancelNotification($order, $item, ...)` | cocina/bar | Item cancelado |
| `cancelNotificationGrouped($order, ...)` | cocina/bar | Items cancelados agrupados (incluye "Anulado por") |
| `invoiceTicket($invoice, $format)` | caja | Stub (no-op); comprobante por PDF Greenter |
| `cashRegisterSummary($cash, $data, ...)` | caja | Cierre completo + **Flujo de Caja del Día** + Sobrante/Faltante |

**Encoding:** CP850 con tabla de mapeo manual para caracteres especiales (ñ, tildes).

### 5.5 PrintServerService (`app/Services/PrintServerService.php`)

| Método | Propósito |
|--------|-----------|
| `isServerRunning()` | Health check (GET /status) |
| `getAvailablePrinters()` | Lista impresoras del sistema |
| `printText($printer, $text)` | Envía texto para imprimir |

---

## 6. Frontend

### 6.1 Tecnologías

| Componente | Tecnología |
|-----------|-----------|
| Plantillas | Blade (AdminLTE) |
| CSS | Tailwind CSS + AdminLTE |
| JavaScript | Vanilla JS (sin frameworks) |
| Gráficos | Chart.js |
| Impresión | ESC/POS vía fetch a localhost:9100 |

### 6.2 POS Multi-venta (localStorage)

```javascript
// Estructura de datos
let saleTabs = [
    {
        id: 1,
        name: 'Venta 1',
        items: [{id, name, price, quantity, stock, is_composite}],
        customerId: null,
        customerName: '',
        documentType: 'NV',
        paymentMethod: 'EFECTIVO',
        createdAt: '2026-07-15T10:00:00'
    }
];
let activeTabId = 1;

// Persistencia
const STORAGE_KEY = 'pos_tabs';
const STORAGE_ACTIVE = 'pos_activeTab';

// Funciones clave
function saveTabsToStorage() { localStorage.setItem(...); }
function loadTabsFromStorage() { ... }
function switchToTab(tabId) { ... }
function addNewTab() { ... }
function closeTab(tabId) { ... }
```

### 6.3 Polling (Restaurante, KDS y POS Chatarra)

| Vista | Función | Intervalo | Endpoint |
|-------|---------|-----------|----------|
| Restaurante | `pollActiveOrders()` | 10s | `/restaurant/active-orders` |
| Restaurante | `pollTableLocks()` | 10s | `/restaurant/locks` |
| Restaurante | `pollPrintServer()` | 10s | `/restaurant/print-status` |
| KDS | `loadKitchenOrders()` | 5s | `/restaurant/kitchen-orders?kds={cocina\|cocina2\|bar}` |
| POS Chatarra | `pollStations()` | 10s | `/scrap-pos/{mode}/stations` |

**POS Chatarra (`scrap_pos/index.blade.php`):**
- Grilla de estaciones con estados **LIBRE / PESANDO / POR COBRAR** (recargada por `pollStations` sin recargar la página).
- Botón **Enviar a Caja** (modal con campo Cliente/Vendedor) → `sendOrder` (imprime comanda + bloquea).
- Tras enviar: banner "ENVIADO A CAJA — PENDIENTE DE PAGO"; se ocultan "+ Agregar", +/− y eliminar; solo queda **Cobrar**.
- Si la operación abierta ya no está activa (la cobró otro usuario), `pollStations` cierra el modal.

**Headers requeridos en todos los fetch:**
```javascript
headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
}
```

**Manejo de errores:** `.catch(() => {})` silencioso para polling, `showError()` para acciones del usuario.

---

## 7. APIs REST

### 7.1 Endpoints (propuesta para versión futura)

**Autenticación:** Laravel Sanctum (token-based)

**Productos:**
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/products` | Listar con filtros (?category_id=, ?search=) |
| GET | `/api/products/{id}` | Detalle con componentes si es compuesto |
| GET | `/api/categories` | Listar categorías |

**POS:**
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/pos/sale` | Registrar venta |
| GET | `/api/pos/series` | Series disponibles |

**Restaurante:**
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tables` | Mesas con estado |
| POST | `/api/orders` | Crear pedido (abrir mesa) |
| GET | `/api/orders/{id}` | Detalle del pedido |
| POST | `/api/orders/{id}/items` | Agregar item |
| PUT | `/api/orders/items/{id}` | Modificar item |
| DELETE | `/api/orders/items/{id}` | Eliminar item |
| POST | `/api/orders/{id}/send-to-kitchen` | Enviar a cocina |
| POST | `/api/orders/{id}/charge` | Cobrar |
| GET | `/api/kitchen/orders` | Pedidos en cocina (?kds=cocina) |
| POST | `/api/kitchen/{id}/ready` | Marcar listo |

**Caja:**
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/cash-register/status` | Estado actual (abierta/cerrada) |
| POST | `/api/cash-register/open` | Abrir caja |
| POST | `/api/cash-register/close` | Cerrar caja |
| GET | `/api/cash-register/{id}/summary` | Resumen |

---

## 8. Seguridad

### 8.1 Medidas Implementadas

| Área | Medida | Implementación |
|------|--------|---------------|
| **Command Injection** | Sanitización de exec() | `escapeshellarg()` en CompanyController, BackupController |
| **CSRF** | Protección en formularios | `@csrf` en todos los forms vía Blade |
| **Auth** | Middleware en rutas | `auth`, `admin`, permisos vía `@can` |
| **XSS** | Escape automático | Blade `{{ }}` por defecto |
| **Passwords** | Pendiente de cifrar | `certificado_password`, `soap_password` en texto plano |
| **API Auth** | Sanctum token | Solo ruta `/api/user` actualmente |

### 8.2 Pendientes de Implementar

| Área | Riesgo | Acción |
|------|--------|--------|
| Passwords BD | Alto | Cifrar con `encrypt()`/`decrypt()` de Laravel |
| SSL verification | Alto | Habilitar `CURLOPT_SSL_VERIFYPEER` en DecolectaController |
| GET logout | Medio | Eliminar ruta GET `/logout`, solo POST |
| Column injection | Medio | Whitelist en `searchType` de ProductController |
| Stack traces | Bajo | No exponer en producción (`APP_DEBUG=false`) |

---

## 9. Comandos Artisan y Tareas Programadas

### 9.1 Comandos

| Comando | Propósito | Frecuencia |
|---------|-----------|------------|
| `print:process-queue` | Procesa cola de impresión | Cada 1 min (scheduler) |
| `sunat:send-daily-summary` | Agrupa boletas en resumen diario | Manual / scheduler |
| `sunat:check-summaries` | Consulta estado de tickets | Manual / scheduler |
| `sunat:retry-pending` | Reintenta comprobantes PENDIENTE/RECHAZADO | Manual |
| `sunat:download-padron` | Descarga y extrae el padrón SUNAT (elimina el ZIP) | Semanal (domingo 02:00) |

### 9.2 Limpieza de Caché

```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
```

**Regla:** Siempre ejecutar después de cambiar rutas o vistas.

### 9.3 Migraciones

```bash
php artisan migrate           # Ejecutar pendientes
php artisan migrate:rollback   # Revertir última (--step=1)
php artisan migrate:status     # Ver estado
```

---

## 10. Despliegue

### 10.1 Requisitos del Servidor

| Componente | Requisito |
|-----------|-----------|
| PHP | 8.2+ con extensiones: bcmath, gd, mbstring, openssl, pdo_mysql, xml, zip, soap, intl |
| MySQL | 8.0+ o MariaDB 10.4+ |
| Node.js | 18+ (solo para print server) |
| Composer | 2.x |
| Git | Para despliegue |
| Windows | Para print server (raw-print.ps1) |

### 10.2 Instalación

```bash
git clone <repo> chatarra
cd chatarra
composer install --no-dev
cp .env.example .env   # Configurar DB, APP_KEY
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
cd print-server-node && npm install
```

### 10.3 Tareas Programadas (Windows)

**Archivo:** `scheduler.vbs`

```vbs
' Inicia scheduler de Laravel + Print Server
CreateObject("WScript.Shell").Run "php artisan schedule:work", 0
CreateObject("WScript.Shell").Run "node print-server-node/server.js", 0
```

---

## 11. Control de Versiones

| Versión | Fecha | Cambios Técnicos |
|---------|-------|-----------------|
| 1.0 | Junio 2026 | Laravel 13.x base, Greenter 5.x, print server Node.js |
| 2.0 | Julio 2026 | is_composite + product_components, precio_compra, detalle_consumo (JSON), POS multi-tab localStorage, fix método pago Yape/Plin, ticket caja completo, fix command injection, fix $dest en kitchenTicket |
| 2.1 | Agosto 2026 | Dividir Cuenta (paid_invoice_id + split-charge), permisos SUNAT (send_sunat a cajero; user sin comprobantes/caja), rutas de caja por permiso, apertura de cajón en POS (manual + automática en efectivo), IGV dinámico en precuenta, Greenter v5.3.0 |
| 3.0 | Agosto 2026 | **POS Chatarra** (ScrapPosController, estaciones fijas pos_mode, multiprecio, flujo Enviar/Cobrar con bloqueo, polling stations), compras CO serie COM (sunat_estado NO_ENVIADO), cierre de caja contable (Flujo de Caja del Día + paymentBuckets + saldo en vivo), módulo Ingresos y Gastos (cash_movements + CashMovementController), validación de stock en venta, destino único `print_destination='productos'` con 3 slots de impresión, dashboard ventas+compras, **import/export de productos con multiprecio** (colMap/plantilla/export con Venta y Compra N1-N4, autoajuste dinámico) |
