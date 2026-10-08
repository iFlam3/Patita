# Checklist de pruebas manuales

Estado al 7 de octubre de 2026, con PHP 8.0.28 y MariaDB 10.4.28 (XAMPP,
Windows 11) y el servidor embebido de PHP.

- **Verificado**: se ejecutó y el resultado fue el esperado.
- **Pendiente**: implementado, sin ejecutar en este entorno.

Pruebas automáticas sin base de datos: `php tests/run.php` (81 pruebas:
robots.txt, JSON-LD, importes, validación, CSV, detección de bloqueos, huella
del carrito, calculadoras y países). Resultado: 81 correctas.

## Instalación

| Prueba | Pasos | Esperado | Estado |
|--------|-------|----------|--------|
| Instalación limpia | `php scripts/install.php` | 5 tablas y 28 productos demo | Verificado |
| Reinstalación | `php scripts/install.php --fresh` | Pide confirmación y recrea las tablas | Verificado (con `--yes`) |
| Ilustraciones | `php scripts/generate_demo_images.php` | 64 SVG en `public/assets/images/demo` | Verificado |
| Administrador | `php scripts/create_admin.php --email=... --name=...` | Cuenta con rol admin | Verificado |
| Acceso a archivos internos | Abrir `/.env`, `/../.env` | 404 | Verificado (servidor embebido) |
| Bloqueo en Apache | Abrir `/storage/`, `/app/` con DocumentRoot mal configurado | 403 | Pendiente |

## Catálogo y navegación

| Prueba | Esperado | Estado |
|--------|----------|--------|
| Inicio muestra hero, 7 mascotas, destacados, marcas, beneficios y pie | Todo visible, sin desbordamiento horizontal en 375 y 1100 px | Verificado |
| Comprar por mascota → Animales pequeños | Chips de categoría y búsqueda por conejos, cuyos, hámsteres, chinchillas y hurones | Verificado |
| Comprar por marca → una marca | Solo productos de esa marca; marcas demo rotuladas como ficticias | Verificado |
| Buscar "hamster" o "huron" (sin tilde) | Encuentra "hámster" y "hurón" | Verificado |
| Filtros combinados (mascota + categoría + precio + con stock) | Resultados correctos y chips para quitar cada filtro | Verificado |
| Ordenar por precio y nombre | Orden correcto | Verificado (precio descendente) |
| Paginación | 12 por página; "Anterior/Siguiente" y página actual marcada | Verificado |
| Filtros sin recargar (JS) | Indicador "Cargando productos…", URL actualizada, botón Atrás funciona | Verificado |
| Sin resultados | Mensaje y botón para ver todo | Verificado |
| Error de red al filtrar | Mensaje de error con "Reintentar" que recupera los resultados | Verificado (falla simulada) |
| Catálogo sin JavaScript | El formulario envía por GET y pagina con enlaces | Pendiente de prueba manual con JS desactivado |
| Producto sin stock | Mensaje "Sin existencias por ahora" y sin botón de compra | Verificado |
| Producto inexistente o inactivo | 404 en español | Verificado |
| Precios con `Intl.NumberFormat` | `$29.99` en `es-US` | Verificado |

## Carrito

| Prueba | Esperado | Estado |
|--------|----------|--------|
| Agregar desde tarjeta o ficha (JS) | Aviso accesible y contador actualizado sin recargar | Verificado |
| Agregar sin JS | Redirección con mensaje | Verificado (curl) |
| Enviar un precio falso en el formulario | Se ignora; el subtotal se calcula en el servidor | Verificado |
| Pedir más unidades que el stock | La cantidad se ajusta al stock | Verificado (10 → 3) |
| Agregar producto agotado | Rechazado con mensaje | Verificado |
| Actualizar cantidad y eliminar | Totales recalculados | Verificado |
| `return_to` hacia otro sitio | Se ignora (redirección abierta bloqueada) | Verificado |
| Envío gratis al superar el umbral | "Gratis" en carrito y resumen | Verificado |
| Producto desactivado mientras está en el carrito | Se retira con aviso | Verificado |

## Checkout y pedidos

| Prueba | Esperado | Estado |
|--------|----------|--------|
| Checkout sin sesión | Redirige a Ingresar y vuelve al checkout tras ingresar | Verificado |
| Datos inválidos | Errores por campo con `aria-invalid` | Verificado (7 errores) |
| Resumen antes de confirmar | Productos, dirección, contacto, pago, envío y total | Verificado |
| Huella del carrito alterada | No crea el pedido y pide revisar | Verificado |
| Doble clic en "Confirmar pedido" | Un solo pedido | Verificado (navegador) |
| Dos envíos simultáneos con el mismo token | Un solo pedido; ambos ven la misma confirmación | Verificado (curl en paralelo) |
| Reenvío del formulario después de confirmar | Muestra el pedido existente | Verificado |
| Stock descontado | Se resta lo comprado | Verificado (3 → 0) |
| Confirmación | Número PAT-000001, estado e instrucciones de pago | Verificado |
| Correo de confirmación | Archivo `.eml` en `storage/mail` | Verificado |
| Historial y detalle en Mi cuenta | Lista y detalle con precios históricos | Verificado |
| Pedido de otro usuario | 404 | Verificado |

## Autenticación

