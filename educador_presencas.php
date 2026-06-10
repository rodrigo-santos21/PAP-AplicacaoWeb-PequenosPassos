<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

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

        $res = mysqli_query($link, "SELECT IDcri, tipo FROM presenca WHERE IDpre = $idPres AND estado = 1");
        $p = mysqli_fetch_assoc($res);

        if (!$p) { echo "ERRO"; exit; }

        if ($p['tipo'] === 'falta') { echo "ERRO_FALTA"; exit; }

        $id_crianca = $p['IDcri'];

        $res2 = mysqli_query($link, "
            SELECT 1 FROM crianca_educador 
            WHERE IDcri = $id_crianca AND IDedu = $id_educador AND estado = 1
        ");

        if (mysqli_num_rows($res2) == 0) { echo "ERRO_PERMISSAO"; exit; }

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

        $res = mysqli_query($link, "SELECT IDcri FROM presenca WHERE IDpre = $idPres AND estado = 1");
        $p = mysqli_fetch_assoc($res);

        if (!$p) { echo "ERRO"; exit; }

        $id_crianca = $p['IDcri'];

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
    <script src="assets/fullcalendar/index.global.min.js"></script>
</head>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { scrollbar-width: none; }
</style>

<body class="bg-gray-100 min-h-screen">

<div class="flex min-h-screen flex-col lg:flex-row">

    <div class="hidden lg:block">
        <?php include("sidebar_educador.php"); ?>
    </div>

    <?php include("menu_mobile_educador.php"); ?>

    <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

        <h1 class="text-3xl font-bold text-gray-800 mb-8">Marcar e Ver presenças das crianças da sua sala</h1>

        <a href="educador.php"
        class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
            ← Voltar
        </a>

        <div class="w-full bg-white shadow-lg rounded-lg p-8">
            
            <label class="font-semibold">Escolha a criança:</label>
            <select id="crianca" class="border p-2 rounded w-full mb-6">
                <option value="">-- Selecionar --</option>
                <?php foreach ($criancas as $c): ?>
                    <option value="<?= $c['IDcri'] ?>"><?= $c['nome'] ?></option>
                <?php endforeach; ?>
            </select>

            <div id="calendar"></div>

        </div>

        <!-- ============================================================
            MODAL EDITAR PRESENÇA
            ============================================================ -->

        <div id="modalEditar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
            <div class="bg-white w-80 p-6 rounded-lg shadow-lg">

                <h3 class="text-xl font-bold mb-3">Editar Presença</h3>

                <input type="hidden" id="edit_idPres">

                <label>Hora Entrada:</label>
                <input type="time" id="edit_horaE" class="border p-2 w-full mb-3">

                <label>Hora Saída:</label>
                <input type="time" id="edit_horaS" class="border p-2 w-full mb-3">

                <div class="flex gap-2 mt-4">
                    <button onclick="guardarEdicao()" class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
                    <button onclick="removerPresenca()" class="bg-red-600 text-white px-4 py-2 rounded">Remover</button>
                    <button onclick="fecharModal()" class="bg-gray-600 text-white px-4 py-2 rounded">Cancelar</button>
                </div>

            </div>
        </div>

        <!-- ============================================================
            MODAL ALERTA
            ============================================================ -->

        <div id="modalAlert" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">
            <div class="bg-white w-80 p-6 rounded-lg shadow-lg text-center">
                <h3 id="modalAlertTitulo" class="text-xl font-bold mb-3">Aviso</h3>
                <p id="modalAlertMensagem" class="text-gray-700 mb-4"></p>

                <button onclick="fecharModalAlert()" class="bg-blue-600 text-white px-4 py-2 rounded">
                    OK
                </button>
            </div>
        </div>

        <!-- ============================================================
            MODAL CONFIRMAÇÃO
            ============================================================ -->

        <div id="modalConfirm" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">
            <div class="bg-white w-80 p-6 rounded-lg shadow-lg text-center">
                <h3 id="modalConfirmTitulo" class="text-xl font-bold mb-3">Confirmar</h3>
                <p id="modalConfirmMensagem" class="text-gray-700 mb-4"></p>

                <div class="flex justify-center gap-3">
                    <button id="modalConfirmSim" class="bg-green-600 text-white px-4 py-2 rounded">Sim</button>
                    <button id="modalConfirmNao" class="bg-gray-600 text-white px-4 py-2 rounded">Não</button>
                </div>
            </div>
        </div>

        <!-- ============================================================
            MODAL INPUT (para marcar presença)
            ============================================================ -->

        <div id="modalInput" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">
            <div class="bg-white w-80 p-6 rounded-lg shadow-lg">

                <h3 class="text-xl font-bold mb-3">Marcar Presença</h3>

                <label>Hora Entrada:</label>
                <input type="time" id="input_horaE" class="border p-2 w-full mb-3">

                <label>Hora Saída (opcional):</label>
                <input type="time" id="input_horaS" class="border p-2 w-full mb-3">

                <div class="flex gap-2 mt-4">
                    <button id="modalInputConfirmar" class="bg-green-600 text-white px-4 py-2 rounded">Confirmar</button>
                    <button onclick="fecharModalInput()" class="bg-gray-600 text-white px-4 py-2 rounded">Cancelar</button>
                </div>

            </div>
        </div>

<script>

let calendar;

/* ============================================================
   FUNÇÕES DE MODAIS (ALERTA / CONFIRMAÇÃO / INPUT)
   ============================================================ */

function abrirModalAlert(titulo, mensagem) {
    document.getElementById("modalAlertTitulo").innerText = titulo;
    document.getElementById("modalAlertMensagem").innerText = mensagem;
    document.getElementById("modalAlert").classList.remove("hidden");
}

function fecharModalAlert() {
    document.getElementById("modalAlert").classList.add("hidden");
}

let confirmCallback = null;

let callbackSim = null;
let callbackNao = null;

function abrirModalConfirm(titulo, mensagem, sim, nao) {
    document.getElementById("modalConfirmTitulo").innerText = titulo;
    document.getElementById("modalConfirmMensagem").innerText = mensagem;
    document.getElementById("modalConfirm").classList.remove("hidden");

    callbackSim = sim;
    callbackNao = nao;
}


function fecharModalConfirm() {
    document.getElementById("modalConfirm").classList.add("hidden");
    confirmCallback = null;
}

document.getElementById("modalConfirmSim").onclick = function () {
    if (callbackSim) callbackSim();
    fecharModalConfirm();
};

document.getElementById("modalConfirmNao").onclick = function () {
    if (callbackNao) callbackNao();
    fecharModalConfirm();
};


let inputCallback = null;

function abrirModalInput(callback) {
    document.getElementById("input_horaE").value = "";
    document.getElementById("input_horaS").value = "";
    document.getElementById("modalInput").classList.remove("hidden");
    inputCallback = callback;
}

function fecharModalInput() {
    document.getElementById("modalInput").classList.add("hidden");
    inputCallback = null;
}

document.getElementById("modalInputConfirmar").onclick = function () {
    const horaE = document.getElementById("input_horaE").value;
    const horaS = document.getElementById("input_horaS").value;

    if (!horaE) {
        abrirModalAlert("Erro", "A hora de entrada é obrigatória.");
        return;
    }

    if (inputCallback) inputCallback(horaE, horaS);
    fecharModalInput();
};

/* ============================================================
   FULLCALENDAR
   ============================================================ */

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

            fetch("getPresencas.php?id_crianca=" + id_crianca)
                .then(r => r.json())
                .then(data => successCallback(data))
                .catch(() => abrirModalAlert("Erro", "Não foi possível carregar as presenças."));
        },

        eventClick: function(info) {

            if (info.event.title.includes("FALTA")) {
                return;
            }

            const idPres = info.event.id;

            const horaE = info.event.extendedProps.horaE || "";
            const horaS = info.event.extendedProps.horaS || "";

            document.getElementById("edit_idPres").value = idPres;
            document.getElementById("edit_horaE").value = horaE;
            document.getElementById("edit_horaS").value = horaS;

            document.getElementById("modalEditar").classList.remove("hidden");
        },

        dateClick: function(info) {

            let id_crianca = document.getElementById('crianca').value;

            if (!id_crianca) {
                abrirModalAlert("Aviso", "Escolha uma criança primeiro.");
                return;
            }

            // PERGUNTAR SE É FALTA
            abrirModalConfirm(
                "Marcar Falta",
                "Deseja marcar FALTA para esta data?",

                // SIM → marcar falta
                function() {
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
                            abrirModalAlert("Erro", "Erro ao marcar falta: " + res);
                        }
                    });
                },

                // NÃO → marcar presença normal
                function() {
                    abrirModalInput(function(horaE, horaS) {

                        fetch("marcarPresenca.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: `id_crianca=${id_crianca}&data=${info.dateStr}&horaE=${horaE}&horaS=${horaS}`
                        })
                        .then(r => r.text())
                        .then(res => {
                            if (res.trim() === "ok") {
                                calendar.refetchEvents();
                            } else {
                                abrirModalAlert("Erro", "Erro ao marcar presença: " + res);
                            }
                        });

                    });
                }
            );
        }
    });

    calendar.render();

    document.getElementById('crianca').addEventListener('change', function() {
        calendar.refetchEvents();
    });

});

/* ============================================================
   FUNÇÕES DO MODAL EDITAR
   ============================================================ */

function fecharModal() {
    document.getElementById("modalEditar").classList.add("hidden");
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
    .then(res => {

        if (res.trim() === "ERRO_HORAS") {
            abrirModalAlert("Erro", "A hora de saída não pode ser menor que a hora de entrada.");
            return;
        }

        if (res.trim() === "ERRO_PERMISSAO") {
            abrirModalAlert("Erro", "Não tem permissão para editar esta presença.");
            return;
        }

        fecharModal();
        calendar.refetchEvents();
    });
}

function removerPresenca() {

    abrirModalConfirm(
        "Remover Presença",
        "Tem a certeza que deseja remover esta presença?",
        function() {

            const idPres = document.getElementById("edit_idPres").value;

            fetch("educador_presencas.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `acao=remover&idPres=${idPres}`
            })
            .then(r => r.text())
            .then(res => {

                if (res.trim() === "ERRO_PERMISSAO") {
                    abrirModalAlert("Erro", "Não tem permissão para remover esta presença.");
                    return;
                }

                fecharModal();
                calendar.refetchEvents();
            });
        }
    );
}

</script>

</body>
</html>
