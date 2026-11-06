<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\Auth;
use App\Services\Mailer;
use App\Services\RateLimiter;
use Throwable;

/**
 * Recuperación de contraseña con código de 6 dígitos enviado por correo:
 *  - El código se guarda con bcrypt, vence en PASSWORD_RESET_TTL_MINUTES y sirve una vez.
 *  - Cada código admite MAX_CODE_ATTEMPTS intentos; al agotarlos se invalida.
 *  - Además hay un tope de intentos por IP, para no probar códigos en muchas cuentas.
 *  - Las respuestas son idénticas exista o no el correo.
 */
final class PasswordResetController extends Controller
{
    private const MAX_CODE_ATTEMPTS = 5;
    private const MAX_IP_ATTEMPTS = 30;
    private const SESSION_EMAIL = 'reset_email';
    private const DUMMY_HASH = '$2y$10$7YoGvBf.v/OgvFekpgSl6Om2MKtFoeIIDf2YX2lR9oQIwRyVgjKDW';
    private const INVALID = 'El código no es válido o venció. Revísalo o pide uno nuevo.';

    /**
     * @param array<string, string> $params
     */
    public function showRequest(Request $request, array $params): Response
    {
        return $this->view('auth/forgot', ['title' => 'Recuperar contraseña', 'errors' => Session::errors()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function sendCode(Request $request, array $params): Response
    {
        $email = User::normalizeEmail($request->input('email'));
        $v = new Validator(['email' => $email]);
        $v->required('email', 'tu correo')->email('email');
        if ($v->fails()) {
            return $this->backWithErrors('/recuperar', ['email' => $email], $v->errors());
        }

        $auth = (array) config('app.auth');
        $decay = (int) $auth['login_decay_minutes'] * 60;
        $limiter = new RateLimiter();
        $keyAccount = 'reset|' . $email . '|' . $request->ip();
        $keyIp = 'reset-ip|' . $request->ip();
        $limited = $limiter->tooManyAttempts($keyAccount, (int) $auth['reset_max_requests'], $decay)
            || $limiter->tooManyAttempts($keyIp, (int) $auth['reset_max_requests'] * 4, $decay);

        if ($limited) {
            log_message('security', 'Solicitud de recuperación limitada.', ['ip' => $request->ip()]);
        } else {
            $limiter->hit($keyAccount, $decay);
            $limiter->hit($keyIp, $decay);
            $this->issueCode($email, (int) $auth['reset_ttl_minutes']);
        }

        // Mismo mensaje y mismo destino en todos los casos: no revela si el correo existe.
        Session::set(self::SESSION_EMAIL, $email);
        Session::flash('success', sprintf(
            'Si existe una cuenta con %s, te enviamos un código de 6 dígitos. Vence en %d minutos. Revisa también la carpeta de spam.',
            $email,
            (int) $auth['reset_ttl_minutes']
        ));
        if (config('app.env') === 'development' && config('app.mail.transport') === 'log') {
            Session::flash('info', 'Modo desarrollo: los correos se guardan como archivos .eml en storage/mail.');
        }

        return $this->redirect('/restablecer');
    }

    /**
     * @param array<string, string> $params
     */
    public function showReset(Request $request, array $params): Response
    {
        $email = Session::get(self::SESSION_EMAIL);
        if (!is_string($email) || $email === '') {
            return $this->redirect('/recuperar');
        }

        return $this->view('auth/reset', [
            'title'  => 'Nueva contraseña',
            'email'  => $email,
            'errors' => Session::errors(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function reset(Request $request, array $params): Response
    {
        $email = Session::get(self::SESSION_EMAIL);
        if (!is_string($email) || $email === '') {
            return $this->redirect('/recuperar');
        }

        $input = [
            'code'                  => preg_replace('/\s+/', '', $request->input('code')) ?? '',
            'password'              => $request->raw('password'),
            'password_confirmation' => $request->raw('password_confirmation'),
        ];
        $v = new Validator($input);
        $v->required('code', 'el código')
            ->required('password', 'una contraseña nueva')
            ->matches('password_confirmation', 'password', 'Las contraseñas no coinciden.');
        if ($input['code'] !== '' && preg_match('/^\d{6}$/', $input['code']) !== 1) {
            $v->addError('code', 'El código tiene 6 dígitos.');
        }
        foreach (AuthController::passwordProblems($input['password'], $email) as $problem) {
            $v->addError('password', $problem);
        }
        // Los errores de formato no gastan intentos.
        if ($v->fails()) {
            return $this->backWithErrors('/restablecer', ['code' => $input['code']], $v->errors());
        }

        $ttl = (int) config('app.auth.reset_ttl_minutes') * 60;
        $limiter = new RateLimiter();
        $keyIp = 'reset-verify-ip|' . $request->ip();
        if ($limiter->tooManyAttempts($keyIp, self::MAX_IP_ATTEMPTS, $ttl)) {
            log_message('security', 'Verificación de código limitada por IP.', ['ip' => $request->ip()]);

            return $this->codeError('Demasiados intentos desde tu conexión. Espera unos minutos y vuelve a intentarlo.');
        }
        $limiter->hit($keyIp, $ttl);

        $user = User::findByEmail($email);
        $token = $user !== null ? PasswordResetToken::latestValidForUser((int) $user['id']) : null;
        if ($token === null) {
            password_verify($input['code'], self::DUMMY_HASH); // mismo tiempo de respuesta exista o no la cuenta

            return $this->codeError(self::INVALID);
        }

        $keyCode = 'reset-code|' . (int) $token['id'];
        if ($limiter->tooManyAttempts($keyCode, self::MAX_CODE_ATTEMPTS, $ttl)) {
            PasswordResetToken::invalidateForUser((int) $user['id']);

            return $this->codeError('Superaste el número de intentos para este código. Pide uno nuevo.');
        }
        if (!password_verify($input['code'], (string) $token['token_hash'])) {
            $limiter->hit($keyCode, $ttl);
            if ($limiter->tooManyAttempts($keyCode, self::MAX_CODE_ATTEMPTS, $ttl)) {
                PasswordResetToken::invalidateForUser((int) $user['id']);
                log_message('security', 'Código de recuperación bloqueado por intentos.', ['user_id' => (int) $user['id']]);

                return $this->codeError('Superaste el número de intentos para este código. Pide uno nuevo.');
            }

            return $this->codeError(self::INVALID);
        }

        if (!PasswordResetToken::markUsed((int) $token['id'])) {
            return $this->codeError(self::INVALID);
        }

        User::updatePassword((int) $user['id'], password_hash($input['password'], PASSWORD_DEFAULT));
        PasswordResetToken::invalidateForUser((int) $user['id']);
        $limiter->clear($keyCode);
        $limiter->clear('login|' . $email . '|' . $request->ip());
        Session::forget(self::SESSION_EMAIL);

        // Cierra la sesión actual; las demás quedan invalidadas porque cambió la huella de la contraseña.
        Auth::logout();
        Session::flash('success', 'Actualizamos tu contraseña. Ya puedes ingresar con la nueva.');

        return $this->redirect('/ingresar');
    }

    private function codeError(string $message): Response
    {
        return $this->backWithErrors('/restablecer', [], ['code' => $message], $message);
    }

    private function issueCode(string $email, int $ttlMinutes): void
    {
        $user = User::findByEmail($email);
        if ($user === null) {
            return;
        }

        try {
            PasswordResetToken::invalidateForUser((int) $user['id']);
            $code = PasswordResetToken::newCode();
            PasswordResetToken::create((int) $user['id'], $code, $ttlMinutes);

            $body = sprintf(
                "Hola, %s:\n\nRecibimos una solicitud para restablecer la contraseña de tu cuenta en %s.\n\n"
                . "Tu código de verificación es:\n\n    %s\n\n"
                . "Escríbelo en la página de recuperación. Vence en %d minutos y solo sirve una vez.\n\n"
                . "Si no lo solicitaste, ignora este correo: tu contraseña actual sigue funcionando. Nunca te pediremos este código por teléfono ni por chat.\n",
                (string) $user['name'],
                (string) config('app.name'),
                implode(' ', str_split($code, 3)),
                $ttlMinutes
            );
            Mailer::send((string) $user['email'], (string) $user['name'], 'Tu código para restablecer la contraseña', $body);

            if (random_int(1, 20) === 1) {
                PasswordResetToken::purgeExpired();
            }
        } catch (Throwable $e) {
            // Nunca se registra el código.
            log_message('mail', 'No se pudo enviar el correo de recuperación.', ['error' => $e->getMessage()]);
        }
    }
}
