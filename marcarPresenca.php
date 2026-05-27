<?php
session_start();
include "DBConnection.php";

if ($_SESSION['tipo'] !== 'educador') {
    echo "erro:sem_permissao";
    exit();
}

$id_crianca = $_POST['id_crianca'] ?? null;
$data = $_POST['data'] ?? null;

$horaE = ($_POST['horaE'] !== "") ? $_POST['horaE'] : null;
$horaS = ($_POST['horaS'] !== "") ? $_POST['horaS'] : null;

if (!$id_crianca || !$data) {
    echo "erro:campos_invalidos";
    exit();
}

// Verificar se a criança pertence ao educador
$sql = "SELECT * FROM crianca_educador 
        WHERE IDcri = $id_crianca 
        AND IDedu = {$_SESSION['IDedu']} 
        AND estado = 1";

$res = mysqli_query($link, $sql);

if (mysqli_num_rows($res) == 0) {
    echo "erro:crianca_invalida";
    exit();
}

// Inserir presença
$sql = "INSERT INTO presenca (`data`, horaE, horaS, IDcri)
        VALUES (
            '$data',
            " . ($horaE ? "'$horaE'" : "NULL") . ",
            " . ($horaS ? "'$horaS'" : "NULL") . ",
            $id_crianca
        )";

if (mysqli_query($link, $sql)) {
    echo "ok";
} else {
    echo "erro_sql: " . mysqli_error($link);
}
