<?php

declare(strict_types=1);

/*
 * Guías de cuidado por mascota (páginas /cuidados/{mascota}).
 *
 * Contenido general y ampliamente aceptado, sin dosis, tratamientos ni
 * diagnósticos. Cada página recuerda que la referencia es el veterinario.
 */
return [
    'dog' => [
        'summary' => 'Un perro necesita rutina: comida a horas fijas, agua siempre disponible, paseos diarios y visitas periódicas al veterinario.',
        'sections' => [
            ['icon' => 'wheat', 'title' => 'Alimentación e hidratación', 'items' => [
                'Elige un alimento completo para su etapa de vida (cachorro, adulto o senior) y su tamaño.',
                'Respeta la ración de la etiqueta y ajústala con tu veterinario según su peso y actividad.',
                'Deja agua fresca y limpia todo el día; lava el bebedero a diario.',
                'Evita darle restos de comida humana: algunos alimentos comunes son tóxicos para perros.',
            ]],
            ['icon' => 'scissors', 'title' => 'Higiene y aseo', 'items' => [
                'Cepíllalo según su tipo de pelo: los de pelo largo necesitan cepillado frecuente.',
                'Báñalo solo cuando haga falta y con productos para perros.',
                'Revisa uñas, orejas y dientes de forma regular.',
            ]],
            ['icon' => 'volleyball', 'title' => 'Ejercicio y enriquecimiento', 'items' => [
                'Sácalo a pasear todos los días; la duración depende de su edad, raza y salud.',
                'Juega con él y enséñale órdenes básicas con refuerzo positivo.',
                'Rota juguetes y ofrécele juguetes de olfato o de premio para estimularlo.',
            ]],
            ['icon' => 'house', 'title' => 'Hogar y seguridad', 'items' => [
                'Dale una cama propia en un lugar tranquilo y sin corrientes de aire.',
                'Usa collar o arnés con placa de identificación y considera el microchip.',
                'Guarda fuera de su alcance medicamentos, productos de limpieza y cables.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud preventiva', 'items' => [
                'Lleva un calendario de vacunas y desparasitación indicado por tu veterinario.',
                'Programa al menos un chequeo anual, o más seguido si es cachorro o senior.',
                'Consulta pronto si notas que deja de comer, vomita varias veces, respira con dificultad o está decaído.',
            ]],
        ],
    ],

    'cat' => [
        'summary' => 'Los gatos valoran la tranquilidad y la limpieza: arenero cuidado, lugares altos donde descansar y juego diario.',
        'sections' => [
            ['icon' => 'wheat', 'title' => 'Alimentación e hidratación', 'items' => [
                'Ofrécele alimento completo para gatos según su etapa de vida; no lo alimentes con comida para perros.',
                'Muchos gatos beben poco: las fuentes de agua o el alimento húmedo pueden ayudar a que tomen más.',
                'Separa el plato de agua del de comida y ambos del arenero.',
            ]],
            ['icon' => 'scissors', 'title' => 'Higiene y arenero', 'items' => [
                'Retira los desechos del arenero cada día y cambia la arena por completo con regularidad.',
                'Ten un arenero por gato y, si puedes, uno adicional.',
                'Cepíllalo con frecuencia, sobre todo si tiene pelo largo, para reducir bolas de pelo.',
            ]],
            ['icon' => 'volleyball', 'title' => 'Juego y enriquecimiento', 'items' => [
                'Juega con él a diario con cañas o juguetes que imiten presas.',
                'Coloca rascadores estables y superficies altas para trepar y observar.',
                'Dale escondites: cajas o camas cubiertas donde se sienta seguro.',
            ]],
            ['icon' => 'house', 'title' => 'Hogar y seguridad', 'items' => [
                'Instala mallas en ventanas y balcones.',
                'Algunas plantas de interior son tóxicas para gatos; revísalas antes de tenerlas en casa.',
                'Usa una transportadora rígida para cualquier traslado.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud preventiva', 'items' => [
                'Sigue el plan de vacunas y desparasitación de tu veterinario, aunque viva dentro de casa.',
                'Los gatos disimulan el malestar: un chequeo anual ayuda a detectar problemas a tiempo.',
                'Consulta pronto si no come, deja de usar el arenero o hace esfuerzos para orinar.',
            ]],
        ],
    ],

    'bird' => [
        'summary' => 'Las aves de compañía necesitan espacio para moverse, una dieta variada adecuada a su especie y mucha interacción.',
        'sections' => [
            ['icon' => 'wheat', 'title' => 'Alimentación e hidratación', 'items' => [
                'La dieta depende de la especie: infórmate sobre la tuya antes de elegir semillas, pellets o frutas.',
                'Cambia el agua todos los días y lava comederos y bebederos.',
                'No le des aguacate, chocolate, cafeína ni alcohol.',
            ]],
            ['icon' => 'house', 'title' => 'Jaula y entorno', 'items' => [
                'La jaula debe permitirle extender las alas y moverse entre perchas.',
                'Ubícala lejos de la cocina, corrientes de aire y sol directo todo el día.',
                'Los humos de sartenes antiadherentes sobrecalentadas, aerosoles y velas aromáticas pueden dañar a las aves.',
            ]],
            ['icon' => 'volleyball', 'title' => 'Enriquecimiento', 'items' => [
                'Ofrece perchas de distintos grosores y materiales.',
                'Rota juguetes y dedica tiempo diario a interactuar con ella.',
                'Si sale de la jaula, supervísala y cierra puertas y ventanas.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud preventiva', 'items' => [
                'Busca un veterinario con experiencia en aves para un chequeo inicial.',
                'Consulta pronto si se queda con las plumas erizadas, deja de comer o cambia sus heces.',
            ]],
        ],
    ],

    'fish' => [
        'summary' => 'Cuidar peces es cuidar el agua: tamaño adecuado del acuario, filtración, temperatura estable y mantenimiento constante.',
        'sections' => [
            ['icon' => 'waves', 'title' => 'Acuario y agua', 'items' => [
                'Elige el tamaño del acuario según las especies y la cantidad de peces adultos.',
                'Cicla el acuario (establece el filtro biológico) antes de introducir peces.',
                'Usa acondicionador para el agua del grifo y mide los parámetros con un kit de pruebas.',
            ]],
            ['icon' => 'scissors', 'title' => 'Mantenimiento', 'items' => [
                'Haz cambios parciales de agua de forma regular en lugar de vaciar todo el acuario.',
                'Limpia la grava con sifón y enjuaga el filtro con agua del propio acuario.',
            ]],
            ['icon' => 'wheat', 'title' => 'Alimentación', 'items' => [
                'Usa alimento formulado para tu especie y da solo lo que coman en pocos minutos.',
                'El exceso de comida ensucia el agua.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud', 'items' => [
                'Investiga la compatibilidad entre especies antes de mezclarlas.',
                'Pon en cuarentena a los peces nuevos cuando sea posible.',
                'Consulta con un especialista si ves manchas, aletas dañadas o nado errático.',
            ]],
        ],
    ],

    'reptile' => [
        'summary' => 'Los reptiles dependen de su entorno para regular su temperatura: el terrario correcto es la base de su salud.',
        'sections' => [
            ['icon' => 'waves', 'title' => 'Terrario', 'items' => [
                'Cada especie necesita rangos propios de temperatura, humedad e iluminación; investígalos antes de comprar.',
                'Crea una zona cálida y otra más fresca para que pueda regularse.',
                'Usa termómetro e higrómetro para verificar las condiciones.',
            ]],
            ['icon' => 'house', 'title' => 'Refugio y sustrato', 'items' => [
                'Coloca al menos un escondite en cada zona de temperatura.',
                'Elige un sustrato adecuado a la especie y fácil de limpiar.',
            ]],
            ['icon' => 'wheat', 'title' => 'Alimentación e hidratación', 'items' => [
                'Hay especies insectívoras, herbívoras y omnívoras: la dieta cambia por completo según la tuya.',
                'Ofrece agua limpia en un plato estable y renuévala con frecuencia.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud preventiva', 'items' => [
                'Busca un veterinario con experiencia en reptiles.',
                'Lávate las manos después de manipularlo o limpiar el terrario.',
                'Consulta si deja de comer durante un periodo inusual para su especie o tiene problemas para mudar la piel.',
            ]],
        ],
    ],

    'horse' => [
        'summary' => 'Un caballo necesita forraje, agua abundante, movimiento diario y cuidados regulares de cascos y dientes.',
        'sections' => [
            ['icon' => 'wheat', 'title' => 'Alimentación e hidratación', 'items' => [
                'El forraje (pasto o heno) es la base de su dieta; los cambios de alimentación deben ser graduales.',
                'Debe tener agua limpia disponible siempre.',
                'Ajusta los concentrados según su trabajo con asesoría veterinaria o de un nutricionista equino.',
            ]],
            ['icon' => 'horseshoe', 'title' => 'Cascos y aseo', 'items' => [
                'Limpia los cascos antes y después de montar.',
                'Programa visitas regulares del herrador.',
                'Cepíllalo para revisar la piel y detectar heridas.',
            ]],
            ['icon' => 'house', 'title' => 'Instalaciones y ejercicio', 'items' => [
                'La cuadra debe estar ventilada, seca y limpia.',
                'Necesita salir a moverse y, si es posible, convivir con otros caballos.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud preventiva', 'items' => [
                'Sigue el plan de vacunas, desparasitación y revisión dental de tu veterinario.',
                'Llama al veterinario de inmediato si muestra signos de cólico, como mirarse el flanco, patear el abdomen o revolcarse con insistencia.',
            ]],
        ],
    ],

    'small_pet' => [
        'summary' => 'Conejos, cuyos, hámsteres, chinchillas y hurones tienen necesidades muy distintas entre sí: infórmate sobre tu especie.',
        'sections' => [
            ['icon' => 'wheat', 'title' => 'Alimentación', 'items' => [
                'Conejos y cuyos necesitan heno disponible todo el día como base de su dieta.',
                'Los hurones son carnívoros y requieren alimento específico para hurones.',
                'Introduce alimentos nuevos poco a poco y consulta qué verduras son adecuadas para tu especie.',
            ]],
            ['icon' => 'house', 'title' => 'Hábitat', 'items' => [
                'Elige una jaula o recinto amplio para el tamaño adulto de tu mascota.',
                'Evita pisos de rejilla que lastimen sus patas.',
                'Mantenlos lejos del calor intenso: chinchillas y conejos lo toleran mal.',
            ]],
            ['icon' => 'scissors', 'title' => 'Higiene', 'items' => [
                'Limpia el sustrato con regularidad.',
                'Las chinchillas se bañan en arena específica, no en agua.',
                'Revisa uñas y dientes: en roedores y conejos los dientes crecen toda la vida.',
            ]],
            ['icon' => 'volleyball', 'title' => 'Enriquecimiento', 'items' => [
                'Ofrece objetos para roer, túneles y escondites.',
                'Conejos y cuyos son sociables; infórmate antes de tenerlos solos.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud preventiva', 'items' => [
                'Busca un veterinario con experiencia en animales exóticos o pequeños.',
                'Consulta pronto si deja de comer o de hacer heces: en conejos y cuyos puede ser urgente.',
            ]],
        ],
    ],

    'farm' => [
        'summary' => 'Gallinas, cerdos, cabras y ovejas necesitan alimento adecuado, agua limpia, refugio seco y protección frente a depredadores.',
        'sections' => [
            ['icon' => 'wheat', 'title' => 'Alimentación e hidratación', 'items' => [
                'Usa alimento formulado para la especie y su etapa (crecimiento, postura, engorde).',
                'El agua limpia debe estar disponible siempre; revisa bebederos a diario.',
                'Cabras y ovejas son rumiantes: el forraje es la base de su dieta.',
            ]],
            ['icon' => 'house', 'title' => 'Corral y refugio', 'items' => [
                'Ofrece un refugio seco, ventilado y protegido del sol y la lluvia.',
                'Cerca y techa el área de las gallinas para protegerlas de depredadores.',
                'Las ponedoras usan nidales en lugares tranquilos y con poca luz.',
            ]],
            ['icon' => 'scissors', 'title' => 'Higiene', 'items' => [
                'Retira el estiércol y cambia la cama con regularidad.',
                'Revisa pezuñas en cabras y ovejas.',
            ]],
            ['icon' => 'stethoscope', 'title' => 'Salud preventiva', 'items' => [
                'Consulta a un veterinario de animales de granja sobre vacunas y desparasitación.',
                'Infórmate sobre los requisitos sanitarios y de registro de tu país o municipio.',
                'Aísla a los animales nuevos o enfermos antes de juntarlos con el grupo.',
            ]],
        ],
    ],
];
