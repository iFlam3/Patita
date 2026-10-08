# Limitaciones y pendientes para producción

Este proyecto es una demo funcional. Esta lista separa lo que falta probar de lo
que falta construir antes de vender de verdad.

## Pendiente de probar (implementado, pero no ejecutado)

- **PHP 8.2, 8.3 y 8.4.** Todo se verificó en PHP 8.0.28 (XAMPP). El código
  evita sintaxis y funciones deprecadas conocidas, pero no se ejecutó en 8.2+.
  PHP 8.0 ya no recibe parches de seguridad: no lo uses en producción.
- **MySQL 8.** Se verificó en MariaDB 10.4.28. El esquema usa sintaxis
  compatible (CHECK requiere MySQL 8.0.16+; las funciones de ventana del bloque
  de destacados requieren MySQL 8.0+ o MariaDB 10.2+).
- **Apache con `.htaccess`.** Se verificó con el servidor embebido de PHP. Las
  reglas de `public/.htaccess` y los bloqueos de `storage/`, `app/` y la raíz
  no se probaron en Apache/Nginx.
- **Navegadores.** La interfaz se revisó en el navegador integrado (Chromium) a
  375, 800 y 1100 px de ancho. Falta Safari (iOS), Firefox y dispositivos reales.
- **Lectores de pantalla.** Se comprobaron etiquetas, `alt`, foco visible,
  navegación por teclado y regiones `aria-live`, pero no se hizo una prueba con
  NVDA, JAWS o VoiceOver.
- **Concurrencia a escala.** Se probaron dos confirmaciones simultáneas del
  mismo pedido (resultado: un solo pedido). No se hizo una prueba de carga con
  muchos compradores sobre el mismo producto.

## Limitaciones conocidas del diseño

### Límite de intentos con archivos locales

`app/services/RateLimiter.php` guarda los intentos en `storage/ratelimit` para no
agregar una tabla. Límites por defecto: 5 intentos fallidos por correo + IP y
20 por IP en 15 minutos; 3 solicitudes de recuperación por correo + IP.

- **Un solo servidor.** Con varias instancias detrás de un balanceador, cada una
  cuenta por separado: el límite real se multiplica.
- **Depende de la IP.** Usuarios detrás de la misma NAT (oficina, red móvil)
  comparten el límite por IP. Un atacante con muchas IP evita el límite por IP,
  aunque cada combinación correo + IP sigue limitada.
- **Detrás de un proxy** hay que configurar el servidor web para que
  `REMOTE_ADDR` sea la IP real del cliente. La app no confía en
  `X-Forwarded-For` porque el cliente puede falsificarla.
- **Crecimiento de archivos.** Cada clave crea un archivo pequeño; los vencidos
  se borran de forma probabilística. Un ataque masivo puede crear muchos.
- **Falla abierta.** Si `storage/ratelimit` no es escribible, el límite deja de
  aplicarse (se registra en `storage/logs/security.log`).
- Para producción conviene Redis o una tabla dedicada, o un límite en el
  servidor web o en un WAF.

### Autenticación

- **No se verifica la propiedad del correo** al registrarse (solo formato y
  unicidad). Requiere una columna `email_verified_at` y un flujo de enlace.
- **Cerrar otras sesiones**: al cambiar o recuperar la contraseña, las demás
  sesiones se invalidan en su siguiente petición (huella del hash en la sesión).
  No hay una lista de sesiones activas ni "cerrar todas" manual.
- **Recuperación de contraseña**: el mensaje es idéntico exista o no el correo,
  pero el tiempo de respuesta puede diferir porque el correo se envía dentro de
  la misma petición. Con SMTP lento la diferencia es medible. Solución: cola de
  envío asíncrona.
- **Sin 2FA** para administradores ni bloqueo de cuenta notificado por correo.
- **Contraseñas**: mínimo 8 caracteres y máximo 72 bytes (límite de bcrypt). No
  se comparan contra listas de contraseñas filtradas.

