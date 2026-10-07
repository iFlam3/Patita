<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Auth as AuthMiddleware;

/**
 * Enrutador mínimo: patrones con parámetros {nombre} o {nombre:regex},
 * verificación CSRF obligatoria en POST y middleware por nombre
 * ('auth', 'admin', 'guest').
 */
final class Router
{
    /**
     * @var list<array{method: string, regex: string, handler: array{0: class-string, 1: string}, middleware: list<string>}>
     */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodMismatch = true;
                continue;
            }

            if ($method === 'POST') {
                $token = $request->input('_token');
                if ($token === '') {
                    $token = $request->header('X-CSRF-Token');
                }
                if (!Csrf::verify($token)) {
                    throw new HttpException(419);
                }
            }

            foreach ($route['middleware'] as $name) {
                $response = AuthMiddleware::handle($name, $request);
                if ($response instanceof Response) {
                    return $response;
                }
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();

            return $controller->{$action}($request, $params);
        }

        throw new HttpException($methodMismatch ? 405 : 404);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $regex = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            static fn (array $m): string => '(?P<' . $m[1] . '>' . (isset($m[2]) && $m[2] !== '' ? $m[2] : '[^/]+') . ')',
            $pattern
        );

        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#u',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }
}
