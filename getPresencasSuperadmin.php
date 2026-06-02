<?php
session_start();
include "DBConnection.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    echo "[]";
    exit();
}

$id_crianca = $_GET['id_crianca'] ?? null;

if (!$id_crianca) {
    echo "[]";
    exit();
}

$eventos = [];

$sql = "SELECT * FROM presenca 
        WHERE IDcri = $id_crianca 
          AND estado = 1";

$res = mysqli_query($link, $sql);

while ($p = mysqli_fetch_assoc($res)) {

    $data = $p['data'];
    $horaE = $p['horaE'];
    $horaS = $p['horaS'];
    $tipo  = $p['tipo'];

    // FALTA
    if ($tipo === 'falta') {

        $cor = "#dc2626"; // vermelho

        if ($p['justificacao_estado'] === 'pendente') $cor = "#d97706";
        if ($p['justificacao_estado'] === 'aceite')   $cor = "#16a34a";
        if ($p['justificacao_estado'] === 'recusada') $cor = "#7f1d1d";

        $eventos[] = [
            "id"     => $p['IDpre'],
            "title"  => "FALTA",
            "start"  => $data,
            "allDay" => true,
            "color"  => $cor,
            "tipo"   => "falta",
            "justificacao" => $p['justificacao'],
            "estado" => $p['justificacao_estado']
        ];

        continue;
    }

    // PRESENÇA
    $eventos[] = [
        "id"     => $p['IDpre'],
        "title"  => "Entrada: $horaE",
        "start"  => $data . "T" . $horaE,
        "end"    => $horaS ? $data . "T" . $horaS : null,
        "color"  => "#3b82f6",
        "tipo"   => "presenca",
        "horaE"  => substr($horaE, 0, 5),
        "horaS"  => $horaS ? substr($horaS, 0, 5) : null
    ];
}

echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
