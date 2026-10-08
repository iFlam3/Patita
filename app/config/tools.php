<?php

declare(strict_types=1);

/*
 * Datos de las herramientas interactivas.
 *
 * Alimentos: basados en las listas públicas de alimentos peligrosos para
 * mascotas de la ASPCA (Animal Poison Control Center) y fuentes veterinarias
 * de divulgación. Niveles:
 *   toxic  = tóxico para la especie.
 *   avoid  = no recomendado (malestar o riesgo físico).
 *   nodata = no hay un dato verificado para esa especie en estas fuentes.
 * Las mascotas que no aparecen en un alimento se tratan como "nodata".
 */
return [
    'foods' => [
        'chocolate' => [
            'label' => 'Chocolate y cacao',
            'note'  => 'Contiene teobromina y cafeína. El chocolate oscuro, el de repostería y el cacao en polvo son los más concentrados.',
            'levels' => ['dog' => 'toxic', 'cat' => 'toxic', 'bird' => 'toxic', 'horse' => 'toxic', 'small_pet' => 'toxic', 'farm' => 'toxic'],
        ],
        'uvas' => [
            'label' => 'Uvas y pasas',
            'note'  => 'En perros se han asociado a daño renal, incluso en cantidades pequeñas.',
            'levels' => ['dog' => 'toxic', 'cat' => 'toxic'],
        ],
        'cebolla' => [
            'label' => 'Cebolla, ajo, puerro y cebollín',
            'note'  => 'Crudos, cocidos o en polvo pueden dañar los glóbulos rojos. Revisa caldos, salsas y condimentos.',
            'levels' => ['dog' => 'toxic', 'cat' => 'toxic', 'horse' => 'toxic', 'bird' => 'toxic', 'small_pet' => 'toxic', 'farm' => 'toxic'],
        ],
        'xilitol' => [
            'label' => 'Xilitol (edulcorante)',
            'note'  => 'Está en chicles, dulces sin azúcar y algunas mantequillas de maní. En perros puede provocar una caída brusca de azúcar en sangre.',
            'levels' => ['dog' => 'toxic', 'cat' => 'avoid'],
        ],
        'aguacate' => [
            'label' => 'Aguacate',
            'note'  => 'Contiene persina. En aves, conejos, cuyos, caballos, cabras y ovejas se considera especialmente peligroso.',
            'levels' => ['bird' => 'toxic', 'horse' => 'toxic', 'small_pet' => 'toxic', 'farm' => 'toxic', 'dog' => 'avoid', 'cat' => 'avoid'],
        ],
        'alcohol' => [
            'label' => 'Bebidas alcohólicas',
            'note'  => 'Incluye alimentos preparados con alcohol. Incluso cantidades pequeñas afectan a animales de poco peso.',
            'levels' => ['dog' => 'toxic', 'cat' => 'toxic', 'bird' => 'toxic', 'reptile' => 'toxic', 'horse' => 'toxic', 'small_pet' => 'toxic', 'farm' => 'toxic'],
        ],
        'cafeina' => [
            'label' => 'Café, té y bebidas energéticas',
            'note'  => 'La cafeína es un estimulante que afecta el corazón y el sistema nervioso.',
            'levels' => ['dog' => 'toxic', 'cat' => 'toxic', 'bird' => 'toxic', 'horse' => 'toxic', 'small_pet' => 'toxic', 'farm' => 'toxic'],
        ],
        'macadamia' => [
            'label' => 'Nueces de macadamia',
            'note'  => 'En perros se asocian a debilidad, temblores y fiebre.',
            'levels' => ['dog' => 'toxic'],
        ],
        'masa' => [
            'label' => 'Masa cruda con levadura',
            'note'  => 'Puede expandirse en el estómago y producir alcohol al fermentar.',
            'levels' => ['dog' => 'toxic', 'cat' => 'toxic'],
        ],
        'lacteos' => [
            'label' => 'Leche y lácteos',
            'note'  => 'Muchos perros y gatos adultos digieren mal la lactosa y pueden tener malestar digestivo.',
            'levels' => ['dog' => 'avoid', 'cat' => 'avoid'],
        ],
        'huesos' => [
            'label' => 'Huesos cocidos',
            'note'  => 'Pueden astillarse y causar atragantamiento u obstrucciones.',
            'levels' => ['dog' => 'avoid', 'cat' => 'avoid'],
        ],
    ],

    'levels' => [
        'toxic'  => ['label' => 'Tóxico para esta especie', 'short' => 'Tóxico', 'class' => 'danger'],
        'avoid'  => ['label' => 'No recomendado', 'short' => 'No recomendado', 'class' => 'warn'],
        'nodata' => ['label' => 'Sin información verificada', 'short' => 'Sin información', 'class' => 'neutral'],
    ],

    // Edad: años humanos por año de mascota a partir del tercer año.
    // Estimación orientativa basada en tablas de equivalencia de uso común.
    'dog_sizes' => [
        'pequeno' => ['label' => 'Pequeño (hasta 9 kg)', 'rate' => 4],
        'mediano' => ['label' => 'Mediano (10 a 22 kg)', 'rate' => 5],
        'grande'  => ['label' => 'Grande (23 a 40 kg)', 'rate' => 6],
        'gigante' => ['label' => 'Gigante (más de 40 kg)', 'rate' => 7],
    ],

    // Condición corporal en escala de 9 puntos, agrupada en 5 descripciones.
    'body_conditions' => [
        2 => ['label' => 'Muy delgado', 'text' => 'Costillas, columna y huesos de la cadera visibles a simple vista; no se palpa grasa.'],
        3 => ['label' => 'Delgado', 'text' => 'Costillas fáciles de palpar y algo visibles; cintura muy marcada.'],
        5 => ['label' => 'Ideal', 'text' => 'Costillas fáciles de palpar con una capa fina de grasa; cintura visible desde arriba y abdomen recogido de lado.'],
        7 => ['label' => 'Sobrepeso', 'text' => 'Cuesta palpar las costillas; cintura poco visible; abdomen redondeado.'],
        9 => ['label' => 'Obesidad', 'text' => 'No se palpan las costillas; sin cintura; abdomen abultado y depósitos de grasa visibles.'],
    ],
];
