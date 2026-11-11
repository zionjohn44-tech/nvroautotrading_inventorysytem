<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';

use NVRO\Core\Database;

$config = require dirname(__DIR__) . '/config/database.php';
if (is_file(dirname(__DIR__) . '/config/local.php')) {
    $config = array_replace($config, require dirname(__DIR__) . '/config/local.php');
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['name'])) { throw new RuntimeException('Invalid database name.'); }
$server = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$server->exec('CREATE DATABASE IF NOT EXISTS `' . $config['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db = Database::connection();
$db->exec(file_get_contents(__DIR__ . '/schema.sql'));
$columns = [
    'vehicle' => [
        'Image_Path_2' => 'VARCHAR(255) DEFAULT NULL',
        'Image_Path_3' => 'VARCHAR(255) DEFAULT NULL',
    ],
    'user' => [
        'Session_Version' => 'INT NOT NULL DEFAULT 1',
        'Must_Change_Password' => 'TINYINT(1) NOT NULL DEFAULT 0',
    ],
    'test_drive' => [
        'Duration_Minutes' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 30',
        'Created_At' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ],
];
foreach ($columns as $table => $definitions) {
    $existing = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($definitions as $column => $definition) {
        if (!in_array($column, $existing, true)) {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }
}
$db->exec('CREATE TABLE IF NOT EXISTS auth_attempts (Attempt_Key CHAR(64) PRIMARY KEY, Failures INT NOT NULL DEFAULT 0, Window_Start DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
// Preserve existing staff accounts while retiring the Sales Staff role.
Database::transaction(function (PDO $db): void {
    $db->exec("UPDATE employee SET Position='Dealership Staff' WHERE Position='Sales Staff'");
    $db->exec("UPDATE `user` SET Role='Dealership Staff', Session_Version=Session_Version+1 WHERE Role='Sales Staff'");
});
$roleColumn = $db->query("SHOW COLUMNS FROM `user` LIKE 'Role'")->fetch();
if ($roleColumn['Type'] !== "enum('Administrator','Dealership Staff')") {
    $db->exec("ALTER TABLE `user` MODIFY Role ENUM('Administrator','Dealership Staff') NOT NULL");
}
echo "Database ready: {$config['name']}. Existing records preserved.\n";
