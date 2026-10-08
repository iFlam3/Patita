-- =============================================================================
-- Patita — catálogo inicial de DEMOSTRACIÓN (48 productos: 4 por mascota, más estética y granja)
--
-- IMPORTANTE
-- * Todos estos productos tienen data_origin = 'demo'. NO fueron extraídos de
--   petstore.direct ni de petsmart.com y no se atribuyen a esas fuentes.
-- * Las marcas son ficticias, creadas para la demo. Cualquier coincidencia con
--   una marca real es accidental: reemplázalas antes de publicar.
-- * Precio (USD) y stock son valores de prueba, no información comercial
--   verificada. Las descripciones son genéricas y no contienen indicaciones
--   veterinarias ni beneficios de salud.
-- * Las imágenes son ilustraciones locales (public/assets/images/demo).
-- * No se crea ningún usuario: el administrador se crea con
--     php scripts/create_admin.php
-- =============================================================================

SET NAMES utf8mb4;

INSERT INTO products
    (sku, name, slug, brand, pet_type, category, description, price, currency, stock, image_url, data_origin, active, created_at, updated_at)
VALUES
-- Perros --------------------------------------------------------------------
('AV-DEMO-DOG-01', 'Alimento seco para perro adulto, 4 kg', 'alimento-seco-perro-adulto-4-kg', 'Patita Básicos', 'dog', 'food',
 'Bolsa resellable de 4 kg con croquetas para perro adulto. La composición, el análisis garantizado y la ración diaria no están disponibles en esta demo: consulta siempre la etiqueta del fabricante.',
 29.99, 'USD', 25, 'assets/images/demo/dog-food.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 1 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-DOG-02', 'Correa de nailon trenzado, 1.5 m', 'correa-nailon-trenzado-1-5-m', 'Patio Norte', 'dog', 'collars_leashes',
 'Correa de nailon trenzado de 1.5 m con mosquetón metálico y asa acolchada. Medidas de referencia para la demo.',
 12.99, 'USD', 40, 'assets/images/demo/dog-collars_leashes.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 2 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-DOG-03', 'Cama acolchada rectangular, talla M', 'cama-acolchada-rectangular-m', 'Patio Norte', 'dog', 'beds_habitats',
 'Cama rectangular de 70 × 55 cm con bordes elevados y funda removible. Medidas de referencia para la demo.',
 49.99, 'USD', 8, 'assets/images/demo/dog-beds_habitats.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 3 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-DOG-04', 'Pelota de caucho, 7 cm', 'pelota-caucho-7-cm', 'Patio Norte', 'dog', 'toys',
 'Pelota de caucho macizo de 7 cm de diámetro. Supervisa siempre el juego y retira el juguete si se daña.',
 7.99, 'USD', 0, 'assets/images/demo/dog-toys.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 4 MINUTE, UTC_TIMESTAMP()),

-- Gatos ---------------------------------------------------------------------
('AV-DEMO-CAT-01', 'Arenero cubierto con puerta abatible', 'arenero-cubierto-puerta-abatible', 'Patita Básicos', 'cat', 'grooming',
 'Arenero de plástico con tapa, puerta abatible y asa superior. Medidas aproximadas: 50 × 40 × 38 cm.',
 37.99, 'USD', 12, 'assets/images/demo/cat-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 5 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-CAT-02', 'Rascador de cartón corrugado', 'rascador-carton-corrugado', 'Patio Norte', 'cat', 'toys',
 'Plancha de cartón corrugado de 45 × 20 cm para rascar, con base de cartón reforzado.',
 9.99, 'USD', 30, 'assets/images/demo/cat-toys.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 6 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-CAT-03', 'Comedero doble de acero inoxidable', 'comedero-doble-acero-inoxidable', 'Patita Básicos', 'cat', 'bowls_feeders',
 'Base antideslizante con dos platos de acero inoxidable desmontables de 250 ml cada uno.',
 13.99, 'USD', 18, 'assets/images/demo/cat-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 7 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-CAT-04', 'Transportadora rígida mediana', 'transportadora-rigida-mediana', 'Patio Norte', 'cat', 'transport',
 'Transportadora de plástico rígido con puerta metálica, ventilación lateral y asa superior. Medidas aproximadas: 48 × 32 × 30 cm. Revisa el tamaño adecuado para tu mascota antes de comprar.',
 43.99, 'USD', 3, 'assets/images/demo/cat-transport.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 8 MINUTE, UTC_TIMESTAMP()),

-- Aves ----------------------------------------------------------------------
('AV-DEMO-BIRD-01', 'Mezcla de semillas para canario, 1 kg', 'mezcla-semillas-canario-1-kg', 'Pluma Serena', 'bird', 'food',
 'Bolsa de 1 kg con mezcla de semillas. La composición exacta no está disponible en esta demo: consulta la etiqueta del fabricante.',
 6.99, 'USD', 35, 'assets/images/demo/bird-food.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 9 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-BIRD-02', 'Jaula rectangular con dos perchas', 'jaula-rectangular-dos-perchas', 'Pluma Serena', 'bird', 'beds_habitats',
 'Jaula metálica de 46 × 30 × 50 cm con bandeja extraíble, dos perchas de madera y dos comederos.',
 71.99, 'USD', 5, 'assets/images/demo/bird-beds_habitats.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 10 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-BIRD-03', 'Bebedero tubular, 150 ml', 'bebedero-tubular-150-ml', 'Pluma Serena', 'bird', 'bowls_feeders',
 'Bebedero de plástico transparente de 150 ml con gancho para fijarlo a los barrotes de la jaula.',
 3.99, 'USD', 50, 'assets/images/demo/bird-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 11 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-BIRD-04', 'Columpio de madera con cuentas', 'columpio-madera-cuentas', 'Pluma Serena', 'bird', 'toys',
 'Columpio de madera natural de 15 cm con cuentas de colores y gancho metálico.',
 4.99, 'USD', 22, 'assets/images/demo/bird-toys.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 12 MINUTE, UTC_TIMESTAMP()),

-- Peces ---------------------------------------------------------------------
('AV-DEMO-FISH-01', 'Alimento en hojuelas para peces tropicales, 100 g', 'alimento-hojuelas-peces-tropicales-100-g', 'Agua Clara Lab', 'fish', 'food',
 'Frasco de 100 g de alimento en hojuelas. La composición y la cantidad por toma no están disponibles en esta demo: consulta la etiqueta del fabricante.',
 6.99, 'USD', 40, 'assets/images/demo/fish-food.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 13 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-FISH-02', 'Filtro interno para acuario de hasta 60 L', 'filtro-interno-acuario-60-l', 'Agua Clara Lab', 'fish', 'aquarium_terrarium',
 'Filtro sumergible con esponja lavable y ventosas de fijación. La capacidad es un valor de referencia para la demo: verifica la ficha del fabricante.',
 25.99, 'USD', 10, 'assets/images/demo/fish-aquarium_terrarium.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 14 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-FISH-03', 'Sifón manual para limpieza de grava', 'sifon-limpieza-grava', 'Agua Clara Lab', 'fish', 'grooming',
 'Sifón con pera de bombeo, tubo rígido de 30 cm y manguera de 1.5 m para cambios de agua.',
 9.99, 'USD', 15, 'assets/images/demo/fish-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 15 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-FISH-04', 'Acuario de vidrio de 20 L con tapa', 'acuario-vidrio-20-l-tapa', 'Agua Clara Lab', 'fish', 'aquarium_terrarium',
 'Acuario de vidrio de 40 × 25 × 25 cm con tapa de plástico. No incluye filtro, calentador ni iluminación.',
 63.99, 'USD', 4, 'assets/images/demo/fish-aquarium_terrarium.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 16 MINUTE, UTC_TIMESTAMP()),

-- Reptiles ------------------------------------------------------------------
('AV-DEMO-REP-01', 'Terrario de vidrio 60 × 30 × 30 cm', 'terrario-vidrio-60x30x30', 'Terrario Andino', 'reptile', 'aquarium_terrarium',
 'Terrario de vidrio con puertas frontales con cerradura y tapa de malla metálica. No incluye iluminación ni calefacción.',
 132.99, 'USD', 3, 'assets/images/demo/reptile-aquarium_terrarium.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 17 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-REP-02', 'Lámpara cerámica de calor, 60 W', 'lampara-ceramica-calor-60-w', 'Terrario Andino', 'reptile', 'aquarium_terrarium',
 'Emisor cerámico de 60 W con rosca E27. Requiere un portalámparas apto para cerámica. La temperatura adecuada depende de cada especie: consulta a un especialista.',
 18.99, 'USD', 15, 'assets/images/demo/reptile-aquarium_terrarium.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 18 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-REP-03', 'Escondite de resina tipo roca, mediano', 'escondite-resina-roca-mediano', 'Terrario Andino', 'reptile', 'beds_habitats',
 'Refugio de resina con acabado de roca, de 20 × 15 × 9 cm.',
 15.99, 'USD', 20, 'assets/images/demo/reptile-beds_habitats.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 19 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-REP-04', 'Plato de agua de resina, poco profundo', 'plato-agua-resina-poco-profundo', 'Terrario Andino', 'reptile', 'bowls_feeders',
 'Plato de resina de 14 × 11 cm y 2.5 cm de profundidad, con acabado de roca.',
 6.99, 'USD', 14, 'assets/images/demo/reptile-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 20 MINUTE, UTC_TIMESTAMP()),

-- Caballos ------------------------------------------------------------------
('AV-DEMO-HOR-01', 'Cepillo de cerdas duras para caballo', 'cepillo-cerdas-duras-caballo', 'Establo Sur', 'horse', 'equine_care',
 'Cepillo de 21 cm con cerdas sintéticas duras y correa de mano ajustable.',
 9.99, 'USD', 25, 'assets/images/demo/horse-equine_care.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 21 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-HOR-02', 'Limpiacascos con cepillo', 'limpiacascos-con-cepillo', 'Establo Sur', 'horse', 'equine_care',
 'Gancho metálico para cascos con cepillo de cerdas en el extremo y mango de plástico.',
 5.99, 'USD', 30, 'assets/images/demo/horse-equine_care.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 22 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-HOR-03', 'Cabezada de nailon ajustable, talla caballo', 'cabezada-nailon-ajustable', 'Establo Sur', 'horse', 'collars_leashes',
 'Cabezada de nailon con hebillas metálicas y ajuste en nuca y muserola. Verifica las medidas antes de comprar.',
 25.99, 'USD', 7, 'assets/images/demo/horse-collars_leashes.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 23 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-HOR-04', 'Cubo de plástico de 15 L con asa', 'cubo-plastico-15-l-asa', 'Establo Sur', 'horse', 'bowls_feeders',
 'Cubo de plástico de 15 L con asa metálica, para agua o alimento.',
 8.99, 'USD', 0, 'assets/images/demo/horse-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 24 MINUTE, UTC_TIMESTAMP()),

-- Animales pequeños (conejos, cuyos, hámsteres, chinchillas, hurones) --------
('AV-DEMO-SMA-01', 'Heno de pradera para conejos y cuyos, 1 kg', 'heno-pradera-conejos-cuyos-1-kg', 'Madriguera Co.', 'small_pet', 'food',
 'Bolsa de 1 kg de heno de pradera. Antes de cambiar la alimentación de tu mascota, consulta a un veterinario.',
 7.99, 'USD', 45, 'assets/images/demo/small_pet-food.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 25 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-SMA-02', 'Rueda de ejercicio de 21 cm para hámster', 'rueda-ejercicio-21-cm-hamster', 'Madriguera Co.', 'small_pet', 'toys',
 'Rueda de plástico de 21 cm de diámetro con base independiente y superficie de pisada cerrada.',
 12.99, 'USD', 12, 'assets/images/demo/small_pet-toys.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 26 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-SMA-03', 'Arena para baño de chinchilla, 2 kg', 'arena-bano-chinchilla-2-kg', 'Madriguera Co.', 'small_pet', 'grooming',
 'Bolsa de 2 kg de arena fina para baño de chinchilla. La granulometría exacta no está disponible en esta demo.',
 8.99, 'USD', 16, 'assets/images/demo/small_pet-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 27 MINUTE, UTC_TIMESTAMP()),
('AV-DEMO-SMA-04', 'Hamaca de tela para hurón', 'hamaca-tela-huron', 'Madriguera Co.', 'small_pet', 'beds_habitats',
 'Hamaca de tela de 45 × 45 cm con cuatro ganchos metálicos para colgar dentro de la jaula.',
 10.99, 'USD', 9, 'assets/images/demo/small_pet-beds_habitats.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 28 MINUTE, UTC_TIMESTAMP());

-- Estética canina y felina, y granja (también datos de demostración) ------
INSERT INTO products
    (sku, name, slug, brand, pet_type, category, description, price, currency, stock, image_url, data_origin, active, created_at, updated_at)
VALUES
('PAT-EST-DOG-01', 'Champú para perro de pelo corto, 500 ml', 'champu-perro-pelo-corto-500-ml', 'Patita Pro', 'dog', 'grooming',
 'Champú de uso externo en frasco de 500 ml. Sigue el modo de uso de la etiqueta y evita el contacto con los ojos. La fórmula exacta no está disponible en esta demo.',
 9.99, 'USD', 30, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 0 SECOND, UTC_TIMESTAMP()),
('PAT-EST-DOG-02', 'Acondicionador desenredante para perro, 500 ml', 'acondicionador-desenredante-perro-500-ml', 'Patita Pro', 'dog', 'grooming',
 'Acondicionador de uso externo en frasco de 500 ml para aplicar después del baño. Sigue el modo de uso de la etiqueta.',
 11.99, 'USD', 20, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 1 SECOND, UTC_TIMESTAMP()),
('PAT-EST-DOG-03', 'Cepillo carda autolimpiable', 'cepillo-carda-autolimpiable', 'Patita Pro', 'dog', 'grooming',
 'Cepillo de púas metálicas con botón que retira el pelo acumulado. Mango antideslizante.',
 12.99, 'USD', 25, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 2 SECOND, UTC_TIMESTAMP()),
('PAT-EST-DOG-04', 'Cortaúñas tipo tijera con tope de seguridad', 'cortaunas-tijera-tope-seguridad', 'Patita Pro', 'dog', 'grooming',
 'Cortaúñas de acero inoxidable con tope ajustable y lima incluida. Si no tienes experiencia, pide a un profesional que te muestre cómo cortar.',
 8.99, 'USD', 40, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 3 SECOND, UTC_TIMESTAMP()),
('PAT-EST-DOG-05', 'Máquina cortapelo inalámbrica con 4 peines guía', 'maquina-cortapelo-inalambrica-4-peines', 'Patita Pro', 'dog', 'grooming',
 'Cortapelo recargable para peluquería canina, con cuchilla desmontable, cuatro peines guía de 3 a 12 mm y base de carga.',
 49.99, 'USD', 6, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 4 SECOND, UTC_TIMESTAMP()),
('PAT-EST-DOG-06', 'Tijeras curvas de peluquería, 18 cm', 'tijeras-curvas-peluqueria-18-cm', 'Patita Pro', 'dog', 'grooming',
 'Tijeras de acero inoxidable de 18 cm con hoja curva para acabados y apoyadedos removible.',
 24.99, 'USD', 10, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 5 SECOND, UTC_TIMESTAMP()),
('PAT-EST-DOG-07', 'Mesa de peluquería plegable con brazo', 'mesa-peluqueria-plegable-brazo', 'Patita Pro', 'dog', 'grooming',
 'Mesa plegable de 90 × 60 cm con superficie de goma antideslizante, brazo de altura ajustable y lazo de sujeción. Nunca dejes a tu mascota sola sobre la mesa.',
 129.99, 'USD', 3, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 6 SECOND, UTC_TIMESTAMP()),
('PAT-EST-DOG-08', 'Secador soplador para mascotas de dos velocidades', 'secador-soplador-mascotas-dos-velocidades', 'Patita Pro', 'dog', 'grooming',
 'Secador de 2 velocidades y 2 temperaturas con manguera flexible y tres boquillas. Mantén siempre una distancia segura de la piel.',
 89.99, 'USD', 4, 'assets/images/demo/dog-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 7 SECOND, UTC_TIMESTAMP()),
('PAT-EST-CAT-01', 'Cepillo deslanador para gato', 'cepillo-deslanador-gato', 'Patita Pro', 'cat', 'grooming',
 'Cepillo con hoja dentada protegida para retirar pelo suelto de la capa interna. Mango ergonómico.',
 14.99, 'USD', 18, 'assets/images/demo/cat-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 8 SECOND, UTC_TIMESTAMP()),
('PAT-EST-CAT-02', 'Guante de aseo de silicona', 'guante-aseo-silicona', 'Patita Pro', 'cat', 'grooming',
 'Guante con puntas de silicona para cepillar mientras acaricias. Talla única con cierre ajustable.',
 7.99, 'USD', 35, 'assets/images/demo/cat-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 9 SECOND, UTC_TIMESTAMP()),
('PAT-EST-CAT-03', 'Champú en seco en espuma para gato, 200 ml', 'champu-seco-espuma-gato-200-ml', 'Patita Pro', 'cat', 'grooming',
 'Espuma de uso externo en envase de 200 ml que no requiere enjuague. Sigue el modo de uso de la etiqueta y evita los ojos.',
 10.99, 'USD', 15, 'assets/images/demo/cat-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 10 SECOND, UTC_TIMESTAMP()),
('PAT-EST-CAT-04', 'Cortaúñas pequeño para gato', 'cortaunas-pequeno-gato', 'Patita Pro', 'cat', 'grooming',
 'Cortaúñas de hoja semicircular de acero inoxidable, del tamaño adecuado para uñas pequeñas.',
 6.99, 'USD', 30, 'assets/images/demo/cat-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 11 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-01', 'Alimento para gallinas ponedoras, 20 kg', 'alimento-gallinas-ponedoras-20-kg', 'Granja Viva', 'farm', 'food',
 'Saco de 20 kg de alimento para gallinas ponedoras. La composición y la ración diaria no están disponibles en esta demo: consulta la etiqueta del fabricante.',
 27.99, 'USD', 12, 'assets/images/demo/farm-food.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 12 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-02', 'Comedero de tolva para aves de corral, 5 kg', 'comedero-tolva-aves-corral-5-kg', 'Granja Viva', 'farm', 'bowls_feeders',
 'Comedero de plástico con tolva de 5 kg, plato con rejilla y asa para colgar.',
 15.99, 'USD', 20, 'assets/images/demo/farm-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 13 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-03', 'Bebedero de sifón para aves de corral, 5 L', 'bebedero-sifon-aves-corral-5-l', 'Granja Viva', 'farm', 'bowls_feeders',
 'Bebedero de dos piezas con depósito de 5 L y base de llenado por gravedad.',
 11.99, 'USD', 22, 'assets/images/demo/farm-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 14 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-04', 'Nidal ponedero de plástico', 'nidal-ponedero-plastico', 'Granja Viva', 'farm', 'beds_habitats',
 'Nidal de plástico lavable de 35 × 30 × 35 cm con techo inclinado y bandeja extraíble.',
 19.99, 'USD', 10, 'assets/images/demo/farm-beds_habitats.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 15 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-05', 'Comedero de canal para cerdos, 1 m', 'comedero-canal-cerdos-1-m', 'Granja Viva', 'farm', 'bowls_feeders',
 'Comedero de plástico resistente de 1 m de largo con bordes reforzados y patas de apoyo.',
 34.99, 'USD', 5, 'assets/images/demo/farm-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 16 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-06', 'Cepillo de cerdas para ganado', 'cepillo-cerdas-ganado', 'Granja Viva', 'farm', 'grooming',
 'Cepillo de cerdas sintéticas duras de 22 cm con correa para la mano, para cabras, ovejas y ganado.',
 9.99, 'USD', 14, 'assets/images/demo/farm-grooming.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 17 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-07', 'Bloque de sal para ganado, 2 kg', 'bloque-sal-ganado-2-kg', 'Granja Viva', 'farm', 'food',
 'Bloque de sal compactada de 2 kg con orificio para colgar. Consulta a un veterinario sobre la suplementación de tu ganado.',
 6.99, 'USD', 25, 'assets/images/demo/farm-food.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 18 SECOND, UTC_TIMESTAMP()),
('PAT-GRA-08', 'Red de heno para cabras y ovejas', 'red-heno-cabras-ovejas', 'Granja Viva', 'farm', 'bowls_feeders',
 'Red de nailon con cordón de cierre para ofrecer heno en el corral. Capacidad aproximada de 5 kg.',
 13.99, 'USD', 9, 'assets/images/demo/farm-bowls_feeders.svg', 'demo', 1, UTC_TIMESTAMP() - INTERVAL 19 SECOND, UTC_TIMESTAMP());
