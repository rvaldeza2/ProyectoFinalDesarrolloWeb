<?php
require __DIR__ . '/../login/sesion.php';
require __DIR__ . '/../../config/conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$id         = (int) ($_GET['id'] ?? 0);
$errores    = [];
$tipos      = ['casa' => 'Casa', 'trabajo' => 'Trabajo', 'otro' => 'Otro'];

// La dirección tiene que ser de un contacto del usuario que inició sesión
$stmt = mysqli_prepare($conn,
    'SELECT d.contacto_id, d.direccion, d.ciudad, d.departamento, d.pais, d.tipo, c.nombre, c.apellido
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['direccion', 'ciudad', 'departamento', 'pais', 'tipo'] as $campo) {
        $d[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if ($d['direccion'] === '') {
        $errores[] = 'La dirección es obligatoria.';
    } elseif (mb_strlen($d['direccion']) > 200) {
        $errores[] = 'La dirección admite máximo 200 caracteres.';
    }

    if ($d['ciudad'] === '') {
        $errores[] = 'La ciudad es obligatoria.';
    } elseif (mb_strlen($d['ciudad']) > 100) {
        $errores[] = 'La ciudad admite máximo 100 caracteres.';
    }

    if ($d['departamento'] === '') {
        $errores[] = 'El departamento es obligatorio.';
    } elseif (mb_strlen($d['departamento']) > 100) {
        $errores[] = 'El departamento admite máximo 100 caracteres.';
    }

    if ($d['pais'] === '') {
        $errores[] = 'El país es obligatorio.';
    } elseif (mb_strlen($d['pais']) > 100) {
        $errores[] = 'El país admite máximo 100 caracteres.';
    }

    if (!isset($tipos[$d['tipo']])) {
        $errores[] = 'Seleccione un tipo de dirección válido.';
    }

    if (!$errores) {
        $stmt = mysqli_prepare($conn,
            'UPDATE direcciones SET direccion = ?, ciudad = ?, departamento = ?, pais = ?, tipo = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'sssssi',
            $d['direccion'], $d['ciudad'], $d['departamento'], $d['pais'], $d['tipo'], $id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['mensaje'] = 'Dirección actualizada correctamente.';
            header('Location: ../contactos/ver.php?id=' . $d['contacto_id']);
            exit;
        }
        $errores[] = 'No se pudo actualizar la dirección.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar dirección</title>
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
    <h1 class="h3 mb-3">Editar dirección de <?= htmlspecialchars($d['nombre'] . ' ' . $d['apellido']) ?></h1>

    <?php foreach ($errores as $error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
        <div class="row">
            <div class="col-12 mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" id="direccion" name="direccion" class="form-control" value="<?= htmlspecialchars($d['direccion']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="ciudad" class="form-label">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" class="form-control" value="<?= htmlspecialchars($d['ciudad']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="departamento" class="form-label">Departamento</label>
                <input type="text" id="departamento" name="departamento" class="form-control" value="<?= htmlspecialchars($d['departamento']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="pais" class="form-label">País</label>
                <input type="text" id="pais" name="pais" class="form-control" value="<?= htmlspecialchars($d['pais']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="tipo" class="form-label">Tipo</label>
                <select id="tipo" name="tipo" class="form-select">
                    <?php foreach ($tipos as $valor => $texto): ?>
                        <option value="<?= $valor ?>" <?= $d['tipo'] === $valor ? 'selected' : '' ?>><?= $texto ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
        <a href="../contactos/ver.php?id=<?= $d['contacto_id'] ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

</body>
</html>
