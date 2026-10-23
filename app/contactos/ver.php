<?php
require __DIR__ . '/../login/sesion.php';
require __DIR__ . '/../../config/conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$id         = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn,
    'SELECT id, nombre, apellido, email, empresa, fecha_nacimiento, notas
     FROM contactos WHERE id = ? AND usuario_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $id, $usuario_id);
mysqli_stmt_execute($stmt);
$contacto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$contacto) {
    $_SESSION['error'] = 'Contacto no encontrado.';
    header('Location: index.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT id, numero, tipo FROM telefonos WHERE contacto_id = ? ORDER BY id');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$telefonos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn,
    'SELECT id, direccion, ciudad, departamento, pais, tipo FROM direcciones WHERE contacto_id = ? ORDER BY id');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$direcciones = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$tipos = ['movil' => 'Móvil', 'casa' => 'Casa', 'trabajo' => 'Trabajo', 'otro' => 'Otro'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ver contacto</title>
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

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['mensaje']) ?></div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <a href="index.php" class="btn btn-secondary btn-sm mb-3">Volver</a>

    <div class="card mb-4">
        <div class="card-body">
            <h1 class="h3"><?= htmlspecialchars($contacto['nombre'] . ' ' . $contacto['apellido']) ?></h1>
            <p class="mb-1"><strong>Correo:</strong> <?= htmlspecialchars($contacto['email']) ?></p>
            <p class="mb-1"><strong>Empresa:</strong> <?= htmlspecialchars($contacto['empresa']) ?></p>
            <p class="mb-1"><strong>Fecha de nacimiento:</strong> <?= date('d/m/Y', strtotime($contacto['fecha_nacimiento'])) ?></p>
            <p><strong>Notas:</strong> <?= htmlspecialchars($contacto['notas']) ?></p>
            <a href="editar.php?id=<?= $contacto['id'] ?>" class="btn btn-warning btn-sm">Editar contacto</a>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h4 mb-0">Teléfonos</h2>
        <a href="../telefonos/crear.php?contacto_id=<?= $contacto['id'] ?>" class="btn btn-primary btn-sm">Agregar teléfono</a>
    </div>

    <?php if (!$telefonos): ?>
        <p class="text-muted">Este contacto no tiene teléfonos.</p>
    <?php else: ?>
        <div class="table-responsive mb-4">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($telefonos as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['numero']) ?></td>
                            <td><?= htmlspecialchars($tipos[$t['tipo']] ?? $t['tipo']) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="../telefonos/editar.php?id=<?= $t['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <form method="post" action="../telefonos/eliminar.php" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este teléfono?')">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h4 mb-0">Direcciones</h2>
        <a href="../direcciones/crear.php?contacto_id=<?= $contacto['id'] ?>" class="btn btn-primary btn-sm">Agregar dirección</a>
    </div>

    <?php if (!$direcciones): ?>
        <p class="text-muted">Este contacto no tiene direcciones.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Dirección</th>
                        <th>Ciudad</th>
                        <th>Departamento</th>
                        <th>País</th>
                        <th>Tipo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($direcciones as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['direccion']) ?></td>
                            <td><?= htmlspecialchars($d['ciudad']) ?></td>
                            <td><?= htmlspecialchars($d['departamento']) ?></td>
                            <td><?= htmlspecialchars($d['pais']) ?></td>
                            <td><?= htmlspecialchars($tipos[$d['tipo']] ?? $d['tipo']) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="../direcciones/editar.php?id=<?= $d['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <form method="post" action="../direcciones/eliminar.php" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar esta dirección?')">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
