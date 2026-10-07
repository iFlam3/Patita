# Decisiones del proyecto

Documento de referencia para la identidad, la arquitectura y las concesiones del
proyecto. Fecha de redacción: 7 de octubre de 2026.

## 1. Nombre de la tienda

Ninguno de estos nombres fue verificado como marca registrada ni como dominio
disponible. Antes de usarlos comercialmente hay que consultar el registro de
marcas de cada país donde se venda (por ejemplo IMPI en México, SIC en
Colombia, INAPI en Chile o INDECOPI en Perú) y la disponibilidad de dominios.

| # | Nombre | Por qué funciona | Eslogan |
|---|--------|------------------|---------|
| 1 | **Arca Verde** | El arca reúne a todas las especies; "verde" conecta con la paleta y con lo natural. Dos palabras cortas y comunes en todo el español. | *Todo para tu mascota, sea cual sea.* |
| 2 | Huella y Escama | Une a los mamíferos (huella) con peces y reptiles (escama). | *Para cada huella y cada escama.* |
| 3 | Nido & Corral | Va del ave al caballo; suena cercano y de campo. | *Del nido al corral, todo lo que necesitan.* |
| 4 | Tribu Animal | "Tribu" sugiere familia diversa; fácil de recordar. | *Tu tribu, bien cuidada.* |
| 5 | Patas, Plumas y Aletas | Describe literalmente la variedad de especies. | *Si tiene patas, plumas o aletas, lo tenemos.* |
| 6 | Madriguera | Evoca refugio y hogar; neutral entre especies. | *El lugar donde tu mascota se siente en casa.* |
| 7 | Fauna Casera | Habla de los animales que viven en casa sin limitarse a perros y gatos. | *La fauna de tu casa, atendida.* |
| 8 | Bicho Bien | Coloquial y simpático en gran parte de Latinoamérica. | *Para que tu bicho esté bien.* |
| 9 | Pradera | Natural y amplio; funciona para caballos, conejos y aves. | *Todo crece mejor en la pradera.* |
| 10 | Cola y Pluma | Breve, sonoro y abarca mamíferos y aves. | *Insumos para colas y plumas de todo tipo.* |

**Nombre elegido: Patita.** Se eligió por encima de las propuestas anteriores
(la recomendación inicial era Arca Verde). Es corto, cariñoso y fácil de decir
en cualquier variante del español. Como "patita" remite sobre todo a mamíferos,
la portada y la navegación insisten en las siete mascotas para no excluir a
aves, peces y reptiles. El logotipo provisional es una huella verde dibujada
para este proyecto. No copia logotipos, empaques ni identidades de Dog Chow,
Purina ni de otras marcas. Tampoco se verificó la disponibilidad de "Patita"
como marca o dominio.

## 2. Decisiones principales y supuestos

| Tema | Decisión | Motivo |
|------|----------|--------|
| Backend | PHP sin framework, front controller + MVC mínimo, PDO con consultas preparadas reales (`ATTR_EMULATE_PREPARES = false`). | Lo pide el brief; sencillo de mantener y de desplegar en hosting compartido. |
| Versión de PHP | Objetivo PHP 8.2+. El código evita sintaxis exclusiva de 8.1+ (enums, `readonly`, `never`). | El único PHP disponible para verificar era 8.0.28 (XAMPP). Así se pudo ejecutar todo; no se probó en 8.2/8.3/8.4 (ver `PRODUCCION.md`). |
| Estilos | CSS propio con tokens en `:root` (sin Tailwind). | No requiere compilación ni Node; el CSS entregado es el final. |
| JavaScript | Vanilla, como mejora progresiva. | Todo funciona sin JS; con JS el catálogo filtra sin recargar, el carrito agrega sin salir de la página y los precios se formatean con `Intl.NumberFormat`. |
| Dependencias | Ninguna obligatoria. PHPMailer solo si se usa SMTP (`composer require phpmailer/phpmailer`). | En desarrollo el correo se guarda como `.eml` en `storage/mail`. |
| País y moneda demo | El Salvador, dólar estadounidense (USD), `es-SV`, zona `America/El_Salvador`. Se eligió un país dolarizado para que USD sea su moneda local y `Intl` muestre `$1,234.50`. | Configurables en `.env` (`APP_COUNTRY`, `APP_CURRENCY`, `APP_LOCALE`, `APP_TIMEZONE`). No se asume una moneda común para la región. |
| Monedas | Solo se venden productos en la moneda de la tienda. Un producto en otra moneda queda oculto e inactivo. | Así un pedido nunca mezcla monedas. |
| Precio de origen | `source_price`/`source_currency` se guardan aparte y nunca se convierten. | El precio local lo define un administrador. |
| Pagos | Transferencia bancaria y pago contra entrega, configurables con `PAYMENT_METHODS`. | Modalidad demostrativa: no hay cobros ni datos de tarjeta. |
| Envío | Tarifa fija y umbral de envío gratis (`SHIPPING_FLAT_RATE`, `SHIPPING_FREE_FROM`), visibles en carrito, resumen y página "Envíos y pagos". | No se inventan zonas ni plazos de entrega. |
| Impuestos | No se calculan. | No existe una regla fiscal común para Latinoamérica; se documenta como pendiente. |
| Checkout | Tres pasos: datos → resumen → confirmación. | El brief pide mostrar un resumen antes de confirmar. |
| Fechas | Se guardan en UTC y se muestran en `APP_TIMEZONE`. | Evita ambigüedades si cambia la zona horaria. |

