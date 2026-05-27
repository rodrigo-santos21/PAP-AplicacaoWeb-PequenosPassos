<?php

session_start();
include "DBConnection.php";

header('Content-Type: application/json; charset=utf-8');

// Se não houver sessão, devolver JSON vazio
if (!isset($_SESSION['tipo'])) {
    echo "[]";
    exit();
}

// Verificar se veio o ID da criança
$id_crianca = $_GET['id_crianca'] ?? null;

if (!$id_crianca) {
    echo "[]";
    exit();
}

$eventos = [];

// Buscar presenças/faltas APENAS da criança selecionada e ativas
$sql = "SELECT IDpre, data, horaE, horaS, tipo 
        FROM presenca 
        WHERE IDcri = $id_crianca 
          AND estado = 1";

$res = mysqli_query($link, $sql);

while ($p = mysqli_fetch_assoc($res)) {

    $data  = $p['data'];
    $horaE = $p['horaE'];
    $horaS = $p['horaS'];
    $tipo  = $p['tipo'];

    // Buscar nome da criança
    $sql2 = "SELECT nome FROM crianca WHERE IDcri = $id_crianca AND estado = 1";
    $res2 = mysqli_query($link, $sql2);
    $crianca = mysqli_fetch_assoc($res2);

    if (!$crianca) continue;

    $nome = $crianca['nome'];

    // FALTA (sem horas, dia inteiro, a vermelho)
    if ($tipo === 'falta') {
        $eventos[] = [
            "id"     => $p['IDpre'],
            "title"  => "$nome | FALTA",
            "start"  => $data,
            "allDay" => true,
            "color"  => "#dc2626",
            "horaE"  => null,
            "horaS"  => null
        ];
        continue;
    }

    // garantir formato HH:MM:SS nas presenças
    $horaE = $horaE ? substr($horaE, 0, 8) : null;
    $horaS = $horaS ? substr($horaS, 0, 8) : null;

    if ($horaE && $horaS) {
        $eventos[] = [
            "id"    => $p['IDpre'],
            "title" => "$nome | Entrada: $horaE — Saída: $horaS",
            "start" => $data . "T" . $horaE,
            "end"   => $data . "T" . $horaS,
            "color" => "#16a34a",
            "horaE" => substr($horaE, 0, 5),
            "horaS" => substr($horaS, 0, 5)
        ];
    }
    else if ($horaE) {
        $eventos[] = [
            "id"    => $p['IDpre'],
            "title" => "$nome | Entrada: $horaE",
            "start" => $data . "T" . $horaE,
            "color" => "#facc15",
            "horaE" => substr($horaE, 0, 5),
            "horaS" => null
        ];
    }
    else {
        $eventos[] = [
            "id"     => $p['IDpre'],
            "title"  => "$nome | Presença",
            "start"  => $data,
            "allDay" => true,
            "color"  => "#3b82f6",
            "horaE"  => null,
            "horaS"  => null
        ];
    }
}

echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
