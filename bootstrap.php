<?php

declare(strict_types=1);

use FlowExtract\Support\Env;

$root = __DIR__;

if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'FlowExtract\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $root . '/app/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

Env::load($root . '/.env');

ini_set('display_errors', Env::get('APP_ENV', 'production') === 'local' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $root . '/storage/logs/php-error.log');

session_name('flowextract_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['_demo_token'])) {
    $_SESSION['_demo_token'] = bin2hex(random_bytes(32));
}