## 3. Esquema de base de datos (5 tablas)

Tablas: `users`, `products`, `orders`, `order_items`, `password_reset_tokens`.
El script completo está en `database/schema.sql`.

### Cambios respecto al diseño esperado

- **`orders.subtotal` y `orders.shipping_cost` (columnas nuevas, no tablas).**
  El brief pide costos de envío configurables y visibles. Sin estas columnas el
  total del pedido no se podría explicar ni auditar si después cambia la tarifa
  de envío. No agregan tablas.
- **`orders.status`** usa los valores `received`, `paid`, `preparing`,
  `shipped`, `delivered`, `cancelled` (etiquetas en español en
  `app/config/catalog.php`). `delivered` y `cancelled` son finales. Al cancelar,
  las unidades vuelven al stock dentro de una transacción.
- **`products.image_url`** es `NOT NULL DEFAULT ''`: vacío significa "usar la
  ilustración local según mascota y categoría".

### Concesiones del esquema simplificado

- **Marcas, mascotas y categorías son texto**, no tablas. Las marcas y
  categorías se obtienen con `SELECT DISTINCT`; mascotas, categorías, roles y
  estados son listas controladas en `app/config/catalog.php`. Consecuencia: no
  hay ficha de marca (logo, descripción) y renombrar una marca requiere un
  `UPDATE` sobre los productos.
- **Un producto pertenece a una sola mascota y una sola categoría.** Un
  producto apto para varias especies debe duplicarse o elegirse la principal.
  No se crearon compatibilidades entre especies.
- **Sin variantes** (talla, color, peso): cada variante es un producto con su
  SKU.
- **Las subespecies de "Animales pequeños"** (conejos, cuyos, hámsteres,
  chinchillas, hurones) no tienen columna: se ofrecen como búsquedas de texto.
- **La dirección del pedido es un snapshot JSON** en `orders.shipping_address`
  (calle, complemento, ciudad, región, código postal opcional, país,
  referencias). No hay libreta de direcciones: el checkout precarga los datos
  del último pedido.
- **El carrito vive en la sesión PHP**: se pierde al cerrar sesión o al vencer
  la sesión, y no reserva stock (el stock se valida y descuenta al confirmar).
- **El límite de intentos y los reportes del importador usan archivos**
  (`storage/ratelimit`, `storage/imports`) para no agregar tablas.
- **No hay verificación de propiedad del correo** (enlace de confirmación al
  registrarse): requeriría una columna o tabla adicional. Se valida formato y
  unicidad. Ver `PRODUCCION.md`.
- **Integridad**: claves foráneas (`order_items.product_id` pasa a `NULL` si se
  elimina el producto; las líneas conservan nombre, SKU y precio), índices
  `UNIQUE` en `email`, `sku`, `slug`, `checkout_token`, `token_hash` y
  `(source_site, source_url)`, y `CHECK` en roles, estados, origen e importes.
  La aplicación además fuerza `sql_mode` estricto en cada conexión.

## 4. Revisión de las fuentes de datos (7 de octubre de 2026)

