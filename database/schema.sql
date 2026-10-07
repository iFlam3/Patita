-- =============================================================================
-- Patita — esquema de base de datos (exactamente 5 tablas)
-- Compatible con MySQL 8.0.16+ y MariaDB 10.4+ (InnoDB, utf8mb4).
--
-- Fechas: se guardan en UTC. La aplicación fija `time_zone = '+00:00'` en cada
-- conexión y convierte a APP_TIMEZONE solo al mostrar.
-- Importes: DECIMAL(12,2). Los cálculos en PHP se hacen en centavos enteros.
--
-- Este script NO borra tablas existentes. Para reinstalar desde cero usa
--   php scripts/install.php --fresh
-- (pide confirmación) o elimina las tablas manualmente.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. users
--    role es una lista controlada en la aplicación (customer | admin). El CHECK
--    es una segunda barrera; no existe tabla de roles.
-- -----------------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'customer',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. products
--    brand, pet_type y category son texto. pet_type y category se validan
--    contra listas controladas en app/config/catalog.php; las marcas y
--    categorías disponibles se obtienen con SELECT DISTINCT.
--    price/currency = precio LOCAL de venta. source_price/source_currency =
--    precio observado en la fuente externa (solo referencia, nunca se convierte
--    automáticamente).
--    data_origin distingue: scraped (extraído), demo (demostración), manual.
-- -----------------------------------------------------------------------------
CREATE TABLE products (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    sku             VARCHAR(64)   NOT NULL,
    name            VARCHAR(200)  NOT NULL,
    slug            VARCHAR(220)  NOT NULL,
    brand           VARCHAR(120)  NOT NULL,
    pet_type        VARCHAR(30)   NOT NULL,
    category        VARCHAR(40)   NOT NULL,
    description     TEXT          NOT NULL,
    price           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency        CHAR(3)       NOT NULL,
    stock           INT UNSIGNED  NOT NULL DEFAULT 0,
    image_url       VARCHAR(500)  NOT NULL DEFAULT '',
    source_url      VARCHAR(500)  NULL,
    source_site     VARCHAR(100)  NULL,
    source_price    DECIMAL(12,2) NULL,
    source_currency CHAR(3)       NULL,
    data_origin     VARCHAR(10)   NOT NULL DEFAULT 'manual',
    imported_at     DATETIME      NULL,
    active          TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_sku (sku),
    UNIQUE KEY uq_products_slug (slug),
    -- Deduplicación de importaciones por fuente + URL (NULL se permite repetido).
    UNIQUE KEY uq_products_source (source_site, source_url),
    KEY idx_products_listing (active, currency, pet_type, category),
    KEY idx_products_brand (active, brand),
    KEY idx_products_price (active, price),
    KEY idx_products_created (active, created_at),
    CONSTRAINT chk_products_origin CHECK (data_origin IN ('scraped', 'demo', 'manual')),
    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_source_price CHECK (source_price IS NULL OR source_price >= 0),
    CONSTRAINT chk_products_active CHECK (active IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. orders
--    Los datos de contacto y la dirección son una copia (snapshot) tomada al
--    confirmar; shipping_address guarda JSON con campos flexibles por país.
--    subtotal y shipping_cost se agregan al diseño base para que el total sea
--    auditable (ver docs/DECISIONES.md). checkout_token evita pedidos
--    duplicados por doble clic o reenvío del formulario.
-- -----------------------------------------------------------------------------
CREATE TABLE orders (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED  NOT NULL,
    status           VARCHAR(20)   NOT NULL DEFAULT 'received',
    subtotal         DECIMAL(12,2) NOT NULL,
    shipping_cost    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total            DECIMAL(12,2) NOT NULL,
    currency         CHAR(3)       NOT NULL,
    customer_name    VARCHAR(120)  NOT NULL,
    customer_email   VARCHAR(190)  NOT NULL,
    customer_phone   VARCHAR(40)   NOT NULL,
    shipping_address TEXT          NOT NULL,
    payment_method   VARCHAR(30)   NOT NULL,
    checkout_token   CHAR(64)      NOT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_checkout_token (checkout_token),
    KEY idx_orders_user (user_id, created_at),
    KEY idx_orders_status (status, created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_orders_status CHECK (status IN ('received', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled')),
    CONSTRAINT chk_orders_amounts CHECK (subtotal >= 0 AND shipping_cost >= 0 AND total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. order_items
--    product_name, sku y unit_price son copias históricas: la línea se conserva
--    aunque el producto cambie de precio, se desactive o se elimine
--    (product_id pasa a NULL).
-- -----------------------------------------------------------------------------
CREATE TABLE order_items (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    order_id     INT UNSIGNED  NOT NULL,
    product_id   INT UNSIGNED  NULL,
    product_name VARCHAR(200)  NOT NULL,
    sku          VARCHAR(64)   NOT NULL,
    quantity     INT UNSIGNED  NOT NULL,
    unit_price   DECIMAL(12,2) NOT NULL,
    subtotal     DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. password_reset_tokens
--    Solo se guarda el hash SHA-256 del token; el token en claro viaja una sola
--    vez por correo. Un token es válido si used_at IS NULL y expires_at > ahora.
-- -----------------------------------------------------------------------------
CREATE TABLE password_reset_tokens (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_token_hash (token_hash),
    KEY idx_password_reset_user (user_id, used_at),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
