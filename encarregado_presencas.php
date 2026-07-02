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

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit();
}

$id_encarregado = $_SESSION['id'];

// Buscar filhos
$sql = "SELECT * FROM crianca 
        WHERE IDutl = $id_encarregado 
          AND estado = 1";

$res = mysqli_query($link, $sql);

$criancas = [];
while ($row = mysqli_fetch_assoc($res)) {
    $criancas[] = $row;
}

// Justificação enviada
if (isset($_POST['acao']) && $_POST['acao'] === 'justificar') {

    $idPres = $_POST['idPres'];
    $justificacao = mysqli_real_escape_string($link, $_POST['justificacao']);
    $id_crianca_sel = $_POST['id_crianca_sel'];

    $sql = "UPDATE presenca 
            SET justificacao = '$justificacao',
                justificacao_estado = 'pendente'
            WHERE IDpre = $idPres";

    mysqli_query($link, $sql);

    header("Location: encarregado_presencas.php?id_crianca=$id_crianca_sel");
    exit();
}

$id_crianca_sel = $_GET['id_crianca'] ?? "";
?>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Presenças e Faltas</title>
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

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">

    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal"
        class="hidden fixed top-5 right-5 bg-white dark:bg-gray-800 dark:text-gray-100 
               shadow-lg border-l-4 border-blue-500 dark:border-blue-400 
               rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

    <!-- WRAPPER -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_encarregado.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_encarregado.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Ver Presenças e Faltas das suas crianças
            </h1>
    
            <a href="encarregado.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                   rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
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
                        <option value="<?= $c['IDcri'] ?>" <?= ($id_crianca_sel == $c['IDcri']) ? 'selected' : '' ?>>
                            <?= $c['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div id="calendar"></div>

            </div>

            <!-- MODAL JUSTIFICAÇÃO -->
            <div id="modalJust"
                class="hidden fixed inset-0 w-full h-full bg-black bg-opacity-50 pt-[100px] z-[9999] flex justify-center items-center">
                <div class="bg-white dark:bg-gray-800 w-[400px] mx-auto p-5 rounded-lg z-[10000]">

                    <h3 class="text-xl font-bold mb-3 dark:text-gray-100">Justificar falta</h3>

                    <form method="post">
                        <input type="hidden" name="acao" value="justificar">
                        <input type="hidden" name="idPres" id="just_idPres">
                        <input type="hidden" name="id_crianca_sel" id="just_idCri">

                        <label class="dark:text-gray-300">Justificação:</label>
                        <textarea name="justificacao" rows="4"
                            class="border border-gray-300 dark:border-gray-600 
                                   p-2 w-full mb-3 bg-white dark:bg-gray-700 dark:text-gray-100"
                            required></textarea>

                        <button type="submit"
                            class="bg-green-600 dark:bg-green-700 text-white px-4 py-2 rounded hover:bg-green-700 dark:hover:bg-green-600">
                            Enviar
                        </button>

                        <button type="button" onclick="fecharJustificacao()"
                            class="bg-gray-600 dark:bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-700 dark:hover:bg-gray-600">
                            Cancelar
                        </button>
                    </form>

                </div>
            </div>

            <!-- MODAL PRESENÇA NORMAL -->
            <div id="modalPresenca"
                class="hidden fixed inset-0 w-full h-full bg-black bg-opacity-50 pt-[100px] z-[9999] flex justify-center items-center">
                <div class="bg-white dark:bg-gray-800 w-[400px] mx-auto p-5 rounded-lg z-[10000]">

                    <h3 class="text-xl font-bold mb-3 dark:text-gray-100">Detalhes da Presença</h3>

                    <p class="dark:text-gray-200"><b>Data:</b> <span id="modalPresencaData"></span></p>
                    <p class="dark:text-gray-200"><b>Entrada:</b> <span id="modalPresencaEntrada"></span></p>
                    <p class="dark:text-gray-200"><b>Saída:</b> <span id="modalPresencaSaida"></span></p>

                    <button onclick="document.getElementById('modalPresenca').style.display='none'"
                        class="bg-gray-600 dark:bg-gray-700 text-white px-4 py-2 rounded 
                               hover:bg-gray-700 dark:hover:bg-gray-600 mt-4">
                        Fechar
                    </button>

                </div>
            </div>

        </main>
    </div>

<script>
let calendar;

document.addEventListener('DOMContentLoaded', function() {

    let calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        timeZone: 'local',

        events: function(fetchInfo, successCallback) {

            let id_crianca = document.getElementById('crianca').value;

            if (!id_crianca) {
                successCallback([]);
                return;
            }

            fetch("getPresencasEncarregado.php?id_crianca=" + id_crianca)
                .then(r => r.json())
                .then(data => successCallback(data));
        },

        eventClick: function(info) {

            let tipo = info.event.extendedProps.tipo;

            // ============================
            // PRESENÇA → modal readonly
            // ============================
            if (tipo === "presenca") {

                let entrada = info.event.extendedProps.horaE || "-";
                let saida   = info.event.extendedProps.horaS || "-";
                let data    = info.event.startStr.substring(0, 10);

                document.getElementById("modalPresencaData").innerText = data;
                document.getElementById("modalPresencaEntrada").innerText = entrada;
                document.getElementById("modalPresencaSaida").innerText = saida;

                document.getElementById("modalPresenca").style.display = "block";
                return;
            }

            // ============================
            // FALTA → modal de justificação
            // ============================
            if (tipo === "falta") {

                document.getElementById("just_idPres").value = info.event.id;
                document.getElementById("just_idCri").value = document.getElementById("crianca").value;

                let justificacao = info.event.extendedProps.justificacao;
                let estado = info.event.extendedProps.estado;

                let textarea = document.querySelector("#modalJust textarea");
                let botaoEnviar = document.querySelector("#modalJust button[type='submit']");

                // Se já existe justificação → mostrar readonly
                if (justificacao) {
                    textarea.value = justificacao;
                    textarea.readOnly = true;

                    if (estado === "pendente") {
                        botaoEnviar.style.display = "none";
                    }
                    else if (estado === "aceite") {
                        botaoEnviar.style.display = "none";
                    }
                    else if (estado === "recusada") {
                        textarea.readOnly = false; // permitir reenviar
                        botaoEnviar.style.display = "inline-block";
                    }
                }
                else {
                    textarea.value = "";
                    textarea.readOnly = false;
                    botaoEnviar.style.display = "inline-block";
                }

                document.getElementById("modalJust").style.display = "block";
            }
        }

    });

    calendar.render();

    document.getElementById('crianca').addEventListener('change', function() {
        calendar.refetchEvents();
    });
});

function fecharJustificacao() {
    document.getElementById("modalJust").style.display = "none";
}
</script>

</body>
</html>
