<?php
require __DIR__ . '/../login/sesion.php';
require __DIR__ . '/../../config/conexion.php';

$usuario_id = $_SESSION['usuario_id'];
$buscar     = trim((string) ($_GET['buscar'] ?? ''));

// Solo los contactos del usuario que inició sesión, filtrados por la búsqueda
$stmt = mysqli_prepare($conn,
    'SELECT id, nombre, apellido, email, empresa
     FROM contactos
     WHERE usuario_id = ?
       AND (CONCAT_WS(\' \', nombre, apellido) LIKE ? OR email LIKE ? OR empresa LIKE ?)
     ORDER BY nombre, apellido');
$like = '%' . $buscar . '%';
mysqli_stmt_bind_param($stmt, 'isss', $usuario_id, $like, $like, $like);
mysqli_stmt_execute($stmt);
$contactos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$tipo_cambio = tipo_cambio_banguat();

// Consulta el tipo de cambio del día al Banco de Guatemala
function tipo_cambio_banguat()
{
    $xml = '<?xml version="1.0" encoding="utf-8"?>'
        . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
        . '<soap:Body><TipoCambioDia xmlns="http://www.banguat.gob.gt/variables/ws/" /></soap:Body>'
        . '</soap:Envelope>';

    $ch = curl_init('https://www.banguat.gob.gt/variables/ws/TipoCambio.asmx');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: "http://www.banguat.gob.gt/variables/ws/TipoCambioDia"',
    ]);
    $respuesta = curl_exec($ch);

    if ($respuesta && preg_match('/<fecha>(.*?)<\/fecha>\s*<referencia>(.*?)<\/referencia>/s', $respuesta, $m)) {
        return ['fecha' => $m[1], 'referencia' => (float) $m[2]];
    }
    return null;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contactos</title>
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

    <?php if ($tipo_cambio): ?>
        <div class="alert alert-info">
            Tipo de cambio Banguat (<?= htmlspecialchars($tipo_cambio['fecha']) ?>):
            <strong>US$ 1.00 = Q <?= number_format($tipo_cambio['referencia'], 5) ?></strong>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary">No se pudo consultar el tipo de cambio del Banguat.</div>
    <?php endif; ?>

    <h1 class="h3 mb-3">Contactos</h1>

    <form method="get" class="row g-2 mb-3">
        <div class="col">
            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, correo o empresa"
                   value="<?= htmlspecialchars($buscar) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-secondary">Buscar</button>
        </div>
        <div class="col-auto">
            <a href="crear.php" class="btn btn-primary">Nuevo contacto</a>
        </div>
    </form>

    <?php if (!$contactos): ?>
        <div class="alert alert-warning">No se encontraron contactos.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Empresa</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contactos as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['empresa']) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="ver.php?id=<?= $c['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                                <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <form method="post" action="eliminar.php" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este contacto con sus teléfonos y direcciones?')">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
