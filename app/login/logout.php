<?php
// Borra todo lo de la sesión y regresa al login
session_start();

$_SESSION = [];
session_destroy();

header('Location: login.php?salir=1');
exit;
