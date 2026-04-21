<?php
/**
 * Database connection settings.
 * Copy to config.local.php and override, or set environment variables.
 */
declare(strict_types=1);

$local = __DIR__ . '/config.local.php';
if (is_readable($local)) {
    return array_merge([
        'host'    => '127.0.0.1',
        'dbname'  => 'sys',
        'user'    => 'root',
        'pass'    => 'oogabooga123',
        'charset' => 'utf8mb4',
    ], require $local);
}

return [
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'dbname'  => getenv('DB_NAME') ?: 'sys',
    'user'    => getenv('DB_USER') ?: 'root',
    'pass'    => getenv('DB_PASS') ?: 'oogabooga123',
    'charset' => 'utf8mb4',
];
