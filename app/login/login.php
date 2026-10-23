<?php
session_start();

// Si ya inició sesión, no tiene que volver a entrar
if (isset($_SESSION['usuario_id'])) {
    header('Location: ../contactos/index.php');
    exit;
}

$email   = '';
$errores = [];
$mensaje = isset($_GET['salir']) ? 'Cerró sesión correctamente.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '') {
        $errores[] = 'El correo es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido.';
    }
    if ($password === '') {
        $errores[] = 'La contraseña es obligatoria.';
    }

    if (!$errores) {
        require __DIR__ . '/../../config/conexion.php';

        $stmt = mysqli_prepare($conn, 'SELECT id, nombre, password FROM usuarios WHERE email = ?');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        // Compara la contraseña escrita con la que está guardada con password_hash
        if ($usuario && password_verify($password, $usuario['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header('Location: ../contactos/index.php');
            exit;
        }

        $errores[] = 'El correo o la contraseña son incorrectos.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-4 text-center">Iniciar sesión</h1>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
                    <?php endif; ?>

                    <?php foreach ($errores as $error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>

                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
