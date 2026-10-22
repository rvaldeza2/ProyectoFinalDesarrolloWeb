<?php

header('Content-Type: application/json; charset=utf-8');

// Para que las fechas de PHP salgan con la hora de Guatemala
date_default_timezone_set('America/Guatemala');

// Tipos que se aceptan
const TIPOS_DIRECCION = ['casa', 'trabajo', 'otro'];

require __DIR__ . '/../config/conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

switch ($metodo) {
    case 'GET':
        if ($id > 0) {
            ver_direccion($conn, $id);
        } else {
            listar_direcciones($conn, (int) ($_GET['contacto_id'] ?? 0));
        }
        break;

    case 'POST':
        requerir_api_key();
        crear_direccion($conn, leer_entrada());
        break;

    case 'PUT':
        requerir_api_key();
        exigir_id($id);
        actualizar_direccion($conn, $id, leer_entrada());
        break;

    case 'DELETE':
        requerir_api_key();
        exigir_id($id);
        eliminar_direccion($conn, $id);
        break;

    default:
        responder(405, ['ok' => false, 'mensaje' => 'Método no permitido. Use GET, POST, PUT o DELETE.']);
}

function ver_direccion($conn, $id)
{
    $stmt = mysqli_prepare($conn,
        'SELECT id, contacto_id, direccion, ciudad, departamento, pais, tipo FROM direcciones WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$fila) {
        responder(404, ['ok' => false, 'mensaje' => 'Dirección no encontrada.']);
    }
    responder(200, ['ok' => true, 'datos' => $fila]);
}

// Devuelve todas las direcciones o solo las de un contacto
function listar_direcciones($conn, $contacto_id)
{
    if ($contacto_id > 0) {
        $stmt = mysqli_prepare($conn,
            'SELECT id, contacto_id, direccion, ciudad, departamento, pais, tipo FROM direcciones WHERE contacto_id = ? ORDER BY id');
        mysqli_stmt_bind_param($stmt, 'i', $contacto_id);
    } else {
        $stmt = mysqli_prepare($conn,
            'SELECT id, contacto_id, direccion, ciudad, departamento, pais, tipo FROM direcciones ORDER BY contacto_id, id');
    }
    mysqli_stmt_execute($stmt);
    $filas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    responder(200, ['ok' => true, 'total' => count($filas), 'datos' => $filas]);
}

function crear_direccion($conn, $entrada)
{
    $d = validar_direccion($entrada, true);

    $stmt = mysqli_prepare($conn, 'SELECT id FROM contactos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $d['contacto_id']);
    mysqli_stmt_execute($stmt);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        responder(404, ['ok' => false, 'mensaje' => 'El contacto no existe.']);
    }

    $stmt = mysqli_prepare($conn,
        'INSERT INTO direcciones (contacto_id, direccion, ciudad, departamento, pais, tipo) VALUES (?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'isssss', $d['contacto_id'],
        $d['direccion'], $d['ciudad'], $d['departamento'], $d['pais'], $d['tipo']);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    $nuevoId = mysqli_insert_id($conn);
    responder(201, ['ok' => true, 'mensaje' => 'Dirección creada.', 'datos' => ['id' => $nuevoId] + $d]);
}

function actualizar_direccion($conn, $id, $entrada)
{
    $stmt = mysqli_prepare($conn, 'SELECT contacto_id FROM direcciones WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$fila) {
        responder(404, ['ok' => false, 'mensaje' => 'Dirección no encontrada.']);
    }

    $d = validar_direccion($entrada, false);

    $stmt = mysqli_prepare($conn,
        'UPDATE direcciones SET direccion = ?, ciudad = ?, departamento = ?, pais = ?, tipo = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'sssssi',
        $d['direccion'], $d['ciudad'], $d['departamento'], $d['pais'], $d['tipo'], $id);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    responder(200, ['ok' => true, 'mensaje' => 'Dirección actualizada.',
        'datos' => ['id' => $id, 'contacto_id' => $fila['contacto_id']] + $d]);
}

