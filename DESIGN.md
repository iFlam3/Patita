# Patita · sistema de diseño

Dirección "Mosaico": tienda para mascotas cercana y con energía. Fondo claro,
fotos de estudio con fondos de color, un solo color de acción (ámbar) y verdes
de apoyo. Los tokens viven en `:root` de `public/assets/css/app.css`; las
vistas usan nombres, nunca valores sueltos.

## Color

| Token | Valor | Rol |
|---|---|---|
| `--snow` | `#F6F6F6` | Fondo de página y del navbar |
| `--white` | `#FFFFFF` | Tarjetas, píldora del navbar, campos |
| `--carbon` | `#231F20` | Texto principal, botón secundario (borde) |
| `--carbon-soft` | `#4A4546` | Texto secundario (8.7:1 sobre nieve) |
| `--amber` | `#FF9F1C` | **Acción principal**: botones, carrito. Texto encima siempre carbón (7.9:1) |
| `--amber-deep` | `#EE8C00` | Hover del botón principal |
| `--amber-soft` | `#FFAA33` | Fondos de acento (íconos de beneficios) |
| `--tea` | `#C5EBBF` | Marcos y superficies verdes (mosaico, pie, conteos) |
| `--tea-wash` | `#EAF7E7` | Banda de "Productos destacados" |
| `--lime-light` | `#94DF88` | Bordes y hover suaves |
| `--lime` | `#31C71A` | Solo formas y líneas; **nunca texto** (2.1:1) |
| `--lime-ink` | `#1A6B0F` | Texto y enlaces verdes (6.2:1 nieve, 5.1:1 té) |
| `--primary` | `#1A6B0F` | Botón verde de páginas internas (blanco encima 6.6:1) |

Semánticos sin cambio: error `--red-ink #C40014`, aviso usa ámbar.
Sin modo oscuro.

## Tipografía

- **Gabarito** (títulos, 700–800): geométrica redondeada, amable y legible en grande.
- **Onest** (texto e interfaz, 400–700): diseñada para pantalla; clara en teléfonos.
- Escala: hero `clamp(2.25rem → 4.25rem)`, h2 `clamp(1.5rem → 2.125rem)`,
  h3 `1.0625rem`, cuerpo `1rem/1.6`, pequeño `0.875rem`, mínimo `0.75rem`.
- Títulos con `letter-spacing: -0.02em` y `text-wrap: balance`. Líneas de texto ≤ 60ch.

## Espaciado y forma

- Base 4 px. Gutter lateral 16 px. Ancho máximo 1200 px.
- Secciones: 48 px (móvil) / 72 px (escritorio).
- Radios: 10 px (campos), 14 px (tarjetas), 22 px (fotos), 28 px (marcos), píldora.
- Una sombra: `--shadow-pop` (paneles flotantes y navbar). Las tarjetas usan borde, no sombra.

## Componentes

- **Navbar**: píldora blanca fija arriba sobre nieve. Logo · enlaces · buscador · cuenta · carrito ámbar.
  En móvil: logo, carrito y menú (`details`), buscador en una segunda fila.
- **Botones**: principal ámbar con texto carbón; secundario contorno carbón; ambos en píldora, 48 px de alto.
- **Hero**: pantalla completa. Escritorio: texto a la izquierda, mosaico 2×2 en marco té a la derecha.
  Móvil: el mosaico pasa al fondo con degradado nieve para que el texto se lea.
- **Tarjeta de mascota**: foto propia 4:5 con etiqueta blanca en píldora.
- **Tarjeta de producto**: estructura original; solo cambian colores (botón ámbar, mascota en lima-ink).
- **Pie**: bloque té redondeado arriba, columnas de enlaces, texto carbón.

## Movimiento

- Título por palabras (`data-split`), aparición al deslizar (`data-reveal`, `data-delay`).
- Duraciones 0.25 / 0.6 / 0.9 s, curva `--ease-out`. Con "movimiento reducido" todo queda quieto y visible.

## Imágenes e íconos

- Íconos: sprite propio `icons.svg` (Lucide / Tabler), trazo 2 px.
- Hero: fotos de estudio de Unsplash (licencia Unsplash, uso comercial permitido) en
  `assets/images/hero/`, a 480 y 900 px. Mascotas: fotos propias en `assets/images/photos/`.
- Evitar: degradados morados, texto con degradado, emojis como íconos, todo centrado,
  tarjetas idénticas con sombra en todas partes.
