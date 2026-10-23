<?php
require __DIR__ . '/../login/sesion.php';
require __DIR__ . '/../../config/conexion.php';

// Solo se elimina desde el formulario, no abriendo el enlace directo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contactos/index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$id         = (int) ($_POST['id'] ?? 0);

// Se busca el contacto para saber a dónde regresar y que sea del usuario
$stmt = mysqli_prepare($conn,
    'SELECT d.contacto_id
     FROM direcciones d
     JOIN contactos c ON c.id = d.contacto_id
     WHERE d.id = ? AND c.usuario_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $id, $usuario_id);
mysqli_stmt_execute($stmt);
$d = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$d) {
    $_SESSION['error'] = 'Dirección no encontrada.';
    header('Location: ../contactos/index.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'DELETE FROM direcciones WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$_SESSION['mensaje'] = 'Dirección eliminada correctamente.';
header('Location: ../contactos/ver.php?id=' . $d['contacto_id']);
exit;
