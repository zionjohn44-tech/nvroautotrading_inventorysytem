<?php
declare(strict_types=1);

return [
    'host' => getenv('NVRO_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('NVRO_DB_PORT') ?: '3306',
    'name' => getenv('NVRO_DB_NAME') ?: 'nvroinventory_db',
    'username' => getenv('NVRO_DB_USER') ?: 'root',
    'password' => getenv('NVRO_DB_PASSWORD') ?: '',
];
