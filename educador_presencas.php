<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

// Buscar tema do utilizador
$stmtTema = mysqli_prepare($link, "SELECT tema FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtTema, "i", $IDutl);
mysqli_stmt_execute($stmtTema);
$resTema = mysqli_stmt_get_result($stmtTema);
$tema = mysqli_fetch_assoc($resTema)['tema'] ?? 'light';

// Atualizar sessão
$_SESSION['tema'] = $tema;


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

<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Marcar Presenças (Educador)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="assets/fullcalendar/index.global.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<!-- SCRIPT global de toast-->
<script>
    function mostrarMensagem(tipo, texto) {
        const box = document.getElementById("msgGlobal");
        const icon = document.getElementById("msgIcon");
        const msg = document.getElementById("msgTexto");

        // Limpar classes antigas
        box.classList.remove("border-blue-600", "border-green-600", "border-yellow-500", "border-red-600");
        msg.classList.remove("text-blue-600", "text-green-600", "text-yellow-500", "text-red-600");

        const icons = {
            adicionar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-blue-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m4.5 12.75 6 6 9-13.5" />
            </svg>`,

            editar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 
                        2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 
                        1.13L6 18l.8-2.685a4.5 4.5 0 0 1 
                        1.13-1.897l8.932-8.931Zm0 0L19.5 
                        7.125M18 14v4.75A2.25 2.25 0 0 1 
                        15.75 21H5.25A2.25 2.25 0 0 1 
                        3 18.75V8.25A2.25 2.25 0 0 1 
                        5.25 6H10" />
            </svg>`,

            reset: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-yellow-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 
                        3.374 1.948 3.374h14.71c1.73 0 
                        2.813-1.874 1.948-3.374L13.949 
                        3.378c-.866-1.5-3.032-1.5-3.898 
                        0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>`,

            eliminar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-red-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21
                        c.342.052.682.107 1.022.166m-1.022-.165L19.5 19.5
                        a2.25 2.25 0 0 1-2.244 2.25H6.744A2.25 2.25 0 0 1
                        4.5 19.5L5.772 5.79m14.456 0a48.108 48.108 0 0 0
                        -3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0
                        a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164
                        -2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09
                        1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>`
        };

        // Aplicar ícone
        icon.innerHTML = icons[tipo];
        msg.textContent = texto;

        // Aplicar cor do texto
        if (tipo === "adicionar") msg.classList.add("text-blue-600");
        if (tipo === "editar") msg.classList.add("text-green-600");
        if (tipo === "reset") msg.classList.add("text-yellow-500");
        if (tipo === "eliminar") msg.classList.add("text-red-600");

        // Aplicar cor da borda
        if (tipo === "adicionar") box.classList.add("border-blue-600");
        if (tipo === "editar") box.classList.add("border-green-600");
        if (tipo === "reset") box.classList.add("border-yellow-500");
        if (tipo === "eliminar") box.classList.add("border-red-600");

        // Mostrar
        box.classList.remove("hidden", "opacity-0");
        box.classList.add("opacity-100");

        // Ocultar após 3 segundos
        setTimeout(() => {
            box.classList.add("opacity-0");
            setTimeout(() => box.classList.add("hidden"), 300);
        }, 3000);
    }
</script>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { scrollbar-width: none; }
</style>

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">
    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal" 
        class="hidden fixed top-5 right-5 bg-white shadow-lg border-l-4 rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

<div class="flex min-h-screen flex-col lg:flex-row">

    <!-- SIDEBAR -->
    <div class="hidden lg:block">
        <?php include("sidebar_educador.php"); ?>
    </div>

    <!-- MENU MOBILE -->
    <?php include("menu_mobile_educador.php"); ?>

    <!-- CONTEÚDO -->
    <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
            Marcar e Ver presenças das crianças da sua sala
        </h1>

        <a href="educador.php"
        class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
            ← Voltar
        </a>

        <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">
            
            <label class="font-semibold dark:text-gray-200">Escolha a criança:</label>
            <select id="crianca"
                class="border border-gray-300 dark:border-gray-600 
                       p-2 rounded w-full mb-6 
                       bg-white dark:bg-gray-700 dark:text-gray-100">
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
        <div id="modalEditar"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">

            <div class="bg-white dark:bg-gray-800 w-80 p-6 rounded-lg shadow-lg">

                <h3 class="text-xl font-bold mb-3 dark:text-gray-100">Editar Presença</h3>

                <input type="hidden" id="edit_idPres">

                <label class="dark:text-gray-300">Hora Entrada:</label>
                <input type="time" id="edit_horaE"
                    class="border border-gray-300 dark:border-gray-600 
                           p-2 w-full mb-3 bg-white dark:bg-gray-700 dark:text-gray-100">

                <label class="dark:text-gray-300">Hora Saída:</label>
                <input type="time" id="edit_horaS"
                    class="border border-gray-300 dark:border-gray-600 
                           p-2 w-full mb-3 bg-white dark:bg-gray-700 dark:text-gray-100">

                <div class="flex gap-2 mt-4">
                    <button onclick="guardarEdicao()"
                        class="bg-green-600 dark:bg-green-700 text-white px-4 py-2 rounded hover:bg-green-700 dark:hover:bg-green-600">
                        Guardar
                    </button>

                    <button onclick="removerPresenca()"
                        class="bg-red-600 dark:bg-red-700 text-white px-4 py-2 rounded hover:bg-red-700 dark:hover:bg-red-600">
                        Remover
                    </button>

                    <button onclick="fecharModal()"
                        class="bg-gray-600 dark:bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                </div>

            </div>
        </div>

        <!-- ============================================================
            MODAL ALERTA
        ============================================================ -->
        <div id="modalAlert"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">

            <div class="bg-white dark:bg-gray-800 w-80 p-6 rounded-lg shadow-lg text-center">

                <h3 id="modalAlertTitulo"
                    class="text-xl font-bold mb-3 dark:text-gray-100">
                    Aviso
                </h3>

                <p id="modalAlertMensagem"
                    class="text-gray-700 dark:text-gray-300 mb-4"></p>

                <button onclick="fecharModalAlert()"
                    class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600">
                    OK
                </button>

            </div>
        </div>

        <!-- ============================================================
            MODAL CONFIRMAÇÃO
        ============================================================ -->
        <div id="modalConfirm"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">

            <div class="bg-white dark:bg-gray-800 w-80 p-6 rounded-lg shadow-lg text-center">

                <h3 id="modalConfirmTitulo"
                    class="text-xl font-bold mb-3 dark:text-gray-100">
                    Confirmar
                </h3>

                <p id="modalConfirmMensagem"
                    class="text-gray-700 dark:text-gray-300 mb-4"></p>

                <div class="flex justify-center gap-3">
                    <button id="modalConfirmSim"
                        class="bg-green-600 dark:bg-green-700 text-white px-4 py-2 rounded hover:bg-green-700 dark:hover:bg-green-600">
                        Sim
                    </button>

                    <button id="modalConfirmNao"
                        class="bg-gray-600 dark:bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-600">
                        Não
                    </button>
                </div>

            </div>
        </div>

        <!-- ============================================================
            MODAL INPUT (Marcar Presença)
        ============================================================ -->
        <div id="modalInput"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">

            <div class="bg-white dark:bg-gray-800 w-80 p-6 rounded-lg shadow-lg">

                <h3 class="text-xl font-bold mb-3 dark:text-gray-100">
                    Marcar Presença
                </h3>

                <label class="dark:text-gray-300">Hora Entrada:</label>
                <input type="time" id="input_horaE"
                    class="border border-gray-300 dark:border-gray-600 
                           p-2 w-full mb-3 bg-white dark:bg-gray-700 dark:text-gray-100">

                <label class="dark:text-gray-300">Hora Saída (opcional):</label>
                <input type="time" id="input_horaS"
                    class="border border-gray-300 dark:border-gray-600 
                           p-2 w-full mb-3 bg-white dark:bg-gray-700 dark:text-gray-100">

                <div class="flex gap-2 mt-4">
                    <button id="modalInputConfirmar"
                        class="bg-green-600 dark:bg-green-700 text-white px-4 py-2 rounded hover:bg-green-700 dark:hover:bg-green-600">
                        Confirmar
                    </button>

                    <button onclick="fecharModalInput()"
                        class="bg-gray-600 dark:bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                </div>

            </div>
        </div>

    </main>
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
