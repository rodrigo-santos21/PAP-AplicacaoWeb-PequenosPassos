<?php
session_start();
include "DBConnection.php";

// Só regista log se existir um utilizador autenticado
if (isset($_SESSION['id'])) {

    $IDutilizador = $_SESSION['id'];

    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");

    // Registar log de fim de sessão
    mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                         VALUES ('Fim de sessão', '$fdatahora', '$IDutilizador')");
}

// Terminar sessão
session_unset();
session_destroy();

// Redirecionar para o login
header("Location: index.php");
exit();
?>