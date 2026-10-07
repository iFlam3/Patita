<?php

declare(strict_types=1);

/*
 * Listas controladas de la aplicación. No existen tablas para mascotas,
 * categorías, roles ni estados: estos arreglos son la única fuente de verdad
 * y products.pet_type / products.category se validan contra ellos.
 *
 * Las claves (dog, food, ...) se guardan en la base de datos; los slugs se
 * usan en las URL y las etiquetas en la interfaz.
 */
return [
    'pets' => [
        'dog' => [
            'label' => 'Perros',
            'slug'  => 'perros',
            'icon'  => 'dog',
            'intro' => 'Alimento, paseo, descanso y juego para tu perro.',
        ],
        'cat' => [
            'label' => 'Gatos',
            'slug'  => 'gatos',
            'icon'  => 'cat',
            'intro' => 'Areneros, rascadores, comederos y transporte para tu gato.',
        ],
        'bird' => [
            'label' => 'Aves',
            'slug'  => 'aves',
            'icon'  => 'bird',
            'intro' => 'Jaulas, perchas, comederos y semillas para aves de compañía.',
        ],
        'fish' => [
            'label' => 'Peces',
            'slug'  => 'peces',
            'icon'  => 'fish',
            'intro' => 'Acuarios, filtración, limpieza y alimento para peces.',
        ],
        'reptile' => [
            'label' => 'Reptiles',
            'slug'  => 'reptiles',
            'icon'  => 'turtle',
            'intro' => 'Terrarios, calor, refugios y bebederos para reptiles.',
        ],
        'horse' => [
            'label' => 'Caballos',
            'slug'  => 'caballos',
            'icon'  => 'horse',
            'intro' => 'Cepillos, cascos, cabezadas y cubos para el cuidado equino.',
        ],
        'small_pet' => [
            'label' => 'Animales pequeños',
            'slug'  => 'animales-pequenos',
            'icon'  => 'rabbit',
            'intro' => 'Heno, ruedas, baños de arena y hamacas para conejos, cuyos, hámsteres, chinchillas y hurones.',
            // Término de búsqueda => etiqueta visible. Se buscan en nombre y descripción.
            'subtypes' => [
                'conejo'     => 'Conejos',
                'cuyo'       => 'Cobayas o cuyos',
                'hámster'    => 'Hámsteres',
                'chinchilla' => 'Chinchillas',
                'hurón'      => 'Hurones',
            ],
        ],
    ],

    'categories' => [
        'food'               => ['label' => 'Alimento y alimentación',      'slug' => 'alimento',             'icon' => 'wheat'],
        'toys'               => ['label' => 'Juguetes y enriquecimiento',   'slug' => 'juguetes',             'icon' => 'volleyball'],
        'grooming'           => ['label' => 'Higiene y peluquería',         'slug' => 'higiene',              'icon' => 'scissors'],
        'beds_habitats'      => ['label' => 'Camas, refugios y hábitats',   'slug' => 'camas-y-habitats',     'icon' => 'house'],
        'collars_leashes'    => ['label' => 'Collares, correas y arneses',  'slug' => 'collares-y-correas',   'icon' => 'link-2'],
        'bowls_feeders'      => ['label' => 'Comederos y bebederos',        'slug' => 'comederos',            'icon' => 'dog-bowl'],
        'transport'          => ['label' => 'Transporte',                   'slug' => 'transporte',           'icon' => 'briefcase'],
        'aquarium_terrarium' => ['label' => 'Acuarios y terrarios',         'slug' => 'acuarios-y-terrarios', 'icon' => 'waves'],
        'equine_care'        => ['label' => 'Cuidado equino',               'slug' => 'cuidado-equino',       'icon' => 'horseshoe'],
    ],

    /*
     * Módulos de información por mascota (página de cada animal).
     * Son orientaciones generales de compra, no indicaciones veterinarias:
     * cada módulo enlaza a una categoría y la vista agrega un aviso para
     * consultar a un veterinario ante dudas de salud o alimentación.
     */
    'pet_guides' => [
        'dog' => [
            ['title' => 'Paseo', 'category' => 'collars_leashes',
             'text' => 'Mide el cuello o el pecho de tu perro antes de elegir collar o arnés. Revisa costuras, hebillas y mosquetón con frecuencia.'],
            ['title' => 'Descanso', 'category' => 'beds_habitats',
             'text' => 'Elige la cama según el largo de tu perro echado, con espacio para estirarse. Una funda removible facilita el lavado.'],
            ['title' => 'Juego', 'category' => 'toys',
             'text' => 'Escoge juguetes de un tamaño que no pueda tragarse, supervisa el juego y retíralos cuando se rompan.'],
        ],
        'cat' => [
            ['title' => 'Arenero', 'category' => 'grooming',
             'text' => 'Ubícalo en un lugar tranquilo y lejos del comedero. Retira los desechos a diario y cambia la arena con regularidad.'],
            ['title' => 'Rascado y juego', 'category' => 'toys',
             'text' => 'Un rascador estable cerca de donde descansa le da un lugar propio para rascar. Rota los juguetes para mantener su interés.'],
            ['title' => 'Viajes', 'category' => 'transport',
             'text' => 'Una transportadora rígida y ventilada facilita traslados y visitas al veterinario. Déjala abierta en casa para que la explore antes.'],
        ],
        'bird' => [
            ['title' => 'Jaula', 'category' => 'beds_habitats',
             'text' => 'Debe permitir que el ave extienda las alas sin tocar los barrotes. Verifica que la separación entre barrotes corresponda a su tamaño.'],
            ['title' => 'Agua y comida', 'category' => 'bowls_feeders',
             'text' => 'Lava comederos y bebederos con frecuencia y cambia el agua todos los días.'],
            ['title' => 'Enriquecimiento', 'category' => 'toys',
             'text' => 'Columpios, perchas y juguetes de distintos materiales le dan variedad. Rótalos cada cierto tiempo.'],
        ],
        'fish' => [
            ['title' => 'Acuario', 'category' => 'aquarium_terrarium',
             'text' => 'El tamaño del acuario, el filtro y la temperatura dependen de la especie. Infórmate sobre tus peces antes de equiparlo.'],
            ['title' => 'Mantenimiento', 'category' => 'grooming',
             'text' => 'Los cambios parciales de agua y la limpieza de la grava son parte de la rutina. Un sifón manual simplifica la tarea.'],
            ['title' => 'Alimento', 'category' => 'food',
             'text' => 'Elige el tipo de alimento según la especie y sigue las cantidades que indica la etiqueta del fabricante.'],
        ],
        'reptile' => [
            ['title' => 'Terrario', 'category' => 'aquarium_terrarium',
             'text' => 'Cada especie necesita un rango propio de temperatura y humedad. Investiga el de tu reptil antes de elegir terrario, lámparas y sustrato.'],
            ['title' => 'Refugio', 'category' => 'beds_habitats',
             'text' => 'Coloca al menos un escondite dentro del terrario para que tu reptil pueda resguardarse.'],
            ['title' => 'Agua', 'category' => 'bowls_feeders',
             'text' => 'Un plato poco profundo y estable evita derrames. Límpialo y renueva el agua con regularidad.'],
        ],
        'horse' => [
            ['title' => 'Cepillado y cascos', 'category' => 'equine_care',
             'text' => 'Un juego básico incluye cepillo de cerdas duras y limpiacascos. Revisa los cascos antes y después de montar.'],
            ['title' => 'Equipo', 'category' => 'collars_leashes',
             'text' => 'La cabezada debe ajustarse sin apretar. Revisa hebillas, costuras y desgaste antes de cada uso.'],
            ['title' => 'Agua y alimento', 'category' => 'bowls_feeders',
             'text' => 'Mantén cubos y bebederos limpios, con agua fresca siempre disponible.'],
        ],
        'small_pet' => [
            ['title' => 'Alimentación', 'category' => 'food',
             'text' => 'Conejos y cuyos suelen consumir heno a diario. La dieta cambia según la especie: confírmala con un veterinario.'],
            ['title' => 'Hábitat', 'category' => 'beds_habitats',
             'text' => 'Hámsteres, chinchillas, hurones y conejos necesitan espacios distintos. Elige jaula y accesorios según la especie y su tamaño adulto.'],
            ['title' => 'Higiene', 'category' => 'grooming',
             'text' => 'Las chinchillas se bañan en arena específica para ellas; no la sustituyas por arena común ni por agua.'],
        ],
    ],

    'roles' => [
        'customer' => 'Cliente',
        'admin'    => 'Administrador',
    ],

    'data_origins' => [
        'scraped' => 'Extraído de una fuente externa',
        'demo'    => 'Dato de demostración',
        'manual'  => 'Ingresado manualmente',
    ],

    // Estados de pedido. "final" = no admite más cambios.
    'order_statuses' => [
        'received'  => ['label' => 'Recibido',        'final' => false],
        'paid'      => ['label' => 'Pago confirmado', 'final' => false],
        'preparing' => ['label' => 'En preparación',  'final' => false],
        'shipped'   => ['label' => 'Enviado',         'final' => false],
        'delivered' => ['label' => 'Entregado',       'final' => true],
        'cancelled' => ['label' => 'Cancelado',       'final' => true],
    ],

    // Modalidades demostrativas: no se procesa ningún cobro ni se guardan tarjetas.
    'payment_methods' => [
        'bank_transfer' => [
            'label'       => 'Transferencia bancaria',
            'description' => 'Al confirmar verás los datos para transferir. Usa tu número de pedido como referencia.',
        ],
        'cash_on_delivery' => [
            'label'       => 'Pago contra entrega',
            'description' => 'Pagas cuando recibes tu pedido. Ten a mano el importe exacto.',
        ],
    ],

    // Países de Latinoamérica para formularios y configuración.
    'countries' => [
        'AR' => 'Argentina',
        'BO' => 'Bolivia',
        'BR' => 'Brasil',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'CR' => 'Costa Rica',
        'CU' => 'Cuba',
        'DO' => 'República Dominicana',
        'EC' => 'Ecuador',
        'SV' => 'El Salvador',
        'GT' => 'Guatemala',
        'HN' => 'Honduras',
        'MX' => 'México',
        'NI' => 'Nicaragua',
        'PA' => 'Panamá',
        'PY' => 'Paraguay',
        'PE' => 'Perú',
        'PR' => 'Puerto Rico',
        'UY' => 'Uruguay',
        'VE' => 'Venezuela',
    ],

    // Decimales usados por el formateo de respaldo en el servidor
    // (en el navegador manda Intl.NumberFormat).
    'currency_decimals' => [
        'CLP' => 0,
        'PYG' => 0,
    ],
];
