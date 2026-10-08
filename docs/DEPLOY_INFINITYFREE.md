# Publicar Patita en InfinityFree, paso a paso

Tiempo estimado: 30 a 45 minutos. Necesitas tu PC con el proyecto, XAMPP y
FileZilla (gratis, https://filezilla-project.org).

> En InfinityFree no hay terminal (SSH): los scripts no se ejecutan en el
> servidor. Por eso el paquete se arma en tu PC y la base de datos se importa
> desde phpMyAdmin.

---

## 1. Crear la cuenta y el sitio

1. Regístrate en https://www.infinityfree.com y confirma tu correo.
2. En el área de cliente pulsa **Create Account** (crear cuenta de hosting).
3. Elige un **subdominio gratuito** (por ejemplo `patita.infinityfreeapp.com`)
   o tu propio dominio.
4. Espera a que la cuenta quede **Active**. El subdominio puede tardar desde
   unos minutos hasta algunas horas en responder.
5. En la cuenta de hosting abre **Account Details** (detalles de la cuenta) y
   anota:
   - **Username**: `if0_XXXXXXXX`
   - **Password** de la cuenta de hosting (botón *Show*). Es la misma para FTP y MySQL.
   - **FTP hostname**: `ftpupload.net`
   - **PHP version**: debe ser 8.0 o superior (ideal 8.2+).

## 2. Crear la base de datos

1. Desde la cuenta de hosting abre el **Control Panel**.
2. Entra a **MySQL Databases**.
3. En *New database* escribe `patita` y pulsa **Create Database**.
4. Anota los datos que aparecen en la tabla:
   - **MySQL DB Name**: `if0_XXXXXXXX_patita`
   - **MySQL User Name**: `if0_XXXXXXXX`
   - **MySQL Host Name**: `sqlXXX.infinityfree.com`
   - **Contraseña**: la de la cuenta de hosting (paso 1.5).

## 3. Armar el paquete en tu PC

En la carpeta del proyecto ejecuta (una sola línea, con tus datos reales):

```bash
C:/xampp/php/php.exe scripts/build_infinityfree.php --url=http://patita.infinityfreeapp.com --db-host=sqlXXX.infinityfree.com --db-name=if0_XXXXXXXX_patita --db-user=if0_XXXXXXXX --db-pass=CONTRASEÑA_DE_LA_CUENTA
```

- Usa `http://` en `--url` hasta activar el certificado SSL (paso 7); después
  vuelve a generar el paquete con `https://`.
- Se crea `dist/infinityfree/` con:
  - `htdocs/`: lo que vas a subir (sitio público + carpeta `_app` protegida).
  - `patita-htdocs.zip`: lo mismo comprimido.
  - `database.sql`: tablas y productos.
- La configuración de correo (Gmail) se copia de tu `.env` local.
- **No compartas `dist/`**: el archivo `htdocs/_app/.env` contiene las
  contraseñas de la base de datos y del correo. Git ya lo ignora.

## 4. Importar la base de datos

1. En **MySQL Databases** pulsa **Admin** junto a tu base. Se abre phpMyAdmin.
2. Verifica que a la izquierda esté seleccionada `if0_XXXXXXXX_patita`.
3. Pestaña **Importar** → **Seleccionar archivo** → elige
   `dist/infinityfree/database.sql` → **Continuar** (o **Importar**).
4. Debes ver 5 tablas: `users`, `products`, `orders`, `order_items`,
   `password_reset_tokens`. En `products` deben aparecer 48 filas.

> Si al importar aparece un error por tablas ya existentes, bórralas desde
> phpMyAdmin (marca las 5 tablas → *Eliminar*) e importa de nuevo.

## 5. Subir los archivos con FileZilla

1. Abre FileZilla y conéctate:
   - Servidor: `ftpupload.net`
   - Usuario: `if0_XXXXXXXX`
   - Contraseña: la de la cuenta de hosting
   - Puerto: `21`
2. Activa **Servidor → Forzar mostrar archivos ocultos**, para que se suban
   los archivos `.htaccess` y `.env`.
3. En el panel derecho (servidor) entra a la carpeta **htdocs**.
4. Borra los archivos de ejemplo que trae InfinityFree (por ejemplo
   `index2.html`).
5. En el panel izquierdo (tu PC) entra a
   `...\petstore\dist\infinityfree\htdocs`.
6. Selecciona **todo su contenido** (`_app`, `assets`, `uploads`, `index.php`,
   `.htaccess`) y arrástralo a `htdocs` del servidor. No subas la carpeta
   `htdocs` dentro de `htdocs`: debe quedar `htdocs/index.php`.
7. Espera a que la cola termine sin errores (unos 200 archivos).

## 6. Comprobar el sitio

1. Abre `http://patita.infinityfreeapp.com`. La primera vez InfinityFree puede
   mostrar una verificación del navegador; es normal.
2. Revisa la portada, el catálogo, un producto y "Herramientas".
3. **Seguridad**: abre `http://patita.infinityfreeapp.com/_app/.env`. Debe
   responder **403 Prohibido**. Si se descarga o muestra el archivo, detente y
   revisa que `htdocs/.htaccess` y `htdocs/_app/.htaccess` se hayan subido.

## 7. Activar HTTPS (recomendado)

1. En el área de cliente: **SSL Certificates** → **New SSL Certificate** →
   elige tu dominio y sigue el asistente (usa el certificado gratuito).
2. Cuando el certificado esté **Active** e instalado, comprueba que
   `https://patita.infinityfreeapp.com` abra bien.
3. Vuelve a generar el paquete con `--url=https://...` y sube solo
   `_app/.env`.
4. Opcional: para redirigir siempre a HTTPS, edita `htdocs/.htaccess` en el
   servidor y quita el `#` de estas dos líneas:
   ```
   # RewriteCond %{HTTP:X-Forwarded-Proto} !https
   # RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```
   Si después de eso la página entra en un bucle de redirecciones, vuelve a
   comentarlas.

## 8. Crear tu cuenta de administrador

1. En el sitio publicado, pulsa **Ingresar → Crea una** y regístrate con tu
   correo.
2. En phpMyAdmin, pestaña **SQL**, ejecuta (con tu correo):
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'tu@correo.com';
   ```
3. Cierra sesión e ingresa de nuevo: verás **Administración** en el menú.

## 9. Probar el correo

1. En **Administración** pulsa **Enviarme un correo de prueba**.
2. Si llega, la confirmación de pedidos y la recuperación de contraseña
   funcionan.
3. Si aparece un error de conexión, es probable que el hosting gratuito
   bloquee el puerto 587. Prueba con SSL en el puerto 465: en
   `htdocs/_app/.env` del servidor cambia
   ```
   SMTP_PORT=465
   SMTP_ENCRYPTION=ssl
   ```
   y vuelve a probar. Si tampoco funciona, el plan gratuito no permite
   enviar correo saliente: cambia a `MAIL_TRANSPORT=log` para que la tienda
   siga funcionando (los correos se guardarán en `_app/storage/mail`).

## 10. Actualizar el sitio más adelante

1. Haz los cambios en tu PC y vuelve a ejecutar el comando del paso 3.
2. Sube solo los archivos que cambiaron (normalmente `_app/app` y `assets`).
3. **No borres** `uploads/products` en el servidor: ahí están las fotos que
   subas desde el panel. FileZilla no las borra al sobrescribir.
4. Si cambias el esquema de la base de datos, aplica el cambio en phpMyAdmin;
   no vuelvas a importar `database.sql` completo (borraría pedidos y cuentas).

## Problemas frecuentes

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| Error 500 en todas las páginas | El servidor no acepta `Options -Indexes` | Borra esa línea de `htdocs/.htaccess` |
| Página "Algo salió mal" | Datos de la base incorrectos | Revisa `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `_app/.env` |
| Sin estilos (página en blanco y negro) | Archivos de `assets` sin subir | Sube de nuevo la carpeta `assets` |
| Rutas como `/catalogo` dan 404 del hosting | `.htaccess` no se subió | Activa "mostrar archivos ocultos" en FileZilla y súbelo |
| "La página expiró" al enviar formularios | Cookies bloqueadas o sesión vencida | Recarga la página; usa siempre el mismo dominio (con o sin `www`, http o https) |
| `/_app/.env` se descarga | `.htaccess` faltante | Súbelo de inmediato y cambia las contraseñas de la base y del correo |

## Límites del plan gratuito

InfinityFree es adecuado para mostrar el proyecto, no para vender en serio:
tiene límites de uso diario y de CPU, la primera visita pasa por una
verificación del navegador y no ofrece garantías de disponibilidad. Para
producción, usa un hosting de pago con PHP 8.2+, SSH y respaldos.
