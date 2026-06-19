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

// Verifica se o utilizador é administrador
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

/* ============================================================
   AJAX: DEVOLVER EVENTOS PARA O FULLCALENDAR
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'events') {
    header('Content-Type: application/json; charset=utf-8');

    $events = [];
    $res = mysqli_query($link, "SELECT IDreu, titulo, datahora FROM reuniao WHERE estado = 1");

    while ($r = mysqli_fetch_assoc($res)) {
        $events[] = [
            'id'    => $r['IDreu'],
            'title' => $r['titulo'],
            'start' => $r['datahora']
        ];
    }

    echo json_encode($events);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER DADOS DA REUNIÃO (INCLUINDO PARTICIPANTES)
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)$_GET['id'];

    $stmt = mysqli_prepare($link, "SELECT IDreu, titulo, datahora, localidade, objetivo FROM reuniao WHERE IDreu = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $reu = mysqli_fetch_assoc($res);

    if (!$reu) {
        echo json_encode(['error' => 'Reunião não encontrada']);
        exit;
    }

    $func = [];
    $edu  = [];
    $enc  = [];

    $resP = mysqli_query($link, "SELECT IDutl FROM reuniao_participante WHERE IDreu = $id AND estado = 1");

    while ($p = mysqli_fetch_assoc($resP)) {
        $IDutl = $p['IDutl'];

        $u = mysqli_fetch_assoc(mysqli_query($link, "SELECT tipo FROM utilizador WHERE IDutl = $IDutl AND estado = 1"));
        if (!$u) continue;

        if ($u['tipo'] === 'funcionario') $func[] = $IDutl;
        if ($u['tipo'] === 'educador')   $edu[]  = $IDutl;
        if ($u['tipo'] === 'encarregado') $enc[] = $IDutl;
    }

    echo json_encode([
        'id'           => $reu['IDreu'],
        'titulo'       => $reu['titulo'],
        'datahora'     => $reu['datahora'],
        'localidade'   => $reu['localidade'],
        'objetivo'     => $reu['objetivo'],
        'funcionarios' => $func,
        'educadores'   => $edu,
        'encarregados' => $enc
    ]);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER SALA DE UM EDUCATOR
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getSalaEducador') {
    $id = (int)$_GET['id'];

    $res = mysqli_query($link, "SELECT IDsala FROM educador WHERE IDutl = $id AND estado = 1");
    $row = mysqli_fetch_assoc($res);

    echo json_encode(['sala' => $row ? $row['IDsala'] : null]);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER SALA DE UM ENCARREGADO
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getSalaEncarregado') {
    $id = (int)$_GET['id'];

    $res = mysqli_query($link, "SELECT IDsala FROM crianca WHERE IDutl = $id AND estado = 1");
    $row = mysqli_fetch_assoc($res);

    echo json_encode(['sala' => $row ? $row['IDsala'] : null]);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER EDUCADORES POR SALA
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getEducadores') {
    $sala = (int)$_GET['sala'];

    $res = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDsala = $sala AND estado = 1");

    while ($e = mysqli_fetch_assoc($res)) {
        $IDutl = $e['IDutl'];
        $u = mysqli_fetch_assoc(mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutl AND estado = 1"));

        echo "<label class='block ml-2'>
                <input type='checkbox' class='chk-edu' value='$IDutl'>
                {$u['nome']}
              </label>";
    }
    exit;
}

/* ============================================================
   AJAX: DEVOLVER ENCARREGADOS POR SALA
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getEncarregados') {
    $sala = (int)$_GET['sala'];

    $res = mysqli_query($link, "SELECT IDutl FROM crianca WHERE IDsala = $sala AND estado = 1");

    $mostrados = [];

    while ($c = mysqli_fetch_assoc($res)) {
        $IDutl = $c['IDutl'];

        if (in_array($IDutl, $mostrados)) continue;
        $mostrados[] = $IDutl;

        $u = mysqli_fetch_assoc(mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutl AND estado = 1"));

        echo "<label class='block ml-2'>
                <input type='checkbox' class='chk-enc' value='$IDutl'>
                {$u['nome']}
              </label>";
    }
    exit;
}

/* ============================================================
   AJAX: ATUALIZAR REUNIÃO
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $id         = (int)$_POST['id'];
    $titulo     = $_POST['titulo'];
    $datahora   = $_POST['datahora'];
    $localidade = $_POST['localidade'];
    $objetivo   = $_POST['objetivo'];

    $func = $_POST['funcionarios'] ?? [];
    $edu  = $_POST['educadores'] ?? [];
    $enc  = $_POST['encarregados'] ?? [];

    if (isset($_POST['funcionario_tipo']) && $_POST['funcionario_tipo'] === "todos") {
        $func = [];
        $res = mysqli_query($link, "SELECT IDutl FROM utilizador WHERE tipo='funcionario' AND estado=1");
        while ($row = mysqli_fetch_assoc($res)) $func[] = $row['IDutl'];
    }

    mysqli_query($link, "UPDATE reuniao 
                         SET titulo='$titulo', datahora='$datahora', localidade='$localidade', objetivo='$objetivo' 
                         WHERE IDreu=$id");

    mysqli_query($link, "UPDATE reuniao_participante SET estado = 0 WHERE IDreu = $id");

    $todos = array_unique(array_merge($func, $edu, $enc));
    foreach ($todos as $IDutl) {
        mysqli_query($link, "INSERT INTO reuniao_participante (IDreu, IDutl, estado) VALUES ($id, $IDutl, 1)");
    }

    echo json_encode(['success' => true]);
    exit;
}

/* ============================================================
   AJAX: ELIMINAR REUNIÃO
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Impede qualquer output antes do JSON
    if (ob_get_length()) ob_clean();

    header('Content-Type: application/json; charset=utf-8');

    $id = (int)$_POST['id'];

    $ok1 = mysqli_query($link, "UPDATE reuniao_participante SET estado = 0 WHERE IDreu = $id");
    $ok2 = mysqli_query($link, "UPDATE reuniao SET estado = 0 WHERE IDreu = $id");

    if ($ok1 && $ok2) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'erro' => mysqli_error($link)]);
    }

    exit;
}

/* ============================================================
   CARREGAR LISTA DE FUNCIONÁRIOS
   ============================================================ */
