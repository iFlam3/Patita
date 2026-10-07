<?php

declare(strict_types=1);

/*
 * Crea un usuario administrador o promueve uno existente.
 *
 * Uso:
 *   php scripts/create_admin.php --email=admin@ejemplo.com --name="Nombre"
 *   (pide la contraseña por la terminal; también acepta recibirla por STDIN)
 *
 *   php scripts/create_admin.php --email=cliente@ejemplo.com --promote
 *   (convierte en administrador una cuenta existente sin cambiar su contraseña)
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$options = getopt('', ['email:', 'name:', 'promote', 'help']);
if (isset($options['help']) || !isset($options['email'])) {
    echo "Uso: php scripts/create_admin.php --email=correo [--name=\"Nombre\"] [--promote]\n";
    exit(isset($options['help']) ? 0 : 1);
}

$email = User::normalizeEmail((string) $options['email']);
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Correo inválido.\n");
    exit(1);
}

$existing = User::findByEmail($email);

if (isset($options['promote'])) {
    if ($existing === null) {
        fwrite(STDERR, "No existe una cuenta con ese correo.\n");
        exit(1);
    }
    User::setRole((int) $existing['id'], 'admin');
    echo "La cuenta $email ahora es administradora.\n";
    exit(0);
}

if ($existing !== null) {
    fwrite(STDERR, "Ya existe una cuenta con ese correo. Usa --promote para convertirla en administradora.\n");
    exit(1);
}

$name = trim((string) ($options['name'] ?? 'Administrador'));
if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
    fwrite(STDERR, "El nombre debe tener entre 2 y 120 caracteres.\n");
    exit(1);
}

$interactive = function_exists('stream_isatty') && stream_isatty(STDIN);
if ($interactive) {
    echo "Contraseña (mínimo " . (int) config('app.auth.password_min_length', 8) . " caracteres; se verá al escribirla): ";
}
$password = rtrim((string) fgets(STDIN), "\r\n");
if ($interactive) {
    echo "Repite la contraseña: ";
    $repeat = rtrim((string) fgets(STDIN), "\r\n");
    if ($repeat !== $password) {
        fwrite(STDERR, "Las contraseñas no coinciden.\n");
        exit(1);
    }
}

$problems = AuthController::passwordProblems($password, $email);
if ($password === '' || $problems !== []) {
    fwrite(STDERR, ($problems !== [] ? implode("\n", $problems) : 'La contraseña es obligatoria.') . "\n");
    exit(1);
}

User::create($name, $email, password_hash($password, PASSWORD_DEFAULT), 'admin');
echo "Administrador creado: $email\n";
