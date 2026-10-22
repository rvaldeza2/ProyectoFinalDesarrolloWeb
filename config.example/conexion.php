<?php
$db_host     = 'TU_HOST.aivencloud.com';
$db_port     = 12345;
$db_user     = 'avnadmin';
$db_pass     = 'TU_CONTRASENA';
$db_name     = 'agenda_contactos';
$db_ssl_ca   = __DIR__ . '/ca.pem';
$db_timezone = '-06:00';

// Llave que se pide para crear, editar o eliminar desde la API
const API_KEY = 'CAMBIE-ESTA-CLAVE-SECRETA';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();

// Certificado de Aiven para que la conexión sea segura
mysqli_ssl_set($conn, null, null, $db_ssl_ca, null, null);

$conectado = @mysqli_real_connect(
    $conn,
    $db_host,
    $db_user,
    $db_pass,
    $db_name,
    $db_port,
    null,
    MYSQLI_CLIENT_SSL
);

if (!$conectado) {
    error_log('Error de conexion a MySQL: ' . mysqli_connect_error());

    if (function_exists('responder')) {
        responder(500, ['ok' => false, 'mensaje' => 'No se pudo conectar a la base de datos.']);
    }
    http_response_code(500);
    exit('No se pudo conectar a la base de datos');
}

// Para que acepte acentos y la ñ
mysqli_set_charset($conn, 'utf8mb4');
// Usa la hora de Guatemala para que las fechas se guarden con la hora correcta
$stmt = mysqli_prepare($conn, 'SET time_zone = ?');
mysqli_stmt_bind_param($stmt, 's', $db_timezone);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
