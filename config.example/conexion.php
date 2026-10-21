<?php
$db_host     = 'TU_HOST.aivencloud.com';
$db_port     = 12345;
$db_user     = 'avnadmin';
$db_pass     = 'TU_CONTRASENA';
$db_name     = 'agenda_contactos';
$db_ssl_ca   = __DIR__ . '/ca.pem';
$db_timezone = '-06:00';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_init();

    // Certificado CA de Aiven para verificar el servidor y cifrar la conexion
    mysqli_ssl_set($conn, null, null, $db_ssl_ca, null, null);

    mysqli_real_connect(
        $conn,
        $db_host,
        $db_user,
        $db_pass,
        $db_name,
        $db_port,
        null,
        MYSQLI_CLIENT_SSL
    );

    // Para que acepte acentos y la ñ
    mysqli_set_charset($conn, 'utf8mb4');
    // Usa la hora de Guatemala para que las fechas se guarden con la hora correcta
    $stmt = mysqli_prepare($conn, 'SET time_zone = ?');
    mysqli_stmt_bind_param($stmt, 's', $db_timezone);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $e) {
    error_log('Error de conexion a MySQL: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar a la base de datos');
}
