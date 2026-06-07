<?php
session_start();
include "DBConnection.php";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    exit("erro");
}

$IDcri = intval($_POST['IDcri']);
$refeicao = $_POST['refeicao'];
$valor = intval($_POST['valor']);
$data = $_POST['data'];

// Verificar se já existe registo
$existe = mysqli_fetch_assoc(mysqli_query($link, "
    SELECT IDref FROM refeicao_crianca
    WHERE IDcri = $IDcri AND data = '$data'
"));

if ($existe) {
    // Atualizar
    mysqli_query($link, "
        UPDATE refeicao_crianca
        SET $refeicao = $valor
        WHERE IDcri = $IDcri AND data = '$data'
    ");
} else {
    // Criar novo registo
    mysqli_query($link, "
        INSERT INTO refeicao_crianca (IDcri, data, $refeicao)
        VALUES ($IDcri, '$data', $valor)
    ");
}

echo "ok";
