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
        "Lanche manhã: " . ($m['lanche_manha'] ?: "—") . "\n" .
        "Almoço: " . ($m['almoco'] ?: "—") . "\n" .
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
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Menus Semanais</title>

    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <style>
        .fc-event-title {
            white-space: pre-line !important;
        }
    </style>
</head>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 min-h-screen">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <?php
        if ($_SESSION['tipo'] === 'superadmin') include("sidebar_superadmin.php");
        else include("sidebar_admin.php");
    ?>

    <!-- CONTEÚDO -->
    <main class="flex-1 p-10 ml-[20%]">

        <h1 class="text-3xl font-bold text-gray-800 mb-8">Menus Semanais</h1>

        <a href="adicionar_menu_semana.php"
           class="px-4 py-2 bg-green-600 text-white rounded-md font-semibold hover:bg-green-700">
            + Adicionar Menu Semanal
        </a>

        <div id="calendar" class="mt-10 bg-white p-4 rounded shadow"></div>

    </main>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg">

        <h2 class="text-xl font-bold text-gray-800 mb-4">Editar Menu</h2>

        <form method="POST" action="editar_menu.php">

            <input type="hidden" name="IDmenu" id="edit_IDmenu">

            <!-- NOVO: guardar a data original para voltar à semana correta -->
            <input type="hidden" name="data_original" id="edit_data_original">

            <label class="block font-semibold">Data:</label>
            <input type="text" id="edit_data" disabled class="border p-2 rounded w-full mb-4">

            <label class="block font-semibold">Lanche da manhã:</label>
            <textarea name="lanche_manha" id="edit_lanche_manha" class="border p-2 rounded w-full mb-3"></textarea>

            <label class="block font-semibold">Almoço:</label>
            <textarea name="almoco" id="edit_almoco" class="border p-2 rounded w-full mb-3"></textarea>

            <label class="block font-semibold">Lanche da tarde:</label>
            <textarea name="lanche_tarde" id="edit_lanche_tarde" class="border p-2 rounded w-full mb-3"></textarea>

            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="fecharModal()"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    Cancelar
                </button>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Guardar
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

</body>
</html>
