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

// Apenas admin e superadmin podem aceder
if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['administrador', 'superadmin'])) {
    header("Location: index.php?erro=permissao");
    exit;
}

// Buscar todos os menus
$menus = mysqli_query($link, "
    SELECT * FROM menu_semana 
    WHERE estado = 1
    ORDER BY data ASC
");

$eventos = [];

while ($m = mysqli_fetch_assoc($menus)) {

    $titulo =
    "Lanche manhã: " . ($m['lanche_manha'] ?: "—") . "\\n" .
    "Almoço: " . ($m['almoco'] ?: "—") . "\\n" .
    "Lanche tarde: " . ($m['lanche_tarde'] ?: "—");

    $eventos[] = [
        "id" => $m['IDmenu'],
        "title" => $titulo,
        "start" => $m['data'],
        "allDay" => true
    ];
}

?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menus Semanais</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <style>
        .fc-event-title {
            white-space: pre-line !important;
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
        class="hidden fixed top-5 right-5 bg-white dark:bg-gray-800 shadow-lg border-l-4 rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

    <!-- WRAPPER FLEX -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php
                $tipo = $_SESSION['tipo'];

                if ($tipo === "administrador") {
                    include("sidebar_admin.php");
                } elseif ($tipo === "superadmin") {
                    include("sidebar_superadmin.php");
                } 
            ?>
        </div>

        <!-- MENU MOBILE -->
        <?php
            if ($tipo === "administrador") {
                include("menu_mobile_admin.php");
            } elseif ($tipo === "superadmin") {
                include("menu_mobile_superadmin.php");
            } 
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">Menus Semanais</h1>
            
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                
                <?php if ($tipo === "administrador") { ?>
                    <a href="admin.php" 
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded-md font-semibold hover:bg-blue-700 dark:hover:bg-blue-600">
                        ← Voltar
                    </a>
                <?php } elseif ($tipo === "superadmin") {?>
                    <a href="superadmin.php" 
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded-md font-semibold hover:bg-blue-700 dark:hover:bg-blue-600">
                        ← Voltar
                    </a>
                <?php } ?>

                <a href="adicionar_menu_semana.php"
                class="px-4 py-2 bg-green-600 dark:bg-green-700 text-white rounded-md font-semibold hover:bg-green-700 dark:hover:bg-green-600">
                    + Adicionar Menu Semanal
                </a>

            </div>

            <div id="calendar" class="bg-white dark:bg-gray-800 p-4 rounded shadow"></div>

        </main>
    </div>

<!-- MODAL EDITAR -->
<div id="modalEditar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-lg">

        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Editar Menu</h2>

        <form method="POST" action="editar_menu.php">

            <input type="hidden" name="IDmenu" id="edit_IDmenu">
            <input type="hidden" name="data_original" id="edit_data_original">

            <label class="block font-semibold text-gray-800 dark:text-gray-200">Data:</label>
            <input type="text" id="edit_data" disabled 
                   class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-4 
                          bg-gray-100 dark:bg-gray-700 dark:text-gray-100">

            <label class="block font-semibold text-gray-800 dark:text-gray-200">Lanche da manhã:</label>
            <textarea name="lanche_manha" id="edit_lanche_manha"
                      class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                             bg-white dark:bg-gray-700 dark:text-gray-100"></textarea>

            <label class="block font-semibold text-gray-800 dark:text-gray-200">Almoço:</label>
            <textarea name="almoco" id="edit_almoco"
                      class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                             bg-white dark:bg-gray-700 dark:text-gray-100"></textarea>

            <label class="block font-semibold text-gray-800 dark:text-gray-200">Lanche da tarde:</label>
            <textarea name="lanche_tarde" id="edit_lanche_tarde"
                      class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                             bg-white dark:bg-gray-700 dark:text-gray-100"></textarea>

            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="fecharModal()"
                        class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white rounded hover:bg-gray-600 dark:hover:bg-gray-500">
                    Cancelar
                </button>

                <button type="button"
                        onclick="abrirModalEliminar()"
                        class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white rounded hover:bg-red-700 dark:hover:bg-red-600">
                    Eliminar
                </button>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded hover:bg-blue-700 dark:hover:bg-blue-600">
                    Guardar
                </button>
            </div>

        </form>
    </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-md">

        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Eliminar Menu</h2>

        <p class="text-gray-700 dark:text-gray-300 mb-6">
            Tens a certeza que desejas eliminar este menu semanal?
        </p>

        <form method="POST" action="eliminar_menu.php">
            <input type="hidden" name="IDmenu" id="delete_IDmenu">

            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModalEliminar()"
                        class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white rounded hover:bg-gray-600 dark:hover:bg-gray-500">
                    Cancelar
                </button>

                <button type="submit"
                        class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white rounded hover:bg-red-700 dark:hover:bg-red-600">
                    Eliminar
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function abrirModal(menu) {

    // ID do menu
    document.getElementById("edit_IDmenu").value = menu.id;

    // Data visível
    document.getElementById("edit_data").value = menu.startStr;

    // NOVO: guardar a data original para o redirect
    document.getElementById("edit_data_original").value = menu.startStr;

    // Separar as linhas do evento
    const linhas = menu.title.split("\n");

    document.getElementById("edit_lanche_manha").value =
        linhas[0].replace("Lanche manhã: ", "");

    document.getElementById("edit_almoco").value =
        linhas[1].replace("Almoço: ", "");

    document.getElementById("edit_lanche_tarde").value =
        linhas[2].replace("Lanche tarde: ", "");

    // Abrir modal
    const modal = document.getElementById("modalEditar");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function fecharModal() {
    const modal = document.getElementById("modalEditar");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}

function abrirModalEliminar() {
    // Copiar o ID do menu que está no modal de edição
    const id = document.getElementById("edit_IDmenu").value;
    document.getElementById("delete_IDmenu").value = id;

    fecharModal(); // fecha o modal de edição

    const modal = document.getElementById("modalEliminar");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function fecharModalEliminar() {
    const modal = document.getElementById("modalEliminar");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}


document.addEventListener('DOMContentLoaded', function () {

    const calendarEl = document.getElementById('calendar');

    // NOVO: ler a data enviada pelo editar_menu.php
    let dataInicial = new URLSearchParams(window.location.search).get("data");

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridWeek',
        locale: 'pt',
        firstDay: 1,

        // NOVO: se houver data no URL, abrir nessa semana
        initialDate: dataInicial ? dataInicial : undefined,

        events: <?php echo json_encode($eventos); ?>,

        eventClick: function(info) {
            abrirModal(info.event);
        }
    });

    calendar.render();
});
</script>

<!-- TOAST -->
<!-- TOAST: menu criado -->
<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'adicionado'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("adicionar", "Menu semanal criado com sucesso!");
});
</script>
<?php endif; ?>

<!-- TOAST: menu editado -->
<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'editado'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("editar", "Menu semanal atualizado com sucesso!");
});
</script>
<?php endif; ?>

<!-- TOAST: menu eliminado -->
<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'eliminado'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("eliminar", "Menu semanal eliminado com sucesso!");
});
</script>
<?php endif; ?>

<!-- TOAST: erro ao atualizar -->
<?php if (isset($_GET['erro']) && $_GET['erro'] === 'atualizar'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("reset", "Erro ao atualizar o menu semanal.");
});
</script>
<?php endif; ?>

</body>
</html>