function eliminar_direccion($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'DELETE FROM direcciones WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        responder(404, ['ok' => false, 'mensaje' => 'Dirección no encontrada.']);
    }
    responder(200, ['ok' => true, 'mensaje' => 'Dirección eliminada.']);
}

// Revisa que los datos vengan completos y correctos
function validar_direccion($entrada, $con_contacto)
{
    $d = [
        'direccion'    => trim((string) ($entrada['direccion'] ?? '')),
        'ciudad'       => trim((string) ($entrada['ciudad'] ?? '')),
        'departamento' => trim((string) ($entrada['departamento'] ?? '')),
        'pais'         => trim((string) ($entrada['pais'] ?? '')),
        'tipo'         => trim((string) ($entrada['tipo'] ?? '')),
    ];
    $errores = [];

    if ($con_contacto) {
        $d['contacto_id'] = (int) ($entrada['contacto_id'] ?? 0);
        if ($d['contacto_id'] <= 0) {
            $errores['contacto_id'] = 'El contacto_id es obligatorio y debe ser un número.';
        }
    }

    if ($d['direccion'] === '') {
        $errores['direccion'] = 'La dirección es obligatoria.';
    } elseif (mb_strlen($d['direccion']) > 200) {
        $errores['direccion'] = 'La dirección admite máximo 200 caracteres.';
    }

    if ($d['ciudad'] === '') {
        $errores['ciudad'] = 'La ciudad es obligatoria.';
    } elseif (mb_strlen($d['ciudad']) > 100) {
        $errores['ciudad'] = 'La ciudad admite máximo 100 caracteres.';
    }

    if ($d['departamento'] === '') {
        $errores['departamento'] = 'El departamento es obligatorio.';
    } elseif (mb_strlen($d['departamento']) > 100) {
        $errores['departamento'] = 'El departamento admite máximo 100 caracteres.';
    }

    if ($d['pais'] === '') {
        $errores['pais'] = 'El país es obligatorio.';
    } elseif (mb_strlen($d['pais']) > 100) {
        $errores['pais'] = 'El país admite máximo 100 caracteres.';
    }

    if (!in_array($d['tipo'], TIPOS_DIRECCION, true)) {
        $errores['tipo'] = 'El tipo debe ser: ' . implode(', ', TIPOS_DIRECCION) . '.';
    }

    if ($errores) {
        responder(400, ['ok' => false, 'mensaje' => 'Hay datos inválidos.', 'errores' => $errores]);
    }
    return $d;
}

// Envía la respuesta al cliente
function responder($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Lee los datos que mandó el cliente
function leer_entrada()
{
    $texto = file_get_contents('php://input');

    if (trim($texto) === '') {
        return $_POST;
    }
    $datos = json_decode($texto, true);
    if (is_array($datos)) {
        return $datos;
    }
    if (!empty($_POST)) {
        return $_POST;
    }
    responder(400, ['ok' => false, 'mensaje' => 'El cuerpo de la petición debe ser JSON válido.']);
}

// Solo deja pasar si la llave es correcta
function requerir_api_key()
{
    $recibida = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if (!hash_equals(API_KEY, $recibida)) {
        responder(401, ['ok' => false, 'mensaje' => 'API key inválida o ausente. Envíela en la cabecera X-API-KEY.']);
    }
}

function exigir_id($id)
{
    if ($id <= 0) {
        responder(400, ['ok' => false, 'mensaje' => 'Falta el id en la URL, por ejemplo: ?id=1']);
    }
}

// Avisa si hubo un problema con la base de datos
function error_bd($conn)
{
    error_log('Error en la base de datos: ' . mysqli_error($conn));
    responder(500, ['ok' => false, 'mensaje' => 'Error en la base de datos.']);
}
