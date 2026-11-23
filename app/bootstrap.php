<?php
declare(strict_types=1);

// MEMORii bootstrap: autoload, env, errors, DB. No framework, no Composer runtime deps.
define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require $file;
    }
});
require BASE_PATH . '/app/Core/helpers.php';
require BASE_PATH . '/app/Core/ui.php';

App\Core\Env::load(BASE_PATH . '/.env');
date_default_timezone_set(App\Core\Env::get('APP_TIMEZONE', 'Asia/Bangkok'));

$debug = App\Core\Env::bool('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', ($debug || PHP_SAPI === 'cli') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
