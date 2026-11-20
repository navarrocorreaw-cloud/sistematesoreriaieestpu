<?php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

$host = 'localhost';
$db   = 'tesoreria_iestp_utcubamba';
$user = 'root';
$pass = ''; // Configure según tu instalación de MySQL.
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa config/database.php y que MySQL esté iniciado.');
}
