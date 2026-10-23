<?php
require __DIR__ . '/../login/sesion.php';
require __DIR__ . '/../../config/conexion.php';

// Solo se elimina desde el formulario, no abriendo el enlace directo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$id         = (int) ($_POST['id'] ?? 0);

// Los teléfonos y direcciones se borran solos por el ON DELETE CASCADE
$stmt = mysqli_prepare($conn, 'DELETE FROM contactos WHERE id = ? AND usuario_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $id, $usuario_id);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    $_SESSION['mensaje'] = 'Contacto eliminado correctamente.';
} else {
    $_SESSION['error'] = 'Contacto no encontrado.';
}

header('Location: index.php');
exit;
