<?php

declare(strict_types=1);

/*
 * Envía un correo de prueba con la configuración de .env.
 * Uso: php scripts/test_mail.php --to=tu@correo.com
 */

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$options = getopt('', ['to:']);
$to = (string) ($options['to'] ?? '');
if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Uso: php scripts/test_mail.php --to=tu@correo.com\n");
    exit(1);
}

$transport = (string) config('app.mail.transport');
try {
    App\Services\Mailer::send(
        $to,
        '',
        'Prueba de correo de ' . config('app.name'),
        "Este es un correo de prueba enviado desde " . config('app.name') . ".\n\nSi lo recibiste, el envío de correos está configurado correctamente.\n"
    );
    echo $transport === 'smtp'
        ? "Correo enviado a $to por SMTP (" . config('app.mail.host') . ").\n"
        : "Correo guardado en storage/mail (MAIL_TRANSPORT=$transport).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo enviar: ' . $e->getMessage() . "\n");
    exit(1);
}
