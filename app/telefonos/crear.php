<?php
require __DIR__ . '/../login/sesion.php';
require __DIR__ . '/../../config/conexion.php';

$usuario_id  = $_SESSION['usuario_id'];
$contacto_id = (int) ($_GET['contacto_id'] ?? 0);
$errores     = [];
$t           = ['numero' => '', 'tipo' => 'movil'];
$tipos       = ['movil' => 'Móvil', 'casa' => 'Casa', 'trabajo' => 'Trabajo', 'otro' => 'Otro'];

// El contacto tiene que ser del usuario que inició sesión
$stmt = mysqli_prepare($conn, 'SELECT nombre, apellido FROM contactos WHERE id = ? AND usuario_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $contacto_id, $usuario_id);
mysqli_stmt_execute($stmt);
$contacto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$contacto) {
    $_SESSION['error'] = 'Contacto no encontrado.';
    header('Location: ../contactos/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t['numero'] = trim((string) ($_POST['numero'] ?? ''));
    $t['tipo']   = trim((string) ($_POST['tipo'] ?? ''));

    // Solo números, espacios, +, - y paréntesis
    if ($t['numero'] === '') {
        $errores[] = 'El número es obligatorio.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $t['numero'])) {
        $errores[] = 'El número solo puede tener dígitos, espacios, +, - y paréntesis (de 7 a 20).';
    }

    if (!isset($tipos[$t['tipo']])) {
        $errores[] = 'Seleccione un tipo de teléfono válido.';
    }

    if (!$errores) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO telefonos (contacto_id, numero, tipo) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iss', $contacto_id, $t['numero'], $t['tipo']);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['mensaje'] = 'Teléfono agregado correctamente.';
            header('Location: ../contactos/ver.php?id=' . $contacto_id);
            exit;
        }
        $errores[] = 'No se pudo guardar el teléfono.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agregar teléfono</title>
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
    <h1 class="h3 mb-3">Agregar teléfono a <?= htmlspecialchars($contacto['nombre'] . ' ' . $contacto['apellido']) ?></h1>

    <?php foreach ($errores as $error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="numero" class="form-label">Número</label>
                <input type="text" id="numero" name="numero" class="form-control" value="<?= htmlspecialchars($t['numero']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="tipo" class="form-label">Tipo</label>
                <select id="tipo" name="tipo" class="form-select">
                    <?php foreach ($tipos as $valor => $texto): ?>
                        <option value="<?= $valor ?>" <?= $t['tipo'] === $valor ? 'selected' : '' ?>><?= $texto ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="../contactos/ver.php?id=<?= $contacto_id ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

</body>
</html>
