<?php
// Se incluye al inicio de cada página protegida: si no han iniciado sesión, los manda al login
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login/login.php');
    exit;
}
