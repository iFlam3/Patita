<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Sugiere mascota y categoría a partir del nombre y la URL de un producto
 * importado. Es una heurística por palabras clave: el resultado se marca como
 * "inferido" en el reporte y el producto queda inactivo hasta que un
 * administrador lo revise. Si no hay coincidencia devuelve null (no adivina).
 */
final class Classifier
{
    private const PETS = [
        'small_pet' => ['conejo', 'rabbit', 'hamster', 'hámster', 'cobaya', 'cuyo', 'guinea pig', 'chinchilla', 'hurón', 'huron', 'ferret', 'small animal', 'small pet'],
        'horse'     => ['caballo', 'equino', 'horse', 'equine', 'pony'],
        'farm'      => ['gallina', 'pollo', 'ave de corral', 'aves de corral', 'poultry', 'chicken', 'cerdo', 'pig', 'cabra', 'goat', 'oveja', 'sheep', 'vaca', 'cow', 'ganado', 'livestock', 'granja', 'farm'],
        'reptile'   => ['reptil', 'reptile', 'tortuga', 'turtle', 'tortoise', 'gecko', 'iguana', 'serpiente', 'snake', 'lizard', 'dragon'],
        'fish'      => ['pez', 'peces', 'fish', 'acuario', 'aquarium', 'betta', 'goldfish'],
        'bird'      => ['ave', 'aves', 'pájaro', 'pajaro', 'bird', 'parrot', 'loro', 'canario', 'canary', 'periquito', 'parakeet'],
        'cat'       => ['gato', 'gatito', 'felino', 'cat', 'kitten', 'feline'],
        'dog'       => ['perro', 'cachorro', 'canino', 'dog', 'puppy', 'canine'],
    ];

    private const CATEGORIES = [
        'equine_care'        => ['casco', 'hoof', 'montura', 'saddle', 'brida', 'bridle', 'cabezada', 'halter', 'equino', 'equine'],
        'aquarium_terrarium' => ['acuario', 'aquarium', 'terrario', 'terrarium', 'filtro', 'filter', 'tank', 'pecera'],
        'transport'          => ['transportadora', 'carrier', 'crate', 'jaula de viaje', 'travel'],
        'collars_leashes'    => ['collar', 'correa', 'leash', 'lead', 'arnés', 'arnes', 'harness'],
        'bowls_feeders'      => ['comedero', 'bebedero', 'bowl', 'feeder', 'fountain', 'fuente', 'plato', 'feeding mat'],
        'grooming'           => ['shampoo', 'champú', 'champu', 'cepillo', 'brush', 'groom', 'uñas', 'nail', 'arena', 'litter', 'higiene'],
        'beds_habitats'      => ['cama', 'bed', 'casa', 'house', 'refugio', 'hideout', 'jaula', 'cage', 'hamaca', 'hammock', 'manta', 'blanket'],
        'toys'               => ['juguete', 'toy', 'pelota', 'ball', 'rascador', 'scratcher', 'chew', 'mordedor'],
        'food'               => ['alimento', 'comida', 'food', 'treat', 'premio', 'snack', 'kibble', 'croqueta', 'heno', 'hay', 'semilla', 'seed'],
    ];

    public static function pet(string $text): ?string
    {
        return self::match(self::PETS, $text);
    }

    public static function category(string $text): ?string
    {
        return self::match(self::CATEGORIES, $text);
    }

    /**
     * @param array<string, list<string>> $map
     */
    private static function match(array $map, string $text): ?string
    {
        $haystack = ' ' . mb_strtolower(str_replace(['-', '_', '/'], ' ', $text), 'UTF-8') . ' ';
        foreach ($map as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/(?<![\p{L}])' . preg_quote($keyword, '/') . 's?(?![\p{L}])/u', $haystack) === 1) {
                    return $code;
                }
            }
        }

        return null;
    }
}
