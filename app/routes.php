<?php

declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AdminController;
use App\Controllers\AdminOrderController;
use App\Controllers\AdminProductController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\PasswordResetController;
use App\Core\Router;

/*
 * Todas las rutas POST exigen token CSRF (lo verifica el Router).
 */
return static function (Router $r): void {
    // Tienda
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/envios-y-pagos', [HomeController::class, 'shipping']);
    $r->get('/catalogo', [CatalogController::class, 'index']);
    $r->get('/mascotas', [CatalogController::class, 'pets']);
    $r->get('/mascotas/{pet:[a-z-]+}', [CatalogController::class, 'pet']);
    $r->get('/marcas', [CatalogController::class, 'brands']);
    $r->get('/marcas/{brand:[a-z0-9-]+}', [CatalogController::class, 'brand']);
    $r->get('/producto/{slug:[a-z0-9-]+}', [CatalogController::class, 'product']);

    // Carrito (sesión PHP)
    $r->get('/carrito', [CartController::class, 'index']);
    $r->post('/carrito/agregar', [CartController::class, 'add']);
    $r->post('/carrito/actualizar', [CartController::class, 'update']);
    $r->post('/carrito/eliminar', [CartController::class, 'remove']);

    // Checkout (requiere sesión)
    $r->get('/checkout', [CheckoutController::class, 'form'], ['auth']);
    $r->post('/checkout/revisar', [CheckoutController::class, 'review'], ['auth']);
    $r->get('/checkout/revisar', [CheckoutController::class, 'showReview'], ['auth']);
    $r->post('/checkout/confirmar', [CheckoutController::class, 'place'], ['auth']);
    $r->get('/pedido/{id:\d+}/confirmacion', [CheckoutController::class, 'confirmation'], ['auth']);

    // Autenticación
    $r->get('/ingresar', [AuthController::class, 'showLogin'], ['guest']);
    $r->post('/ingresar', [AuthController::class, 'login'], ['guest']);
    $r->get('/registro', [AuthController::class, 'showRegister'], ['guest']);
    $r->post('/registro', [AuthController::class, 'register'], ['guest']);
    $r->post('/salir', [AuthController::class, 'logout'], ['auth']);
    $r->get('/recuperar', [PasswordResetController::class, 'showRequest']);
    $r->post('/recuperar', [PasswordResetController::class, 'sendLink']);
    $r->get('/restablecer', [PasswordResetController::class, 'showReset']);
    $r->post('/restablecer', [PasswordResetController::class, 'reset']);

    // Mi cuenta
    $r->get('/cuenta', [AccountController::class, 'index'], ['auth']);
    $r->post('/cuenta/perfil', [AccountController::class, 'updateProfile'], ['auth']);
    $r->post('/cuenta/contrasena', [AccountController::class, 'updatePassword'], ['auth']);
    $r->get('/cuenta/pedidos', [AccountController::class, 'orders'], ['auth']);
    $r->get('/cuenta/pedidos/{id:\d+}', [AccountController::class, 'order'], ['auth']);

    // Administración (rol admin)
    $r->get('/admin', [AdminController::class, 'dashboard'], ['admin']);
    $r->get('/admin/importador', [AdminController::class, 'import'], ['admin']);
    $r->get('/admin/productos', [AdminProductController::class, 'index'], ['admin']);
    $r->get('/admin/productos/nuevo', [AdminProductController::class, 'create'], ['admin']);
    $r->post('/admin/productos', [AdminProductController::class, 'store'], ['admin']);
    $r->get('/admin/productos/{id:\d+}/editar', [AdminProductController::class, 'edit'], ['admin']);
    $r->post('/admin/productos/{id:\d+}', [AdminProductController::class, 'update'], ['admin']);
    $r->post('/admin/productos/{id:\d+}/estado', [AdminProductController::class, 'toggle'], ['admin']);
    $r->post('/admin/productos/{id:\d+}/eliminar', [AdminProductController::class, 'destroy'], ['admin']);
    $r->get('/admin/pedidos', [AdminOrderController::class, 'index'], ['admin']);
    $r->get('/admin/pedidos/{id:\d+}', [AdminOrderController::class, 'show'], ['admin']);
    $r->post('/admin/pedidos/{id:\d+}/estado', [AdminOrderController::class, 'updateStatus'], ['admin']);
};
