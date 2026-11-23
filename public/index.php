<?php
declare(strict_types=1);
// MEMORii front controller. The web server's document root must be this /public directory.
require __DIR__ . '/../app/bootstrap.php';

use App\Core\{Auth, Csrf, DomainError, Http, I18n, Router, Session, View};
use App\Controllers\Controller;

Http::securityHeaders();
Session::start();

$cookieLocale = $_COOKIE['memorii_locale'] ?? null;
I18n::setLocale($cookieLocale === 'en' ? 'en' : 'th');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if ($path !== '/' && str_ends_with($path, '/')) $path = rtrim($path, '/');

$router = new Router();
require BASE_PATH . '/app/routes.php';

$render = static function (int $status, string $titleKey, string $bodyKey): never {
    http_response_code($status);
    echo View::render('errors/error', ['title' => t($titleKey), 'body' => t($bodyKey), 'actor' => Auth::actor()]);
    exit;
};

try {
    $route = $router->match($method, $path);
    if (!$route) $render(404, 'common.notFoundTitle', 'common.notFoundBody');
    [[$class, $action], $params, $access] = $route;

    if ($method === 'POST' && !Csrf::verify($_POST['_csrf'] ?? null)) {
        Session::flash('error', t('common.csrf'));
        Http::redirect(Http::safePath($_POST['_back'] ?? null, $path));
    }
    if ($access !== 'guest') {
        $actor = Auth::actor();
        if (!$actor) Http::redirect('/login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
        if ($access !== 'auth' && !$actor->has($access)) $render(403, 'common.forbiddenTitle', 'common.forbiddenBody');
    }
    $out = (new $class())->$action(...array_values($params));
    if (is_string($out)) echo $out;
} catch (DomainError $e) {
    if ($method === 'POST') {
        Session::flash('error', Controller::errorText($e));
        Http::redirect(Http::safePath($_POST['_back'] ?? null, $path));
    }
    match ($e->httpStatus()) {
        403 => $render(403, 'common.forbiddenTitle', 'common.forbiddenBody'),
        404 => $render(404, 'common.notFoundTitle', 'common.notFoundBody'),
        default => $render(422, 'common.error', 'common.error'),
    };
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo App\Core\Env::bool('APP_DEBUG') ? '<pre>' . e((string) $e) . '</pre>' : View::render('errors/error', ['title' => t('common.error'), 'body' => '', 'actor' => null]);
}
