<?php
/**
 * Database connection settings.
 * Copy to config.local.php and override, or set environment variables DB_HOST, DB_NAME, DB_USER, DB_PASS.
 */
declare(strict_types=1);

$local = __DIR__ . '/config.local.php';
if (is_readable($local)) {
    return array_merge([
        'host' => '127.0.0.1',
        'dbname' => 'hospital_records',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ], require $local);
}

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'dbname' => getenv('DB_NAME') ?: 'hospital_records',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];
