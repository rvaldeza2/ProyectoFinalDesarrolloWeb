<?php

header('Content-Type: application/json; charset=utf-8');

// Para que las fechas de PHP salgan con la hora de Guatemala
date_default_timezone_set('America/Guatemala');

const USUARIO_ID = 1;

require __DIR__ . '/../config/conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

switch ($metodo) {
    case 'GET':
        if ($id > 0) {
            ver_contacto($conn, $id);
        } else {
            listar_contactos($conn, trim($_GET['buscar'] ?? ''));
        }
        break;

    case 'POST':
        requerir_api_key();
        crear_contacto($conn, leer_entrada());
        break;

    case 'PUT':
        requerir_api_key();
        exigir_id($id);
        actualizar_contacto($conn, $id, leer_entrada());
        break;

    case 'DELETE':
        requerir_api_key();
        exigir_id($id);
        eliminar_contacto($conn, $id);
        break;

    default:
        responder(405, ['ok' => false, 'mensaje' => 'Método no permitido. Use GET, POST, PUT o DELETE.']);
}

function ver_contacto($conn, $id)
{
    $stmt = mysqli_prepare($conn,
        'SELECT id, usuario_id, nombre, apellido, email, empresa, fecha_nacimiento, notas, creado_en, actualizado_en
         FROM contactos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$fila) {
        responder(404, ['ok' => false, 'mensaje' => 'Contacto no encontrado.']);
    }

    $stmt = mysqli_prepare($conn, 'SELECT id, numero, tipo FROM telefonos WHERE contacto_id = ? ORDER BY id');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $fila['telefonos'] = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    $stmt = mysqli_prepare($conn,
        'SELECT id, direccion, ciudad, departamento, pais, tipo FROM direcciones WHERE contacto_id = ? ORDER BY id');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $fila['direcciones'] = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    responder(200, ['ok' => true, 'datos' => $fila]);
}

// Devuelve todos los contactos o los que coinciden con la búsqueda
function listar_contactos($conn, $buscar)
{
    $stmt = mysqli_prepare($conn,
        'SELECT id, usuario_id, nombre, apellido, email, empresa, fecha_nacimiento, notas, creado_en, actualizado_en
         FROM contactos
         WHERE CONCAT_WS(\' \', nombre, apellido) LIKE ? OR email LIKE ? OR empresa LIKE ?
         ORDER BY nombre, apellido');
    $like = '%' . $buscar . '%';
    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $filas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    responder(200, ['ok' => true, 'total' => count($filas), 'datos' => $filas]);
}

function crear_contacto($conn, $entrada)
{
    $c = validar_contacto($entrada);

    $stmt = mysqli_prepare($conn,
        'INSERT INTO contactos (usuario_id, nombre, apellido, email, empresa, fecha_nacimiento, notas, creado_en, actualizado_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $usuario_id = USUARIO_ID;
    mysqli_stmt_bind_param($stmt, 'issssss', $usuario_id,
        $c['nombre'], $c['apellido'], $c['email'], $c['empresa'], $c['fecha_nacimiento'], $c['notas']);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    $nuevoId = mysqli_insert_id($conn);
    responder(201, ['ok' => true, 'mensaje' => 'Contacto creado.', 'datos' => ['id' => $nuevoId] + $c]);
}

function actualizar_contacto($conn, $id, $entrada)
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM contactos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        responder(404, ['ok' => false, 'mensaje' => 'Contacto no encontrado.']);
    }

    $c = validar_contacto($entrada);

    $stmt = mysqli_prepare($conn,
        'UPDATE contactos
         SET nombre = ?, apellido = ?, email = ?, empresa = ?, fecha_nacimiento = ?, notas = ?, actualizado_en = NOW()
         WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ssssssi',
        $c['nombre'], $c['apellido'], $c['email'], $c['empresa'], $c['fecha_nacimiento'], $c['notas'], $id);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    responder(200, ['ok' => true, 'mensaje' => 'Contacto actualizado.', 'datos' => ['id' => $id] + $c]);
}

// También se borran sus teléfonos y direcciones
function eliminar_contacto($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'DELETE FROM contactos WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        error_bd($conn);
    }

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        responder(404, ['ok' => false, 'mensaje' => 'Contacto no encontrado.']);
    }
    responder(200, ['ok' => true, 'mensaje' => 'Contacto eliminado, junto con sus teléfonos y direcciones.']);
}

// Revisa que los datos vengan completos y correctos
function validar_contacto($entrada)
{
    $c = [
        'nombre'           => trim((string) ($entrada['nombre'] ?? '')),
        'apellido'         => trim((string) ($entrada['apellido'] ?? '')),
        'email'            => trim((string) ($entrada['email'] ?? '')),
        'empresa'          => trim((string) ($entrada['empresa'] ?? '')),
        'fecha_nacimiento' => trim((string) ($entrada['fecha_nacimiento'] ?? '')),
        'notas'            => trim((string) ($entrada['notas'] ?? '')),
    ];
    $errores = [];

    if ($c['nombre'] === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($c['nombre']) > 100) {
        $errores['nombre'] = 'El nombre admite máximo 100 caracteres.';
    }

    if ($c['apellido'] === '') {
        $errores['apellido'] = 'El apellido es obligatorio.';
    } elseif (mb_strlen($c['apellido']) > 100) {
        $errores['apellido'] = 'El apellido admite máximo 100 caracteres.';
    }

    if (!filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El correo no es válido.';
    } elseif (mb_strlen($c['email']) > 150) {
        $errores['email'] = 'El correo admite máximo 150 caracteres.';
    }

    if ($c['empresa'] === '') {
        $errores['empresa'] = 'La empresa es obligatoria.';
    } elseif (mb_strlen($c['empresa']) > 100) {
        $errores['empresa'] = 'La empresa admite máximo 100 caracteres.';
    }

    if ($c['notas'] === '') {
        $errores['notas'] = 'Las notas son obligatorias.';
    } elseif (mb_strlen($c['notas']) > 1000) {
        $errores['notas'] = 'Las notas admiten máximo 1000 caracteres.';
    }

    // La fecha tiene que ser real y no puede ser del futuro
    $fecha = DateTime::createFromFormat('Y-m-d', $c['fecha_nacimiento']);
    if ($c['fecha_nacimiento'] === '') {
        $errores['fecha_nacimiento'] = 'La fecha de nacimiento es obligatoria.';
    } elseif (!$fecha || $fecha->format('Y-m-d') !== $c['fecha_nacimiento']) {
        $errores['fecha_nacimiento'] = 'La fecha de nacimiento no es válida (formato AAAA-MM-DD).';
    } elseif ($c['fecha_nacimiento'] > date('Y-m-d') || $c['fecha_nacimiento'] < '1900-01-01') {
        $errores['fecha_nacimiento'] = 'La fecha de nacimiento debe estar entre 1900 y hoy.';
    }

    if ($errores) {
        responder(400, ['ok' => false, 'mensaje' => 'Hay datos inválidos.', 'errores' => $errores]);
    }
    return $c;
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
