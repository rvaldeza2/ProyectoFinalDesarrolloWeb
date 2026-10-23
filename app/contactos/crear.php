<?php
require __DIR__ . '/../login/sesion.php';
require __DIR__ . '/../../config/conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$errores    = [];
$c = ['nombre' => '', 'apellido' => '', 'email' => '', 'empresa' => '', 'fecha_nacimiento' => '', 'notas' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($c as $campo => $valor) {
        $c[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if ($c['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($c['nombre']) > 100) {
        $errores[] = 'El nombre admite máximo 100 caracteres.';
    }

    if ($c['apellido'] === '') {
        $errores[] = 'El apellido es obligatorio.';
    } elseif (mb_strlen($c['apellido']) > 100) {
        $errores[] = 'El apellido admite máximo 100 caracteres.';
    }

    if (!filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido.';
    } elseif (mb_strlen($c['email']) > 150) {
        $errores[] = 'El correo admite máximo 150 caracteres.';
    }

    if ($c['empresa'] === '') {
        $errores[] = 'La empresa es obligatoria.';
    } elseif (mb_strlen($c['empresa']) > 100) {
        $errores[] = 'La empresa admite máximo 100 caracteres.';
    }

    // La fecha tiene que ser real y no puede ser del futuro
    $fecha = DateTime::createFromFormat('Y-m-d', $c['fecha_nacimiento']);
    if ($c['fecha_nacimiento'] === '') {
        $errores[] = 'La fecha de nacimiento es obligatoria.';
    } elseif (!$fecha || $fecha->format('Y-m-d') !== $c['fecha_nacimiento']) {
        $errores[] = 'La fecha de nacimiento no es válida.';
    } elseif ($c['fecha_nacimiento'] > date('Y-m-d') || $c['fecha_nacimiento'] < '1900-01-01') {
        $errores[] = 'La fecha de nacimiento debe estar entre 1900 y hoy.';
    }

    if ($c['notas'] === '') {
        $errores[] = 'Las notas son obligatorias.';
    } elseif (mb_strlen($c['notas']) > 1000) {
        $errores[] = 'Las notas admiten máximo 1000 caracteres.';
    }

    if (!$errores) {
        $stmt = mysqli_prepare($conn,
            'INSERT INTO contactos (usuario_id, nombre, apellido, email, empresa, fecha_nacimiento, notas, creado_en, actualizado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        mysqli_stmt_bind_param($stmt, 'issssss', $usuario_id,
            $c['nombre'], $c['apellido'], $c['email'], $c['empresa'], $c['fecha_nacimiento'], $c['notas']);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['mensaje'] = 'Contacto creado correctamente.';
            header('Location: ver.php?id=' . mysqli_insert_id($conn));
            exit;
        }
        $errores[] = 'No se pudo guardar el contacto.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo contacto</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="../contactos/index.php">Agenda de Contactos</a>
        <div>
            <span class="text-white me-2"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
            <a href="../login/logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1 class="h3 mb-3">Nuevo contacto</h1>

    <?php foreach ($errores as $error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($c['nombre']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" id="apellido" name="apellido" class="form-control" value="<?= htmlspecialchars($c['apellido']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Correo</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($c['email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="empresa" class="form-label">Empresa</label>
                <input type="text" id="empresa" name="empresa" class="form-control" value="<?= htmlspecialchars($c['empresa']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($c['fecha_nacimiento']) ?>">
            </div>
            <div class="col-12 mb-3">
                <label for="notas" class="form-label">Notas</label>
                <textarea id="notas" name="notas" class="form-control" rows="3"><?= htmlspecialchars($c['notas']) ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

</body>
</html>
