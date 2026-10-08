# Patita (nombre provisional)

Tienda en línea de accesorios e insumos para mascotas pensada para
Latinoamérica: perros, gatos, aves, peces, reptiles, caballos, animales
pequeños (conejos, cuyos, hámsteres, chinchillas y hurones) y animales de
granja. Incluye estética canina y felina, guías de cuidado por especie,
herramientas interactivas (edad, alimentos peligrosos y peso saludable) y
páginas de información (acerca de, preguntas frecuentes, términos, privacidad,
devoluciones y contacto). No vende animales vivos.

> **Tienda de demostración.** Los 48 productos iniciales, sus marcas, precios
> (USD) y existencias son datos de prueba. No se procesan cobros.

Stack: HTML5 semántico, CSS propio (sin compilación), JavaScript vanilla,
PHP sin framework, MySQL/MariaDB con PDO y consultas preparadas. Exactamente
5 tablas.

## Contenido

- [Estado](#estado)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Servidor web](#servidor-web)
- [Correo](#correo)
- [Importador de productos](#importador-de-productos)
- [Configuración regional](#configuración-regional)
- [Seguridad](#seguridad)
- [Estructura](#estructura)
- [Pruebas](#pruebas)
- [Publicar en InfinityFree, paso a paso](docs/DEPLOY_INFINITYFREE.md)
- Documentos: [decisiones y nombres](docs/DECISIONES.md),
  [checklist de pruebas](docs/PRUEBAS.md),
  [limitaciones y pendientes](docs/PRODUCCION.md)

## Estado

| Área | Implementado | Verificado en este entorno |
|------|--------------|----------------------------|
| Catálogo, búsqueda, filtros, orden, paginación | Sí | Sí |
| Comprar por mascota y por marca | Sí | Sí |
| Carrito en sesión, totales en el servidor | Sí | Sí |
| Checkout con resumen, transacción, stock y antiduplicados | Sí | Sí (incluye doble clic y envíos simultáneos) |
| Registro, login, logout, límite de intentos | Sí | Sí |
| Recuperación de contraseña (token hasheado, un solo uso) | Sí | Sí, con transporte `log` |
| Mi cuenta (perfil, contraseña, historial) | Sí | Sí |
| Administración (productos, pedidos, importador) | Sí | Sí |
| Importador JSON/CSV | Sí | Sí |
| Importador web petstore.direct | Sí, deshabilitado | Extracción verificada en 1 ficha; no se importó nada |
| Importador web PetSmart | Sí, deshabilitado | **No verificado** (sus términos lo impiden) |
| Envío por SMTP | Sí | Sí, con Gmail (correo de prueba aceptado) |
| PHP 8.2+ / MySQL 8 | Objetivo | **Pendiente**: se verificó en PHP 8.0.28 y MariaDB 10.4.28 |

El detalle de cada prueba está en [docs/PRUEBAS.md](docs/PRUEBAS.md).

## Requisitos

- **PHP 8.2 o superior** con extensiones `pdo_mysql`, `mbstring`, `curl`, `dom`,
  `json` y `openssl`. El código también corre en PHP 8.0 (así se verificó con
  XAMPP), pero PHP 8.0 y 8.1 ya no reciben parches de seguridad.
- **MySQL 8.0.16+ o MariaDB 10.4+** con InnoDB y `utf8mb4`.
- No necesita Node, compilación ni Composer.

## Instalación

### 1. Configuración

```bash
cp .env.example .env
```

Edita `.env`: como mínimo `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
`DB_PASSWORD` y `APP_URL`. En Windows con XAMPP el usuario por defecto es
`root` sin contraseña (solo para desarrollo local).

Para producción crea un usuario con permisos mínimos:

```sql
CREATE DATABASE patita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'patita'@'localhost' IDENTIFIED BY 'una-clave-larga-y-unica';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES ON patita.* TO 'patita'@'localhost';
```

### 2. Base de datos

Opción A, con el script (crea la base si el usuario tiene permiso, aplica el
esquema y carga los datos demo):

```bash
php scripts/install.php
```

Opción B, con el cliente de MySQL:

```bash
mysql -u patita -p patita < database/schema.sql
```

```bash
mysql -u patita -p patita < database/seed.sql
```

Para reinstalar desde cero (borra las 5 tablas del proyecto y pide
confirmación):

```bash
php scripts/install.php --fresh
```

### 3. Administrador

El seed no crea usuarios. Crea el tuyo (la contraseña se pide por la terminal):

```bash
php scripts/create_admin.php --email=tu@correo.com --name="Tu nombre"
```

Para convertir en administradora una cuenta registrada desde la web:

```bash
php scripts/create_admin.php --email=tu@correo.com --promote
```

### 4. Ejecutar

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Abre http://127.0.0.1:8080. En Windows con XAMPP:

```bash
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public public/index.php
```

Las ilustraciones demo ya están generadas en `public/assets/images/demo`. Si
cambias mascotas o categorías, vuelve a generarlas:

```bash
php scripts/generate_demo_images.php
```

## Servidor web

El **document root debe ser `public/`**. Fuera de esa carpeta están la
configuración, el `.env`, los logs, los correos de desarrollo y el código. Como
defensa adicional, `.htaccess` en la raíz, `app/`, `storage/`, `database/`,
`scripts/`, `tests/` y `docs/` niegan todo acceso si el servidor se configura
mal.

Apache (requiere `mod_rewrite`; `AllowOverride All` para `public/`):

```apache
<VirtualHost *:80>
    ServerName patita.test
    DocumentRoot "/ruta/al/proyecto/public"
    <Directory "/ruta/al/proyecto/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Nginx:

```nginx
server {
    server_name patita.test;
    root /ruta/al/proyecto/public;
    index index.php;
    location / { try_files $uri /index.php$is_args$args; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\. { deny all; }
}
```

También puede instalarse en un subdirectorio (`http://localhost/tienda/`): las
URL y redirecciones se calculan a partir de la ubicación de `public/index.php`.
Se verificó que los enlaces y redirecciones llevan el prefijo correcto; la
reescritura de URL con Apache en subdirectorio está pendiente de probar.

## Correo

- **Desarrollo** (`MAIL_TRANSPORT=log`): cada correo se guarda como archivo
  `.eml` en `storage/mail`. Ábrelo con cualquier editor o cliente de correo para
  ver, por ejemplo, el enlace de recuperación de contraseña. Ese enlace nunca se
  muestra en la web.
- **Producción** (`MAIL_TRANSPORT=smtp`): cliente SMTP propio
  (`app/services/SmtpClient.php`, STARTTLS o SSL, verificación de certificado),
  sin dependencias. Completa `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`,
  `SMTP_PASSWORD`, `SMTP_ENCRYPTION` y `MAIL_FROM_ADDRESS`. Con Gmail usa
  `smtp.gmail.com`, puerto `587`, `tls`, tu correo como usuario y remitente, y
  una **contraseña de aplicación** (no la contraseña de la cuenta). Gmail limita
  la cantidad de envíos diarios; para volumen real conviene un servicio
  transaccional.

Prueba la configuración:

```bash
php scripts/test_mail.php --to=tu@correo.com
```

## Imágenes de productos

- **Sube fotos desde el panel** (editar producto → "Subir imagen"): JPG, PNG o
  WebP de hasta 3 MB y 5000 px. Se validan por contenido, se guardan con nombre
  aleatorio en `public/uploads/products` (sin ejecución de scripts) y se borran
  al reemplazarlas o al eliminar el producto. Recomendado: 1200 × 900 px.
- **O escribe una ruta o URL**: una ruta dentro de `public/` o una URL `https`.
- **Sin imagen**: se muestra la ilustración de su mascota y categoría.
- Usa solo fotos propias, del proveedor con permiso o de bancos con licencia
  comercial. No copies imágenes de otras tiendas.
- La carpeta `public/uploads/products` no se sube a Git: respáldala aparte.

## Importador de productos

Solo por línea de comandos; nunca se ejecuta durante la navegación. El panel
(`/admin/importador`) muestra instrucciones, el estado de cada fuente y los
reportes guardados en `storage/imports`.

**JSON o CSV (recomendado).** Identifica productos por SKU (crea o actualiza).
Ejemplos en `database/import-samples/`.

```bash
php scripts/import_products.php --file=database/import-samples/productos-ejemplo.csv --dry-run
```

```bash
php scripts/import_products.php --file=database/import-samples/productos-ejemplo.json
```

Columnas: `sku, name, brand, pet_type, category, description, price, currency,
stock, image_url, data_origin, active` y opcionalmente `source_url,
source_site, source_price, source_currency`. `pet_type` y `category` aceptan
la clave (`dog`, `food`) o el slug (`perros`, `alimento`). El CSV puede usar `,`
o `;`.

**Fuentes web.** Se revisaron robots.txt y términos de uso de petstore.direct y
petsmart.com (ver [docs/DECISIONES.md](docs/DECISIONES.md#4-revisión-de-las-fuentes-de-datos-7-de-octubre-de-2026)).
Ambas restringen la reutilización comercial de su contenido, por lo que vienen
**deshabilitadas** y el catálogo inicial es 100 % demo.

```bash
php scripts/import_products.php --list-sources
```

```bash
php scripts/import_products.php --source=petstore_direct --check-robots --url=https://www.petstore.direct/products/ejemplo
```

Solo con autorización escrita de la fuente: agrega su clave a
`IMPORT_ALLOWED_SOURCES` y ejecuta, por ejemplo,
`--source=petstore_direct --urls-file=urls.txt --limit=10 --dry-run`. El
importador consulta robots.txt, usa un User-Agent descriptivo, pausa entre
solicitudes, caché y reintentos limitados, se detiene ante CAPTCHA o bloqueos,
prioriza JSON-LD `Product`, deduplica por fuente + URL y crea los productos
inactivos, con precio 0 y stock 0 hasta que un administrador los revise. No
convierte precios externos ni usa el stock externo.

## Configuración regional

| Variable | Demo | Uso |
|----------|------|-----|
| `APP_COUNTRY` | `VE` | País de la tienda |
| `APP_CURRENCY` | `USD` | Moneda de venta (código ISO 4217) |
| `APP_LOCALE` | `es-US` | Formato de precios con `Intl.NumberFormat` |
| `APP_TIMEZONE` | `America/Caracas` | Zona para mostrar fechas (se guardan en UTC) |
| `SHIPPING_COUNTRIES` | `*` | Países de envío: lista ISO separada por comas o `*` para todos (219) |
| `SHIPPING_FLAT_RATE` / `SHIPPING_FREE_FROM` | `99.00` / `999.00` | Costo de envío y umbral de envío gratis |
| `PAYMENT_METHODS` | `bank_transfer,cash_on_delivery` | Formas de pago demostrativas |

Los productos demo están en USD. Si cambias `APP_CURRENCY`, no se mostrarán
hasta que actualices su precio y moneda desde el panel o con un archivo de
importación (nunca se mezclan monedas en un pedido).

## Seguridad

- Contraseñas con `password_hash` / `password_verify` (bcrypt), rehash
  automático; tiempo de respuesta igual exista o no el correo.
- Sesión con cookie `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS, modo
  estricto, ID regenerado al autenticar y cierre por inactividad. Cambiar o
  recuperar la contraseña cierra las demás sesiones.
- CSRF obligatorio en todo POST (también en logout y en peticiones `fetch`).
- Escape de toda salida con `e()`; Content-Security-Policy sin scripts ni
  estilos en línea; `X-Frame-Options: DENY`; `nosniff`; `Referrer-Policy`.
- Consultas preparadas reales; `sql_mode` estricto en cada conexión; entradas
  con UTF-8 inválido se sanean.
- Redirecciones solo a rutas internas.
- Límite de intentos de login y de solicitudes de recuperación con archivos
  locales. **Limitaciones:** solo sirve con un único servidor, depende de la IP
  de `REMOTE_ADDR` y falla de forma abierta si `storage/ratelimit` no es
  escribible. Detalles en [docs/PRODUCCION.md](docs/PRODUCCION.md).
- Recuperación de contraseña: token de 256 bits, solo su hash SHA-256 en la
  base de datos, 60 minutos de vigencia, un solo uso, mensaje idéntico exista
  o no el correo, token fuera de la URL tras abrir el enlace.

## Estructura

```
.
├── .env.example            Variables de entorno de ejemplo (sin credenciales)
├── .htaccess               Niega todo si la raíz se publica por error
├── composer.json           Sin dependencias
├── app/
│   ├── bootstrap.php       Autoload, .env, errores
│   ├── helpers.php         e(), url(), precios, catálogo, registro
│   ├── routes.php          Todas las rutas
│   ├── config/             app.php (.env) y catalog.php (listas controladas)
│   ├── core/               Kernel, Router, Request, Response, View, Session, Csrf, Validator, Database, Env
│   ├── controllers/        Tienda, carrito, checkout, autenticación, cuenta, administración
│   ├── middleware/         Auth (auth, admin, guest)
│   ├── models/             User, Product, Order, PasswordResetToken
│   ├── services/           Auth, Cart, CheckoutService, Mailer, RateLimiter
│   │   └── import/         HttpClient, RobotsTxt, RobotsGuard, JsonLdProductExtractor,
│   │                       adaptadores por sitio, FileImportReader, ProductImporter, ImportReport
│   └── views/              Plantillas PHP (layout, parciales, páginas)
├── database/
│   ├── schema.sql          5 tablas
│   ├── seed.sql            48 productos demo (4 por mascota + estética + granja)
│   └── import-samples/     Ejemplos JSON y CSV
├── docs/                   DECISIONES.md, PRUEBAS.md, PRODUCCION.md
├── public/                 Document root
│   ├── index.php           Front controller
│   └── assets/             css/app.css, js/app.js, images/ (logo, iconos, ilustraciones demo)
├── scripts/                install.php, create_admin.php, import_products.php, generate_demo_images.php,
│                           test_mail.php, build_infinityfree.php
├── storage/                cache/, logs/, mail/, ratelimit/, imports/ (sin acceso web)
└── tests/                  run.php y fixtures
```

## Pruebas

```bash
php tests/run.php
```

81 pruebas unitarias sin base de datos. El checklist manual completo, con lo
verificado y lo pendiente, está en [docs/PRUEBAS.md](docs/PRUEBAS.md).

## Créditos

Iconos de [Lucide](https://lucide.dev) (licencia ISC) y
[Tabler Icons](https://tabler.io/icons) (licencia MIT: caballo, comedero y
herradura). Tipografía [Archivo](https://fonts.google.com/specimen/Archivo)
(SIL Open Font License). Logotipo e ilustraciones demo creados para este
proyecto.