$listaFuncionarios = [];
$resF = mysqli_query($link, "SELECT IDutl, nome FROM utilizador WHERE tipo='funcionario' AND estado=1");
while ($f = mysqli_fetch_assoc($resF)) $listaFuncionarios[] = $f;

?>

<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Listar Reuniões</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- FullCalendar local -->
    <script src="https://pequenospassos.infinityfree.io/assets/fullcalendar/index.global.min.js"></script>

    <style>
        #modalReuniao { 
            z-index: 999999 !important; 
            position: fixed !important; 
        }
        .fc { 
            z-index: 1 !important; 
        }
    </style>
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
            <?php include("sidebar_admin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_admin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Listar Reuniões da creche
            </h1>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">

                <a href="admin.php"
                class="mb-6 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                       rounded-md font-semibold hover:bg-blue-700 dark:hover:bg-blue-600">
                    ← Voltar
                </a>

                <a href="adicionarreu.php"
                class="mb-6 px-4 py-2 bg-green-600 dark:bg-green-700 text-white 
                       rounded-md font-semibold hover:bg-green-700 dark:hover:bg-green-600">
                    + Adicionar Reunião
                </a>

            </div>

            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">
                <div id="calendar"></div>
            </div>

            <!-- MODAL EDITAR -->
            <div id="modalReuniao" 
                 class="hidden inset-0 bg-black bg-opacity-50 flex items-center justify-center">

                <div class="bg-white dark:bg-gray-800 dark:text-gray-100 
                            w-full max-w-3xl rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

                    <h2 class="text-xl font-bold mb-4">Editar Reunião</h2>

                    <form id="formReuniao" class="space-y-4">
                        <input type="hidden" id="reu_id">

                        <!-- CAMPOS BASE -->
                        <div>
                            <label class="block text-sm font-medium dark:text-gray-200">Título</label>
                            <input type="text" id="reu_titulo"
                                   class="w-full border border-gray-300 dark:border-gray-600 
                                          p-2 rounded bg-white dark:bg-gray-900 dark:text-gray-100" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium dark:text-gray-200">Data e Hora</label>
                            <input type="datetime-local" id="reu_datahora"
                                   class="w-full border border-gray-300 dark:border-gray-600 
                                          p-2 rounded bg-white dark:bg-gray-900 dark:text-gray-100" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium dark:text-gray-200">Localidade</label>
                            <input type="text" id="reu_localidade"
                                   class="w-full border border-gray-300 dark:border-gray-600 
                                          p-2 rounded bg-white dark:bg-gray-900 dark:text-gray-100" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium dark:text-gray-200">Objetivo</label>
                            <textarea id="reu_objetivo" rows="3"
                                      class="w-full border border-gray-300 dark:border-gray-600 
                                             p-2 rounded bg-white dark:bg-gray-900 dark:text-gray-100" required></textarea>
                        </div>

                        <hr class="border-gray-300 dark:border-gray-600">

                        <!-- PARTICIPANTES -->
                        <h3 class="text-lg font-semibold mb-3 dark:text-gray-100">Participantes</h3>

                        <!-- BOTÕES -->
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <button type="button" id="btn_func"
                                class="p-3 bg-gray-200 dark:bg-gray-700 rounded text-center font-semibold 
                                       hover:bg-gray-300 dark:hover:bg-gray-600">
                                Funcionários
                            </button>

                            <button type="button" id="btn_edu"
                                class="p-3 bg-gray-200 dark:bg-gray-700 rounded text-center font-semibold 
                                       hover:bg-gray-300 dark:hover:bg-gray-600">
                                Educadores
                            </button>

                            <button type="button" id="btn_enc"
                                class="p-3 bg-gray-200 dark:bg-gray-700 rounded text-center font-semibold 
                                       hover:bg-gray-300 dark:hover:bg-gray-600">
                                Encarregados
                            </button>
                        </div>

                        <!-- FUNCIONÁRIOS -->
                        <div id="sec_func" class="hidden border border-gray-300 dark:border-gray-600 
                                                 p-4 rounded mb-4 dark:bg-gray-900">

                            <label class="block font-medium dark:text-gray-200">Selecionar:</label>
                            <select id="funcionario_tipo"
                                class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                                       bg-white dark:bg-gray-900 dark:text-gray-100">
                                <option value="">-- Escolher --</option>
                                <option value="todos">Todos os funcionários</option>
                                <option value="especificos">Selecionar específicos</option>
                            </select>

                            <div id="funcionario_lista"
                                class="hidden border border-gray-300 dark:border-gray-600 
                                       p-3 rounded dark:bg-gray-800"
                                data-total="<?= count($listaFuncionarios) ?>">

                                <?php foreach ($listaFuncionarios as $f): ?>
                                    <label class="block ml-2 dark:text-gray-200">
                                        <input type="checkbox" class="chk-func" value="<?= $f['IDutl'] ?>">
                                        <?= htmlspecialchars($f['nome']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        </div>

                        <!-- EDUCADORES -->
                        <div id="sec_edu" class="hidden border border-gray-300 dark:border-gray-600 
                                                 p-4 rounded mb-4 dark:bg-gray-900">

                            <label class="block font-medium dark:text-gray-200">Sala:</label>
                            <select id="educador_sala"
                                class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                                       bg-white dark:bg-gray-900 dark:text-gray-100">
                                <option value="">-- Escolher sala --</option>
                                <?php
                                $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                                while ($s = mysqli_fetch_assoc($salas)) {
                                    echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                                }
                                ?>
                            </select>

                            <div id="educador_lista"
                                class="hidden border border-gray-300 dark:border-gray-600 
                                       p-3 rounded dark:bg-gray-800"></div>

                        </div>

                        <!-- ENCARREGADOS -->
                        <div id="sec_enc" class="hidden border border-gray-300 dark:border-gray-600 
                                                 p-4 rounded mb-4 dark:bg-gray-900">

                            <label class="block font-medium dark:text-gray-200">Sala:</label>
                            <select id="encarregado_sala"
                                class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                                       bg-white dark:bg-gray-900 dark:text-gray-100">
                                <option value="">-- Escolher sala --</option>
                                <?php
                                $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                                while ($s = mysqli_fetch_assoc($salas)) {
                                    echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                                }
                                ?>
                            </select>

                            <div id="encarregado_lista"
                                class="hidden border border-gray-300 dark:border-gray-600 
                                       p-3 rounded dark:bg-gray-800"></div>

                        </div>

                        <!-- BOTÕES -->
                        <div class="flex justify-between mt-4">
                            <button type="button" id="btnEliminar"
                                    class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white rounded 
                                           hover:bg-red-700 dark:hover:bg-red-600">
                                Eliminar
                            </button>

                            <div class="flex gap-2">
                                <button type="button" onclick="fecharModal()"
                                        class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white rounded 
                                               hover:bg-gray-600 dark:hover:bg-gray-500">
                                    Cancelar
                                </button>

                                <button type="submit"
                                        class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded 
                                               hover:bg-blue-700 dark:hover:bg-blue-600">
                                    Guardar
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>

<!-- MODAL ELIMINAR -->
<div id="modalEliminarReuniao" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white dark:bg-gray-800 dark:text-gray-100 
                p-6 rounded-lg shadow-lg w-full max-w-md">

        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
            Confirmar Eliminação
        </h2>

        <p class="text-gray-700 dark:text-gray-200 mb-6">
            Tens a certeza que desejas eliminar esta reunião?
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="fecharModalReuniao()"
                class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white rounded 
                       hover:bg-gray-600 dark:hover:bg-gray-500">
                Cancelar
            </button>

            <button id="btnConfirmarEliminarReuniao"
                class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white rounded 
                       hover:bg-red-700 dark:hover:bg-red-600">
                Eliminar
            </button>
        </div>
    </div>
</div>

<script>

let selecionadosEducadores = [];
let selecionadosEncarregados = [];

function abrirModal() {
    document.getElementById('modalReuniao').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modalReuniao').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function () {

    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        height: 'auto',

        events: 'listarreu.php?action=events',

        eventClick: function (info) {
            const id = info.event.id;

            fetch('listarreu.php?action=get&id=' + id)
                .then(r => r.json())
                .then(data => {

                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    /* ============================================================
                    ARRAYS GLOBAIS — MANTER SELECIONADOS
                    ============================================================ */
                    selecionadosEducadores = data.educadores.map(String);
                    selecionadosEncarregados = data.encarregados.map(String);

                    /* ============================================================
                    CAMPOS BASE
                    ============================================================ */
                    document.getElementById('reu_id').value = data.id;
                    document.getElementById('reu_titulo').value = data.titulo;
                    document.getElementById('reu_localidade').value = data.localidade;
                    document.getElementById('reu_objetivo').value = data.objetivo;

                    const dt = data.datahora.replace(' ', 'T').slice(0, 16);
                    document.getElementById('reu_datahora').value = dt;

                    /* ============================================================
                    LIGAR BOTÃO ELIMINAR AO MODAL DE CONFIRMAÇÃO
                    ============================================================ */
                    document.getElementById("btnEliminar").onclick = function () {
                        fecharModal();
                        setTimeout(() => abrirModalEliminar(id), 50);
                    };

                    /* ============================================================
                    LIMPAR LISTAS
                    ============================================================ */
                    document.querySelectorAll('.chk-func').forEach(chk => chk.checked = false);
                    document.getElementById("educador_lista").innerHTML = "";
                    document.getElementById("encarregado_lista").innerHTML = "";

                    /* ============================================================
                    FUNCIONÁRIOS — MARCAR AUTOMATICAMENTE
                    ============================================================ */
                    const idsFunc = data.funcionarios.map(String);

                    document.querySelectorAll('.chk-func').forEach(chk => {
                        if (idsFunc.includes(chk.value)) chk.checked = true;
                    });

                    const selFuncTipo   = document.getElementById('funcionario_tipo');
                    const boxFuncLista  = document.getElementById('funcionario_lista');
                    const totalFunc     = parseInt(boxFuncLista.dataset.total, 10);
                    const selecionados  = data.funcionarios.length;

                    if (selecionados === totalFunc) {
                        selFuncTipo.value = "todos";
                        boxFuncLista.classList.add('hidden');
                    } else if (selecionados > 0) {
                        selFuncTipo.value = "especificos";
                        boxFuncLista.classList.remove('hidden');
                    } else {
                        selFuncTipo.value = "";
                        boxFuncLista.classList.add('hidden');
                    }

                    /* ============================================================
                    EDUCADORES — CARREGAR AUTOMATICAMENTE
                    ============================================================ */
                    if (data.educadores.length > 0) {

                        fetch("listarreu.php?action=getSalaEducador&id=" + data.educadores[0])
                            .then(r => r.json())
                            .then(salaData => {

                                const sala = salaData.sala;
                                const select = document.getElementById("educador_sala");

                                select.value = sala;

                                fetch("listarreu.php?action=getEducadores&sala=" + sala)
                                    .then(r => r.text())
                                    .then(html => {
                                        const box = document.getElementById("educador_lista");
                                        box.innerHTML = html;
                                        box.classList.remove("hidden");

                                        box.querySelectorAll('.chk-edu').forEach(chk => {
                                            if (selecionadosEducadores.includes(chk.value)) chk.checked = true;

                                            chk.addEventListener('change', function () {
                                                if (this.checked) {
                                                    if (!selecionadosEducadores.includes(this.value)) {
                                                        selecionadosEducadores.push(this.value);
                                                    }
                                                } else {
                                                    selecionadosEducadores = selecionadosEducadores.filter(id => id !== this.value);
                                                }
                                            });
                                        });
                                    });
                            });
                    }

                    /* ============================================================
                    ENCARREGADOS — CARREGAR AUTOMATICAMENTE
                    ============================================================ */
                    if (data.encarregados.length > 0) {

                        fetch("listarreu.php?action=getSalaEncarregado&id=" + data.encarregados[0])
                            .then(r => r.json())
                            .then(salaData => {

                                const sala = salaData.sala;
                                const select = document.getElementById("encarregado_sala");

                                select.value = sala;

                                fetch("listarreu.php?action=getEncarregados&sala=" + sala)
                                    .then(r => r.text())
                                    .then(html => {
                                        const box = document.getElementById("encarregado_lista");
                                        box.innerHTML = html;
                                        box.classList.remove("hidden");

                                        box.querySelectorAll('.chk-enc').forEach(chk => {
                                            if (selecionadosEncarregados.includes(chk.value)) chk.checked = true;

                                            chk.addEventListener('change', function () {
                                                if (this.checked) {
                                                    if (!selecionadosEncarregados.includes(this.value)) {
                                                        selecionadosEncarregados.push(this.value);
                                                    }
                                                } else {
                                                    selecionadosEncarregados = selecionadosEncarregados.filter(id => id !== this.value);
                                                }
                                            });
                                        });
                                    });
                            });
                    }

                    abrirModal();
                });
        }
    });

    calendar.render();

    /* ============================================================
       LISTENER — MUDAR SALA EDUCADORES
       ============================================================ */
    document.getElementById("educador_sala").addEventListener("change", function () {
        const sala = this.value;
        const box = document.getElementById("educador_lista");

        if (!sala) {
            box.innerHTML = "";
            box.classList.add("hidden");
            return;
        }

        fetch("listarreu.php?action=getEducadores&sala=" + sala)
            .then(r => r.text())
            .then(html => {
                box.innerHTML = html;
                box.classList.remove("hidden");

                box.querySelectorAll('.chk-edu').forEach(chk => {
                    if (selecionadosEducadores.includes(chk.value)) chk.checked = true;

                    chk.addEventListener('change', function () {
                        if (this.checked) {
                            if (!selecionadosEducadores.includes(this.value)) {
                                selecionadosEducadores.push(this.value);
                            }
                        } else {
                            selecionadosEducadores = selecionadosEducadores.filter(id => id !== this.value);
                        }
                    });
                });
            });
    });

    /* ============================================================
       LISTENER — MUDAR SALA ENCARREGADOS
       ============================================================ */
    document.getElementById("encarregado_sala").addEventListener("change", function () {
        const sala = this.value;
        const box = document.getElementById("encarregado_lista");

        if (!sala) {
            box.innerHTML = "";
            box.classList.add("hidden");
            return;
        }

        fetch("listarreu.php?action=getEncarregados&sala=" + sala)
            .then(r => r.text())
            .then(html => {
                box.innerHTML = html;
                box.classList.remove("hidden");

                box.querySelectorAll('.chk-enc').forEach(chk => {
                    if (selecionadosEncarregados.includes(chk.value)) chk.checked = true;

                    chk.addEventListener('change', function () {
                        if (this.checked) {
                            if (!selecionadosEncarregados.includes(this.value)) {
                                selecionadosEncarregados.push(this.value);
                            }
                        } else {
                            selecionadosEncarregados = selecionadosEncarregados.filter(id => id !== this.value);
                        }
                    });
                });
            });
    });

    /* ============================================================
       SUBMETER EDIÇÃO
       ============================================================ */
    document.getElementById('formReuniao').addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('reu_id').value;
        const titulo = document.getElementById('reu_titulo').value;
        const datahora = document.getElementById('reu_datahora').value;
        const localidade = document.getElementById('reu_localidade').value;
        const objetivo = document.getElementById('reu_objetivo').value;

        const funcionarios = [];
        document.querySelectorAll('.chk-func:checked').forEach(chk => funcionarios.push(chk.value));

        const educadores = [...new Set(selecionadosEducadores)];
        const encarregados = [...new Set(selecionadosEncarregados)];

        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('titulo', titulo);
        formData.append('datahora', datahora);
        formData.append('localidade', localidade);
        formData.append('objetivo', objetivo);

        formData.append('funcionario_tipo', document.getElementById('funcionario_tipo').value);

        funcionarios.forEach(v => formData.append('funcionarios[]', v));
        educadores.forEach(v => formData.append('educadores[]', v));
        encarregados.forEach(v => formData.append('encarregados[]', v));

        fetch('listarreu.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    mostrarMensagem("editar", "Reunião atualizada com sucesso!");
                    fecharModal();
                    calendar.refetchEvents();
                } else {
                    mostrarMensagem("reset", "Erro ao atualizar a reunião.");
                }
            });
    });

});

