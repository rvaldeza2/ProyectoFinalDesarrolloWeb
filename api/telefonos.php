<?php

header('Content-Type: application/json; charset=utf-8');

// Para que las fechas de PHP salgan con la hora de Guatemala
date_default_timezone_set('America/Guatemala');

// Tipos que se aceptan
const TIPOS_TELEFONO = ['movil', 'casa', 'trabajo', 'otro'];

require __DIR__ . '/../config/conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

switch ($metodo) {
    case 'GET':
        if ($id > 0) {
            ver_telefono($conn, $id);
        } else {
            listar_telefonos($conn, (int) ($_GET['contacto_id'] ?? 0));
        }
        break;

    case 'POST':
        requerir_api_key();
        crear_telefono($conn, leer_entrada());
        break;

    case 'PUT':
        requerir_api_key();
        exigir_id($id);
        actualizar_telefono($conn, $id, leer_entrada());
        break;

    case 'DELETE':
        requerir_api_key();
        exigir_id($id);
        eliminar_telefono($conn, $id);
        break;

    default:
        responder(405, ['ok' => false, 'mensaje' => 'Método no permitido. Use GET, POST, PUT o DELETE.']);
}

function ver_telefono($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'SELECT id, contacto_id, numero, tipo FROM telefonos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$fila) {
        responder(404, ['ok' => false, 'mensaje' => 'Teléfono no encontrado.']);
    }
    responder(200, ['ok' => true, 'datos' => $fila]);
}

// Devuelve todos los teléfonos o solo los de un contacto
function listar_telefonos($conn, $contacto_id)
{
    if ($contacto_id > 0) {
        $stmt = mysqli_prepare($conn, 'SELECT id, contacto_id, numero, tipo FROM telefonos WHERE contacto_id = ? ORDER BY id');
        mysqli_stmt_bind_param($stmt, 'i', $contacto_id);
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, contacto_id, numero, tipo FROM telefonos ORDER BY contacto_id, id');
    }
    mysqli_stmt_execute($stmt);
    $filas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    responder(200, ['ok' => true, 'total' => count($filas), 'datos' => $filas]);
}

function crear_telefono($conn, $entrada)
{
    $t = validar_telefono($entrada, true);

    $stmt = mysqli_prepare($conn, 'SELECT id FROM contactos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $t['contacto_id']);
    mysqli_stmt_execute($stmt);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        responder(404, ['ok' => false, 'mensaje' => 'El contacto no existe.']);
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO telefonos (contacto_id, numero, tipo) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iss', $t['contacto_id'], $t['numero'], $t['tipo']);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    $nuevoId = mysqli_insert_id($conn);
    responder(201, ['ok' => true, 'mensaje' => 'Teléfono creado.', 'datos' => ['id' => $nuevoId] + $t]);
}

function actualizar_telefono($conn, $id, $entrada)
{
    $stmt = mysqli_prepare($conn, 'SELECT contacto_id FROM telefonos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$fila) {
        responder(404, ['ok' => false, 'mensaje' => 'Teléfono no encontrado.']);
    }

    $t = validar_telefono($entrada, false);

    $stmt = mysqli_prepare($conn, 'UPDATE telefonos SET numero = ?, tipo = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ssi', $t['numero'], $t['tipo'], $id);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    responder(200, ['ok' => true, 'mensaje' => 'Teléfono actualizado.',
        'datos' => ['id' => $id, 'contacto_id' => $fila['contacto_id']] + $t]);
}

function eliminar_telefono($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'DELETE FROM telefonos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        responder(404, ['ok' => false, 'mensaje' => 'Teléfono no encontrado.']);
    }
    responder(200, ['ok' => true, 'mensaje' => 'Teléfono eliminado.']);
}

// Revisa que los datos vengan completos y correctos
function validar_telefono($entrada, $con_contacto)
{
    $t = [
        'numero' => trim((string) ($entrada['numero'] ?? '')),
        'tipo'   => trim((string) ($entrada['tipo'] ?? '')),
    ];
    $errores = [];

    if ($con_contacto) {
        $t['contacto_id'] = (int) ($entrada['contacto_id'] ?? 0);
        if ($t['contacto_id'] <= 0) {
            $errores['contacto_id'] = 'El contacto_id es obligatorio y debe ser un número.';
        }
    }

    // Solo números, espacios, +, - y paréntesis
    if ($t['numero'] === '') {
        $errores['numero'] = 'El número es obligatorio.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $t['numero'])) {
        $errores['numero'] = 'El número solo puede tener dígitos, espacios, +, - y paréntesis (de 7 a 20).';
    }

    if (!in_array($t['tipo'], TIPOS_TELEFONO, true)) {
        $errores['tipo'] = 'El tipo debe ser: ' . implode(', ', TIPOS_TELEFONO) . '.';
    }

    if ($errores) {
        responder(400, ['ok' => false, 'mensaje' => 'Hay datos inválidos.', 'errores' => $errores]);
    }
    return $t;
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
