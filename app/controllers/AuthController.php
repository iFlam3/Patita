<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Services\Auth;
use App\Services\RateLimiter;

final class AuthController extends Controller
{
    /**
     * Hash bcrypt válido de una cadena aleatoria. Se verifica contra él cuando
     * el correo no existe para que la respuesta tarde lo mismo.
     */
    private const DUMMY_HASH = '$2y$10$Mw1dKooNSRhAEuCjV6i0weh54S8z/sGsgBHck7Y4RjguKqrC552HS';

    /**
     * @param array<string, string> $params
     */
    public function showLogin(Request $request, array $params): Response
    {
        return $this->view('auth/login', ['title' => 'Ingresar', 'errors' => Session::errors()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function login(Request $request, array $params): Response
    {
        $email = User::normalizeEmail($request->input('email'));
        $password = $request->raw('password');
        $input = ['email' => $email];

        if ($email === '' || $password === '') {
            return $this->backWithErrors('/ingresar', $input, array_filter([
                'email'    => $email === '' ? 'Ingresa tu correo.' : '',
                'password' => $password === '' ? 'Ingresa tu contraseña.' : '',
            ]));
        }

        $auth = (array) config('app.auth');
        $decay = (int) $auth['login_decay_minutes'] * 60;
        $limiter = new RateLimiter();
        $keyAccount = 'login|' . $email . '|' . $request->ip();
        $keyIp = 'login-ip|' . $request->ip();

        if ($limiter->tooManyAttempts($keyAccount, (int) $auth['login_max_attempts'], $decay)
            || $limiter->tooManyAttempts($keyIp, (int) $auth['login_max_attempts_per_ip'], $decay)) {
            $wait = max($limiter->availableIn($keyAccount, $decay), $limiter->availableIn($keyIp, $decay));
            log_message('security', 'Inicio de sesión bloqueado por exceso de intentos.', ['ip' => $request->ip()]);
            Session::flashInput($input);
            Session::flash('error', sprintf(
                'Hiciste demasiados intentos. Espera %d minuto(s) antes de volver a intentar o recupera tu contraseña.',
                (int) ceil($wait / 60)
            ));

            return $this->redirect('/ingresar');
        }

        $user = User::findByEmail($email);
        $valid = password_verify($password, $user !== null ? (string) $user['password_hash'] : self::DUMMY_HASH);

        if ($user === null || !$valid) {
            $limiter->hit($keyAccount, $decay);
            $limiter->hit($keyIp, $decay);
            Session::flashInput($input);
            Session::flash('error', 'El correo o la contraseña no coinciden. Revisa los datos e intenta de nuevo.');

            return $this->redirect('/ingresar');
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
            $user = User::find((int) $user['id']) ?? $user;
        }

        $limiter->clear($keyAccount);
        $intended = safe_path(Session::pull('intended'), $user['role'] === 'admin' ? '/admin' : '/cuenta');
        Auth::login($user);
        Session::flash('success', 'Hola, ' . (string) $user['name'] . '. Iniciaste sesión.');

        return $this->redirect($intended);
    }

    /**
     * @param array<string, string> $params
     */
    public function showRegister(Request $request, array $params): Response
    {
        return $this->view('auth/register', ['title' => 'Crear cuenta', 'errors' => Session::errors()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function register(Request $request, array $params): Response
    {
        $input = [
            'name'                  => $request->input('name'),
            'email'                 => User::normalizeEmail($request->input('email')),
            'password'              => $request->raw('password'),
            'password_confirmation' => $request->raw('password_confirmation'),
        ];

        $v = new Validator($input);
        $v->required('name', 'tu nombre')->length('name', 'El nombre', 2, 120)
            ->required('email', 'tu correo')->email('email')
            ->required('password', 'una contraseña')
            ->matches('password_confirmation', 'password', 'Las contraseñas no coinciden.');
        foreach (self::passwordProblems($input['password'], $input['email']) as $problem) {
            $v->addError('password', $problem);
        }
        if (!$v->fails() && User::emailExists($input['email'])) {
            $v->addError('email', 'Ya existe una cuenta con este correo. Inicia sesión o recupera tu contraseña.');
        }

        if ($v->fails()) {
            return $this->backWithErrors('/registro', $input, $v->errors());
        }

        try {
            $id = User::create($input['name'], $input['email'], password_hash($input['password'], PASSWORD_DEFAULT));
        } catch (\PDOException $e) {
            // Carrera con otro registro del mismo correo (índice UNIQUE).
            if ((string) $e->getCode() === '23000') {
                return $this->backWithErrors('/registro', $input, ['email' => 'Ya existe una cuenta con este correo.']);
            }
            throw $e;
        }

        $user = User::find($id);
        if ($user !== null) {
            Auth::login($user);
        }
        Session::flash('success', 'Creaste tu cuenta. ¡Bienvenido a ' . (string) config('app.name') . '!');

        return $this->redirect(safe_path(Session::pull('intended'), '/cuenta'));
    }

    /**
     * @param array<string, string> $params
     */
    public function logout(Request $request, array $params): Response
    {
        Auth::logout();
        Session::flash('success', 'Cerraste sesión.');

        return $this->redirect('/');
    }

    /**
     * Reglas de contraseña compartidas por registro, cambio y recuperación.
     *
     * @return list<string>
     */
    public static function passwordProblems(string $password, string $email = ''): array
    {
        if ($password === '') {
            return [];
        }
        $min = (int) config('app.auth.password_min_length', 8);
        $problems = [];
        if (mb_strlen($password, 'UTF-8') < $min) {
            $problems[] = sprintf('La contraseña debe tener al menos %d caracteres.', $min);
        }
        if (strlen($password) > 72) {
            $problems[] = 'La contraseña admite como máximo 72 bytes (unos 72 caracteres sin acentos).';
        }
        if ($email !== '' && mb_strtolower($password, 'UTF-8') === mb_strtolower($email, 'UTF-8')) {
            $problems[] = 'La contraseña no puede ser igual a tu correo.';
        }

        return $problems;
    }
}
