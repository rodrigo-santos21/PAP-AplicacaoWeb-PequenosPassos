<?php
session_start();
include("DBConnection.php");

// Apenas funcionários podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit();
}

$IDfunc = $_SESSION['id'];

if (!isset($_GET['id'])) {
    header("Location: criancaspendentes.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Apagar associações
mysqli_query($link, "DELETE FROM crianca_educador WHERE IDcri = $id");

// Apagar criança
mysqli_query($link, "DELETE FROM crianca WHERE IDcri = $id");

// Log
date_default_timezone_set("Europe/Lisbon");
$datahora = date("Y-m-d H:i:s");

mysqli_query($link,
    "INSERT INTO logs (descricao, datahora, IDutl)
     VALUES ('Funcionário $IDfunc rejeitou a criança $id', '$datahora', $IDfunc)"
);

header("Location: criancaspendentes.php?sucesso=rejeitado");
exit();
