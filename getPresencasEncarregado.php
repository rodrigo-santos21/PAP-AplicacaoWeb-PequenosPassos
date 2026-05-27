<?php
session_start();
include "DBConnection.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    echo "[]";
    exit();
}

$id_encarregado = $_SESSION['id'];

$id_crianca = $_GET['id_crianca'] ?? null;

if (!$id_crianca) {
    echo "[]";
    exit();
}

// Verificar se a criança pertence ao encarregado
$sqlCheck = "SELECT IDcri FROM crianca 
             WHERE IDcri = $id_crianca 
               AND IDutl = $id_encarregado 
               AND estado = 1";

$resCheck = mysqli_query($link, $sqlCheck);

if (mysqli_num_rows($resCheck) == 0) {
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

        // Cor depende do estado da justificação
        $cor = "#dc2626"; // vermelho padrão

        if ($p['justificacao_estado'] === 'pendente') $cor = "#d97706"; // laranja
        if ($p['justificacao_estado'] === 'aceite')   $cor = "#16a34a"; // verde
        if ($p['justificacao_estado'] === 'recusada') $cor = "#7f1d1d"; // vermelho escuro

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
        "title"  => "Entrada: $horaE" . ($horaS ? " — Saída: $horaS" : ""),
        "start"  => $data . "T" . $horaE,
        "end"    => $horaS ? $data . "T" . $horaS : null,
        "color"  => "#3b82f6",
        "tipo"   => "presenca",
        "horaE"  => substr($horaE, 0, 5),
        "horaS"  => $horaS ? substr($horaS, 0, 5) : null
    ];
}

echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
