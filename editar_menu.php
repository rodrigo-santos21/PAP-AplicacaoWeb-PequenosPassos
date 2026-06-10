<?php
session_start();
include "DBConnection.php";

if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['administrador', 'superadmin'])) {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDmenu = intval($_POST['IDmenu']);
$lanche_manha = mysqli_real_escape_string($link, $_POST['lanche_manha']);
$almoco = mysqli_real_escape_string($link, $_POST['almoco']);
$lanche_tarde = mysqli_real_escape_string($link, $_POST['lanche_tarde']);

mysqli_query($link, "
    UPDATE menu_semana
    SET lanche_manha = '$lanche_manha',
        almoco = '$almoco',
        lanche_tarde = '$lanche_tarde'
    WHERE IDmenu = $IDmenu
");

// REDIRECT COM TOAST DE SUCESSO
header("Location: listarrefeicao.php?sucesso=editado&data=" . urlencode($_POST['data_original']));
exit;
