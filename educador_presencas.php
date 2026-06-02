<?php
session_start();
include "DBConnection.php";

/* ============================================================
   1) VERIFICAR PERMISSÕES E CARREGAR DADOS DO EDUCADOR
   ============================================================ */

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit();
}

$id_utilizador = $_SESSION['id'];

$sqlEdu = "SELECT IDedu FROM educador WHERE IDutl = $id_utilizador AND estado = 1";
$resEdu = mysqli_query($link, $sqlEdu);

if (mysqli_num_rows($resEdu) == 0) {
    exit("Erro: Educador não encontrado.");
}

$dadosEdu = mysqli_fetch_assoc($resEdu);
$id_educador = $dadosEdu['IDedu'];

$_SESSION['IDedu'] = $id_educador;

/* ============================================================
   2) TRATAR PEDIDOS AJAX (EDITAR / REMOVER / FALTA)
   ============================================================ */

if (isset($_POST['acao'])) {

    // EDITAR PRESENÇA
    if ($_POST['acao'] === 'editar') {

        $idPres = intval($_POST['idPres']);
        $horaE = $_POST['horaE'];
        $horaS = $_POST['horaS'];

        // Buscar criança da presença
        $res = mysqli_query($link, "SELECT IDcri, tipo FROM presenca WHERE IDpre = $idPres AND estado = 1");
        $p = mysqli_fetch_assoc($res);

        if (!$p) { echo "ERRO"; exit; }

        // Bloquear edição de faltas
        if ($p['tipo'] === 'falta') { echo "ERRO_FALTA"; exit; }

        $id_crianca = $p['IDcri'];

        // Verificar se pertence ao educador
        $res2 = mysqli_query($link, "
            SELECT 1 FROM crianca_educador 
            WHERE IDcri = $id_crianca AND IDedu = $id_educador AND estado = 1
        ");

        if (mysqli_num_rows($res2) == 0) { echo "ERRO_PERMISSAO"; exit; }

        // Validar horas
        if ($horaE && $horaS && $horaE > $horaS) {
            echo "ERRO_HORAS";
            exit;
        }

        mysqli_query($link, "
            UPDATE presenca 
            SET horaE='$horaE', horaS='$horaS' 
            WHERE IDpre=$idPres
        ");

        echo "OK";
        exit;
    }

    // REMOVER PRESENÇA
    if ($_POST['acao'] === 'remover') {

        $idPres = intval($_POST['idPres']);

        // Buscar criança da presença
        $res = mysqli_query($link, "SELECT IDcri FROM presenca WHERE IDpre = $idPres AND estado = 1");
        $p = mysqli_fetch_assoc($res);

        if (!$p) { echo "ERRO"; exit; }

        $id_crianca = $p['IDcri'];

        // Verificar se pertence ao educador
        $res2 = mysqli_query($link, "
            SELECT 1 FROM crianca_educador 
            WHERE IDcri = $id_crianca AND IDedu = $id_educador AND estado = 1
        ");

        if (mysqli_num_rows($res2) == 0) { echo "ERRO_PERMISSAO"; exit; }

        mysqli_query($link, "UPDATE presenca SET estado = 0 WHERE IDpre = $idPres");

        echo "OK";
        exit;
    }

    // MARCAR FALTA
    if ($_POST['acao'] === 'falta') {

        $id_crianca = intval($_POST['id_crianca']);
        $data = $_POST['data'];

        // Verificar se pertence ao educador
        $res2 = mysqli_query($link, "
            SELECT 1 FROM crianca_educador 
            WHERE IDcri = $id_crianca AND IDedu = $id_educador AND estado = 1
        ");

        if (mysqli_num_rows($res2) == 0) { echo "ERRO_PERMISSAO"; exit; }

        mysqli_query($link, "
            INSERT INTO presenca (IDcri, data, horaE, horaS, tipo, estado)
            VALUES ($id_crianca, '$data', NULL, NULL, 'falta', 1)
        ");

        echo "OK";
        exit;
    }
}

/* ============================================================
   3) BUSCAR CRIANÇAS ASSOCIADAS AO EDUCADOR
   ============================================================ */

$sql = "SELECT * FROM crianca_educador WHERE IDedu = $id_educador AND estado = 1";
$res = mysqli_query($link, $sql);

$criancas = [];
while ($row = mysqli_fetch_assoc($res)) {

    $id_cri = $row['IDcri'];

    $sql2 = "SELECT * FROM crianca WHERE IDcri = $id_cri AND estado = 1";
    $res2 = mysqli_query($link, $sql2);

    if ($c = mysqli_fetch_assoc($res2)) {
        $criancas[] = $c;
    }
}
?>

<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Marcar Presenças (Educador)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="http://localhost/PAP/PAP-AplicacaoWeb-PequenosPassos/assets/fullcalendar/index.global.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">

    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">

        <h2 class="text-2xl font-bold mb-4">Marcar Presenças</h2>
        
        <label class="font-semibold">Escolha a criança:</label>
        <select id="crianca" class="border p-2 rounded w-full mb-6">
            <option value="">-- Selecionar --</option>
            <?php foreach ($criancas as $c): ?>
                <option value="<?= $c['IDcri'] ?>"><?= $c['nome'] ?></option>
            <?php endforeach; ?>
        </select>

        <div id="calendar"></div>

        <a href="educador.php" 
        style="
            display:inline-block;
            padding:10px 18px;
            background:#2563eb;
            color:white;
            text-decoration:none;
            border-radius:6px;
            font-weight:600;
            margin-top:20px;
            font-family:Arial, sans-serif;
        ">
            ← Voltar
        </a>

    </div>

<!-- ============================================================
     MODAL PARA EDITAR / REMOVER PRESENÇA
     ============================================================ -->

<div id="modalEditar" 
     style="
        display:none; 
        position:fixed; 
        top:0; 
        left:0; 
        width:100%; 
        height:100%; 
        background:rgba(0,0,0,0.5); 
        padding-top:100px;
        z-index:9999;
     ">

    <div style="
        background:white; 
        width:300px; 
        margin:auto; 
        padding:20px; 
        border-radius:8px;
        z-index:10000;
        position:relative;
    ">
        <h3 class="text-xl font-bold mb-3">Editar Presença</h3>

        <input type="hidden" id="edit_idPres">

        <label>Hora Entrada:</label>
        <input type="time" id="edit_horaE" class="border p-2 w-full mb-3">

        <label>Hora Saída:</label>
        <input type="time" id="edit_horaS" class="border p-2 w-full mb-3">

        <button onclick="guardarEdicao()" 
                style="background:#16a34a; color:white; padding:8px 12px; border-radius:5px;">
            Guardar
        </button>

        <button onclick="removerPresenca()" 
                style="background:#dc2626; color:white; padding:8px 12px; border-radius:5px;">
            Remover
        </button>

        <button onclick="fecharModal()" 
                style="background:#6b7280; color:white; padding:8px 12px; border-radius:5px;">
            Cancelar
        </button>
    </div>
</div>


<script>

var calendar;

document.addEventListener('DOMContentLoaded', function() {

    var calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        timeZone: 'local',

        events: function(fetchInfo, successCallback, failureCallback) {

            let id_crianca = document.getElementById('crianca').value;

            if (!id_crianca) {
                successCallback([]); 
                return;
            }

            fetch("http://localhost/PAP/PAP-AplicacaoWeb-PequenosPassos/getPresencas.php?id_crianca=" + id_crianca)
                .then(r => r.json())
                .then(data => successCallback(data))
                .catch(err => failureCallback(err));
        },

        eventClick: function(info) {

            // FALTAS NÃO ABREM MODAL
            if (info.event.title.includes("FALTA")) {
                return;
            }

            const idPres = info.event.id;

            const horaE = info.event.extendedProps.horaE || "";
            const horaS = info.event.extendedProps.horaS || "";

            document.getElementById("edit_idPres").value = idPres;
            document.getElementById("edit_horaE").value = horaE;
            document.getElementById("edit_horaS").value = horaS;

            document.getElementById("modalEditar").style.display = "block";
        },

        dateClick: function(info) {

            let id_crianca = document.getElementById('crianca').value;

            if (!id_crianca) {
                alert("Escolha uma criança primeiro");
                return;
            }

            // PERGUNTAR SE É FALTA
            if (confirm("Marcar FALTA para esta data?")) {

                fetch("educador_presencas.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `acao=falta&id_crianca=${id_crianca}&data=${info.dateStr}`
                })
                .then(r => r.text())
                .then(res => {
                    if (res.trim() === "OK") {
                        calendar.refetchEvents();
                    } else {
                        alert("Erro ao marcar falta: " + res);
                    }
                });

                return;
            }

            // CASO CONTRÁRIO → MARCAR PRESENÇA NORMAL
            let horaE = prompt("Hora de entrada (HH:MM):");
            if (!horaE) return;

            let horaS = prompt("Hora de saída (HH:MM) (opcional):");

            fetch("http://localhost/PAP/PAP-AplicacaoWeb-PequenosPassos/marcarPresenca.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_crianca=${id_crianca}&data=${info.dateStr}&horaE=${horaE}&horaS=${horaS}`
            })
            .then(r => r.text())
            .then(res => {
                if (res.trim() === "ok") {
                    calendar.refetchEvents();
                } else {
                    alert("Erro ao marcar presença: " + res);
                }
            });
        }
    });

    calendar.render();

    document.getElementById('crianca').addEventListener('change', function() {
        calendar.refetchEvents();
    });

});


function fecharModal() {
    document.getElementById("modalEditar").style.display = "none";
}

function guardarEdicao() {
    const idPres = document.getElementById("edit_idPres").value;
    const horaE = document.getElementById("edit_horaE").value;
    const horaS = document.getElementById("edit_horaS").value;

    fetch("educador_presencas.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `acao=editar&idPres=${idPres}&horaE=${horaE}&horaS=${horaS}`
    })
    .then(r => r.text())
    .then(() => {
        fecharModal();
        calendar.refetchEvents();
    });
}

function removerPresenca() {
    const idPres = document.getElementById("edit_idPres").value;

    fetch("educador_presencas.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `acao=remover&idPres=${idPres}`
    })
    .then(r => r.text())
    .then(() => {
        fecharModal();
        calendar.refetchEvents();
    });
}

</script>

</body>
</html>