### Tienda

- **El carrito no reserva stock.** El stock se valida al agregar y se descuenta
  de forma atómica al confirmar; si otro cliente compra antes, el resumen avisa
  y ajusta.
- **Búsqueda con `LIKE`.** Suficiente para cientos o pocos miles de productos.
  Para catálogos grandes conviene FULLTEXT o un motor de búsqueda.
- **Sin variantes, cupones, reseñas, lista de deseos ni seguimiento de envío.**
- **Pagos demostrativos.** No hay conciliación de transferencias ni integración
  con pasarelas. Integrar una pasarela local requiere su SDK, webhooks y estados
  de pago adicionales.
- **Envío**: tarifa única por pedido. No hay zonas, peso, volumen ni plazos.
- **Impuestos y facturación**: no se calculan ni se emiten comprobantes.
- **Correos de estado**: solo se envía la confirmación del pedido; los cambios
  de estado no notifican al cliente.

### Páginas legales, guías y herramientas

- **Términos, privacidad y devoluciones son textos base genéricos.** Antes de
  publicar debe revisarlos un abogado según el país de operación (protección al
  consumidor, datos personales, plazos legales de devolución). El plazo de 15
  días de devolución es una política de la tienda, no un requisito legal.
- **Guías de cuidado** con información general de divulgación; conviene que las
  revise un veterinario antes del lanzamiento.
- **Alimentos peligrosos**: basado en listas públicas de la ASPCA. Cubre 11
  alimentos; donde no hay dato verificado para una especie se muestra "Sin
  datos" en lugar de suponer que es seguro.
- **Edad y peso saludable**: estimaciones orientativas (tablas de equivalencia y
  escala de condición corporal de 9 puntos), solo para perros y gatos.
- **Imágenes subidas**: se validan pero no se re-codifican ni redimensionan
  porque la extensión GD no está habilitada en el entorno de prueba. En
  producción conviene activar GD o Imagick para generar versiones optimizadas.

### Contenido y marca

- **Marcas ficticias** en el catálogo demo. Reemplázalas por marcas reales con
  permiso de sus titulares o de tus proveedores.
- **Ilustraciones de demostración**: hay que sustituirlas por fotos propias o
  con licencia.
- **Nombre y logotipo provisionales**: verificar marca y dominio antes de usar.
- **Fuente Archivo desde Google Fonts**: implica una solicitud a Google en cada
  visita. Para privacidad y rendimiento, alojarla en `public/assets/fonts`
  (licencia OFL) y ajustar el CSP.
- **Importación desde sitios externos**: requiere autorización escrita de cada
  fuente. Los adaptadores pueden dejar de funcionar si cambia la estructura.

## Antes de lanzar (checklist)

1. Servidor con PHP 8.2+ con soporte vigente y MySQL 8 o MariaDB con soporte.
2. HTTPS obligatorio; `APP_ENV=production`, `APP_DEBUG=false`,
   `SESSION_SECURE_COOKIE=true`, `APP_URL` con `https://`.
3. DocumentRoot en `public/`; comprobar que `/.env`, `/storage/`, `/app/` y
   `/database/` responden 403 o 404.
4. Usuario de base de datos propio con permisos mínimos (no `root`).
5. SMTP real configurado y probado (SPF, DKIM y DMARC del dominio remitente).
6. Respaldos automáticos de la base de datos y rotación de `storage/logs`.
7. Revisión legal por país: aviso de privacidad y tratamiento de datos
   personales, términos y condiciones, políticas de devolución y garantías,
   protección al consumidor, facturación e impuestos.
8. Medios de pago reales y su conciliación; datos bancarios verdaderos en
   `BANK_TRANSFER_DETAILS`.
9. Catálogo real: marcas, precios, stock, fotos y descripciones con permiso.
10. Prueba de accesibilidad con lector de pantalla y prueba en dispositivos.
11. Monitoreo de errores (los errores se registran en `storage/logs/app.log`).