/* ============================================================
   BOTÕES DE PARTICIPANTES
   ============================================================ */

const sec_func = document.getElementById("sec_func");
const sec_edu  = document.getElementById("sec_edu");
const sec_enc  = document.getElementById("sec_enc");

document.getElementById("btn_func").onclick = () => {
    sec_func.classList.toggle("hidden");
    sec_edu.classList.add("hidden");
    sec_enc.classList.add("hidden");
};

document.getElementById("btn_edu").onclick = () => {
    sec_edu.classList.toggle("hidden");
    sec_func.classList.add("hidden");
    sec_enc.classList.add("hidden");
};

document.getElementById("btn_enc").onclick = () => {
    sec_enc.classList.toggle("hidden");
    sec_func.classList.add("hidden");
    sec_edu.classList.add("hidden");
};

/* ============================================================
   FUNCIONÁRIOS — mostrar checkboxes se escolher "específicos"
   ============================================================ */

document.getElementById("funcionario_tipo").addEventListener("change", function () {
    const lista = document.getElementById("funcionario_lista");
    lista.classList.toggle("hidden", this.value !== "especificos");
});
</script>

<!-- SCRIPT para eliminar reunião -->
<script>
    let idReuniaoParaEliminar = null;

    function abrirModalEliminar(id) {
        idReuniaoParaEliminar = id;
        const modal = document.getElementById("modalEliminarReuniao");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function fecharModalReuniao() {
        const modal = document.getElementById("modalEliminarReuniao");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        idReuniaoParaEliminar = null;
    }

    document.getElementById("btnConfirmarEliminarReuniao").addEventListener("click", function () {
        if (idReuniaoParaEliminar === null) return;

        fetch("listarreu.php?action=delete", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + idReuniaoParaEliminar
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                fecharModalReuniao();
                fecharModal();
                mostrarMensagem("eliminar", "Reunião eliminada com sucesso!");
                calendar.refetchEvents();
            } else {
                mostrarMensagem("reset", "Erro ao atualizar a reunião.");
            }
        });
    });

</script>

<!-- TOAST: reunião adicionada -->
<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'adicionado'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("adicionar", "Reunião criada com sucesso!");
});
</script>
<?php endif; ?>

</body>
</html>
