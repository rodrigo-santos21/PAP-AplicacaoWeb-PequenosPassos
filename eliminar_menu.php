<?php
session_start();
include "DBConnection.php";

// Apenas admin e superadmin podem eliminar
if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['administrador', 'superadmin'])) {
    header("Location: index.php?erro=permissao");
    exit;
}

if (!isset($_POST['IDmenu'])) {
    header("Location: listarrefeicao.php?erro=eliminar");
    exit;
}

$IDmenu = intval($_POST['IDmenu']);

// Soft delete
mysqli_query($link, "
    UPDATE menu_semana
    SET estado = 0
    WHERE IDmenu = $IDmenu
");

// Log
date_default_timezone_set("Europe/Lisbon");
$fdatahora = date("Y-m-d H:i:s");

mysqli_query($link, "
    INSERT INTO logs (descricao, datahora, IDutl)
    VALUES ('Menu semanal eliminado', '$fdatahora', '{$_SESSION['id']}')
");

// Buscar a data do menu antes de eliminar
$res = mysqli_query($link, "SELECT data FROM menu_semana WHERE IDmenu = $IDmenu");
$dados = mysqli_fetch_assoc($res);
$dataOriginal = $dados['data'] ?? date("Y-m-d");

// Redirecionar mantendo a semana correta
header("Location: listarrefeicao.php?sucesso=eliminado&data=" . urlencode($dataOriginal));
exit;