| Fuente | robots.txt | Términos de uso | Decisión |
|--------|-----------|-----------------|----------|
| www.petstore.direct (Shopify; PetStore.Direct LLC, Florida, EE. UU.) | Permite `/products/` para `User-agent: *`; bloquea carrito, cuenta, búsqueda, colecciones ordenadas o filtradas y `/policies/`. | Prohíben copiar, reproducir o republicar contenido con fines públicos o comerciales sin consentimiento previo por escrito. | Adaptador construido y **verificado técnicamente** sobre una ficha (JSON-LD `Product`). **Deshabilitado**: no se importó ningún producto. |
| www.petsmart.com | Bloquea toda URL con parámetros (`/*?`), `/search`, `/cart`, `/checkout`. | Prohíben acceder al sitio para construir un servicio similar o competitivo, descargar o recopilar contenido y explotarlo comercialmente. | **No se inspeccionaron fichas.** Adaptador entregado como **no verificado**, solo JSON-LD genérico, sin selectores HTML. **Deshabilitado.** |

Consecuencias:

- El catálogo inicial (28 productos) es **100 % de demostración**
  (`data_origin = 'demo'`), con marcas ficticias, precios y stock de prueba,
  descripciones genéricas sin indicaciones veterinarias e ilustraciones propias.
  No se atribuye a ninguna de las dos fuentes.
- Que una página sea pública o que robots.txt la permita **no otorga derechos
  de reutilización comercial**. Para habilitar una fuente hace falta
  autorización escrita y agregar su clave a `IMPORT_ALLOWED_SOURCES`.
- Aun con autorización, el importador no copia descripciones
  (`IMPORT_COPY_DESCRIPTIONS=false`) ni usa imágenes remotas
  (`IMPORT_USE_REMOTE_IMAGES=false`) por defecto, no usa el stock externo y deja
  cada producto inactivo con precio 0 hasta su revisión.

## 5. Identidad visual

Paleta del brief (el verde es una propuesta de diseño y el rojo una elección del
proyecto; ninguno se presenta como código oficial de Dog Chow ni de Purina):

| Token | Valor | Uso |
|-------|-------|-----|
| `--green` | #218739 | Identidad, botones principales, banda de navegación, hero. |
| `--green-ink` | #1B6E2F | Textos y enlaces verdes sobre fondos claros. |
| `--green-deep` | #14522A | Estados hover y activos. |
| `--white` | #FFFFFF | Fondo principal. |
| `--neutral` | #F5F7F3 | Fondos de sección y de imágenes. |
| `--carbon` | #231F20 | Texto principal, franja de aviso demo, pie. |
| `--red` | #F30017 | Solo acentos decorativos (puntos de aviso, poco stock). |
| `--red-ink` | #C40014 | Errores, contador del carrito, avisos con texto. |

Contraste medido (relación de luminancia WCAG 2.1):

| Combinación | Relación | Resultado |
|-------------|----------|-----------|
| Blanco sobre #218739 | 4.58:1 | AA texto normal |
| #1B6E2F sobre blanco | 6.33:1 | AA |
| #1B6E2F sobre #F5F7F3 | 5.87:1 | AA |
| #231F20 sobre blanco | 16.3:1 | AAA |
| Blanco sobre #C40014 | 6.25:1 | AA |
| Blanco sobre #F30017 | 4.36:1 | No apto para texto pequeño: por eso el rojo puro es solo decorativo |
| #218739 sobre #F5F7F3 | 4.25:1 | No apto para texto pequeño: por eso existe `--green-ink` |

Tipografía: **Archivo** (Google Fonts, fuente variable con eje de anchura). Los
títulos usan la anchura expandida (`font-stretch: 118–125%`) y peso 800–850,
como el lenguaje gráfico de los empaques de alimento; el texto corrido usa el
ancho normal. Si Google Fonts no carga, se usa la fuente del sistema.

Elemento distintivo: el hero verde con siete animales sobre el casco de un
arca. Todo lo demás es sobrio: bordes en lugar de sombras, radios distintos por
jerarquía, sin carruseles y sin animaciones de entrada; solo hay transiciones
de respuesta a acciones (aviso de carrito, hover) y se respetan las
preferencias de movimiento reducido.

## 6. Adaptación a Latinoamérica

- Interfaz completamente en español latinoamericano, con trato de "tú".
- País, moneda, idioma regional y zona horaria configurables.
- Precios con `Intl.NumberFormat` en el navegador y formato de respaldo en el
  servidor.
- Teléfono flexible (7 a 15 dígitos, admite `+`, espacios, guiones y paréntesis).
- Dirección flexible: complemento, región y código postal son opcionales.
- Países de envío configurables (`SHIPPING_COUNTRIES`).
- Pendiente antes de un lanzamiento real: impuestos, facturación electrónica,
  normas de protección al consumidor y de datos personales de cada país,
  medios de pago locales y logística. Ver `PRODUCCION.md`.
