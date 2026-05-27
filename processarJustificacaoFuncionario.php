<?php
session_start();
include "DBConnection.php";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit();
}

$idPres = $_POST['idPres'];
$acao = $_POST['acao'];

if ($acao === "aceitar") {
    $sql = "UPDATE presenca 
            SET justificacao_estado = 'aceite'
            WHERE IDpre = $idPres";
}

if ($acao === "recusar") {
    $sql = "UPDATE presenca 
            SET justificacao_estado = 'recusada'
            WHERE IDpre = $idPres";
}

mysqli_query($link, $sql);

// Redirecionar de volta
$sala = $_POST['sala'];
$crianca = $_POST['crianca'];

header("Location: funcionario_presencas.php?sala=$sala&crianca=$crianca");
exit();