| Prueba | Esperado | Estado |
|--------|----------|--------|
| Registro | Crea la cuenta e inicia sesión | Verificado |
| Correo duplicado (aunque cambien mayúsculas) | Mensaje en el campo | Verificado |
| Login incorrecto | Mensaje genérico | Verificado |
| 6 intentos fallidos | Bloqueo temporal aun con la contraseña correcta | Verificado |
| Logout | Solo por POST con CSRF; luego `/cuenta` pide ingresar | Verificado |
| POST sin token CSRF o con token falso | 419 "La página expiró" | Verificado |
| Cliente entra a `/admin` | 403 | Verificado |
| Recuperación con correo inexistente | Mismo mensaje y ningún correo enviado | Verificado |
| Token de recuperación | Solo el hash SHA-256 en la base de datos | Verificado |
| Enlace de recuperación | Pasa el token a la sesión, limpia la URL, `Referrer-Policy: no-referrer` | Verificado |
| Reusar el enlace | "El enlace no es válido, ya se usó o venció" | Verificado |
| Enlace vencido (60 min) | Rechazado | Pendiente (requiere esperar o editar `expires_at`) |
| Cambio de contraseña | La sesión actual sigue; las demás se cierran | Verificado |
| Cambio de correo sin contraseña actual | Rechazado | Verificado |
| Contraseña anterior tras recuperar | Ya no funciona | Verificado |

## Administración

| Prueba | Esperado | Estado |
|--------|----------|--------|
| Panel, productos, pedidos, importador | Cargan para el rol admin | Verificado |
| Crear producto con precio 0 publicado | Rechazado | Verificado |
| Crear producto válido | Visible en la tienda; SKU en mayúsculas; slug generado | Verificado |
| Validación del formulario | Errores por campo | Verificado (9 errores) |
| Desactivar | Deja de verse (404) | Verificado |
| Eliminar definitivamente | Pide confirmación; los pedidos conservan su detalle | Verificado (eliminación) |
| Cambiar estado de pedido | Se guarda | Verificado |
| Cancelar pedido | Devuelve unidades al stock | Verificado (0 → 3) |
| Reabrir un pedido cancelado | No permitido | Verificado |

## Importador

| Prueba | Esperado | Estado |
|--------|----------|--------|
| CSV de ejemplo con `--dry-run` | Valida sin escribir | Verificado |
| JSON de ejemplo | Crea 2 productos; la segunda vez los actualiza (sin duplicar) | Verificado |
| Filas inválidas | Se descartan con el detalle del error | Verificado |
| Producto en otra moneda | Se importa inactivo con aviso | Verificado |
| `--list-sources` | Verificación y condiciones de cada fuente | Verificado |
| Fuente no habilitada | Se niega con código 3 y explica por qué | Verificado |
| `--check-robots` contra robots.txt real | Permite `/products/x`, bloquea `?oseid=` | Verificado |
| Adaptador petstore.direct sobre una ficha real | Extrae nombre, marca, descripción, SKU, imagen, precio y moneda | Verificado (1 ficha, sin guardar contenido) |
| Detección de CAPTCHA o bloqueo | Detiene la ejecución | Verificado (unitaria); no se provocó un bloqueo real |
| Importación web completa | Productos inactivos con `data_origin = scraped` | Pendiente (requiere autorización escrita de la fuente) |
| Adaptador PetSmart | — | No verificado (los términos de uso lo impiden) |

## Accesibilidad y diseño

| Prueba | Esperado | Estado |
|--------|----------|--------|
| Etiquetas de formulario, `alt`, un `h1`, IDs únicos | Sin faltantes en 11 páginas | Verificado (script) |
| Foco visible | Contorno de 3 px | Verificado |
| Menú "Comprar por mascota" con teclado | Enter abre, Tab recorre, Escape cierra y devuelve el foco | Verificado |
| Enlace "Saltar al contenido" | Primer elemento enfocable | Verificado |
| Contraste | Ver tabla en `DECISIONES.md` | Verificado (cálculo) |
| Lector de pantalla | — | Pendiente |

## Ampliación del 8 de octubre de 2026

| Prueba | Esperado | Estado |
|--------|----------|--------|
| Páginas nuevas (cuidados, herramientas, acerca de, preguntas frecuentes, términos, privacidad, devoluciones, contacto, granja) | 200 | Verificado |
| Guía de cuidado inexistente | 404 | Verificado |
| Edad: perro grande de 7 años y 6 meses | ≈ 57 años humanos | Verificado |
| Edad con texto inválido | Mensaje de error | Verificado |
| Chocolate para perro | "Tóxico: no se lo des" | Verificado |
| Alimento sin dato para la especie | "Sin datos verificados" con advertencia | Verificado |
| Peso: gato de 6 kg con sobrepeso | ≈ 5.0 kg de referencia | Verificado |
| Países en el checkout | 219, con Venezuela primero | Verificado |
| Subida de imagen válida (PNG) | Se guarda en uploads/products y se ve en la ficha | Verificado |
| Archivo con código disfrazado de PNG | Rechazado | Verificado |
| Filtros de precio Desde/Hasta alineados | Misma altura | Verificado |
| Pie de página al fondo en páginas cortas | Layout de altura completa | Implementado; pendiente de revisión visual |
| Bloqueo de scripts en `public/uploads` con Apache | 403 | Pendiente (solo probado con servidor embebido) |
| Envío real por SMTP (Gmail, STARTTLS, contraseña de aplicación) | Correo de prueba aceptado por el servidor | Verificado |
| Etiquetas formales en alimentos peligrosos | "Tóxico para esta especie", "No recomendado", "Sin información verificada" | Verificado |
| Paquete de InfinityFree en Apache real (XAMPP, carpeta temporal) | Sitio, rutas, sesión, carrito y registro funcionan; `_app`, `.env`, `.htaccess` y `storage` dan 403 | Verificado |
| Paquete en el servidor de InfinityFree | — | Pendiente (lo haces tú con la guía) |
| Correo SMTP desde InfinityFree | — | Pendiente: el plan gratuito podría bloquear puertos SMTP |
