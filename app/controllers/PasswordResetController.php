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
 * Recuperación de contraseña:
 *  - Token aleatorio de 256 bits, enviado una sola vez por correo.
 *  - En la base de datos solo se guarda su hash SHA-256.
 *  - Vence en PASSWORD_RESET_TTL_MINUTES y se marca como usado al aplicarse.
 *  - La respuesta es idéntica exista o no el correo.
 */
final class PasswordResetController extends Controller
{
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
    public function sendLink(Request $request, array $params): Response
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
            $this->issueToken($email, (int) $auth['reset_ttl_minutes']);
        }

        // Mismo mensaje en todos los casos: no revela si el correo existe.
        $message = sprintf(
            'Si existe una cuenta con %s, te enviamos un enlace para crear una nueva contraseña. Vence en %d minutos. Revisa también la carpeta de spam.',
            $email,
            (int) $auth['reset_ttl_minutes']
        );
        Session::flash('success', $message);
        if (config('app.env') === 'development' && config('app.mail.transport') === 'log') {
            Session::flash('info', 'Modo desarrollo: los correos se guardan como archivos .eml en storage/mail (no se muestran en la web).');
        }

        return $this->redirect('/recuperar');
    }

    /**
     * Recibe el enlace del correo. El token se pasa a la sesión y se redirige a
     * una URL limpia para que no quede en el historial ni en la cabecera Referer.
     *
     * @param array<string, string> $params
     */
    public function showReset(Request $request, array $params): Response
    {
        $fromLink = $request->query('token');
        if ($fromLink !== '') {
            if (preg_match('/^[a-f0-9]{64}$/', $fromLink) === 1) {
                Session::set('reset_token', $fromLink);
            } else {
                Session::forget('reset_token');
            }

            return $this->redirect('/restablecer');
        }

        $token = $this->validToken();

        return $this->view('auth/reset', [
            'title'  => 'Nueva contraseña',
            'valid'  => $token !== null,
            'errors' => Session::errors(),
        ])->withHeader('Referrer-Policy', 'no-referrer');
    }

    /**
     * @param array<string, string> $params
     */
    public function reset(Request $request, array $params): Response
    {
        $token = $this->validToken();
        if ($token === null) {
            Session::flash('error', 'El enlace no es válido o venció. Solicita uno nuevo.');

            return $this->redirect('/recuperar');
        }

        $user = User::find((int) $token['user_id']);
        if ($user === null) {
            Session::forget('reset_token');

            return $this->redirect('/recuperar');
        }

        $input = [
            'password'              => $request->raw('password'),
            'password_confirmation' => $request->raw('password_confirmation'),
        ];
        $v = new Validator($input);
        $v->required('password', 'una contraseña nueva')
            ->matches('password_confirmation', 'password', 'Las contraseñas no coinciden.');
        foreach (AuthController::passwordProblems($input['password'], (string) $user['email']) as $problem) {
            $v->addError('password', $problem);
        }
        if ($v->fails()) {
            return $this->backWithErrors('/restablecer', [], $v->errors());
        }

        if (!PasswordResetToken::markUsed((int) $token['id'])) {
            Session::flash('error', 'Este enlace ya se usó. Solicita uno nuevo si lo necesitas.');

            return $this->redirect('/recuperar');
        }

        User::updatePassword((int) $user['id'], password_hash($input['password'], PASSWORD_DEFAULT));
        PasswordResetToken::invalidateForUser((int) $user['id']);
        (new RateLimiter())->clear('login|' . User::normalizeEmail((string) $user['email']) . '|' . $request->ip());

        // Cierra cualquier sesión actual; las demás sesiones del usuario quedan
        // invalidadas porque cambió la huella de la contraseña.
        Auth::logout();
        Session::flash('success', 'Actualizamos tu contraseña. Ya puedes ingresar con la nueva.');

        return $this->redirect('/ingresar');
    }

    private function issueToken(string $email, int $ttlMinutes): void
    {
        $user = User::findByEmail($email);
        if ($user === null) {
            return;
        }

        try {
            PasswordResetToken::invalidateForUser((int) $user['id']);
            $token = bin2hex(random_bytes(32));
            PasswordResetToken::create((int) $user['id'], hash('sha256', $token), $ttlMinutes);

            $link = absolute_url('/restablecer', ['token' => $token]);
            $body = sprintf(
                "Hola, %s:\n\nRecibimos una solicitud para restablecer la contraseña de tu cuenta en %s.\n\n"
                . "Crea una nueva contraseña con este enlace (vence en %d minutos y solo funciona una vez):\n%s\n\n"
                . "Si no la solicitaste, ignora este correo: tu contraseña actual sigue funcionando.\n",
                (string) $user['name'],
                (string) config('app.name'),
                $ttlMinutes,
                $link
            );
            Mailer::send((string) $user['email'], (string) $user['name'], 'Restablece tu contraseña', $body);

            if (random_int(1, 20) === 1) {
                PasswordResetToken::purgeExpired();
            }
        } catch (Throwable $e) {
            // Nunca se registra el token ni el enlace.
            log_message('mail', 'No se pudo enviar el correo de recuperación.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validToken(): ?array
    {
        $token = Session::get('reset_token');
        if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        return PasswordResetToken::findValid(hash('sha256', $token));
    }
}
