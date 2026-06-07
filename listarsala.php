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


if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

/* ============================================================
   PROCESSAR ELIMINAÇÃO (TEM DE VIR ANTES DE QUALQUER HTML)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = intval($_POST['eliminar_id']);

    // Verificar dependências
    $cri = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS total FROM crianca WHERE IDsala = $id AND estado = 1"
    ))['total'];

    $edu = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS total FROM educador WHERE IDsala = $id AND estado = 1"
    ))['total'];

    if ($cri > 0 || $edu > 0) {
        echo "erro_dependencias";
        exit;
    }

    // Eliminar sala (soft delete)
    mysqli_query($link, "UPDATE sala SET estado = 0 WHERE IDsala = $id");

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");
    $IDutl = $_SESSION['id'];

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Sala eliminada (ID $id)', '$fdatahora', '$IDutl')
    ");

    echo "ok";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Salas</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php
            include("sidebar_admin.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Lista de salas da creche </h1>

            <a href="admin.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php
                $res = mysqli_query($link, "SELECT * FROM sala WHERE estado = 1 ORDER BY IDsala");

                while ($s = mysqli_fetch_assoc($res)) {

                    $IDsala = $s['IDsala'];

                    // Contar crianças
                    $cri = mysqli_fetch_assoc(mysqli_query($link,
                        "SELECT COUNT(*) AS total FROM crianca WHERE IDsala = $IDsala AND estado = 1"
                    ))['total'];

                    // Contar educadores
                    $edu = mysqli_fetch_assoc(mysqli_query($link,
                        "SELECT COUNT(*) AS total FROM educador WHERE IDsala = $IDsala AND estado = 1"
                    ))['total'];
                ?>

                    <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $s['nome'] ?></h2>

                        <div class="text-gray-700 space-y-1 mb-4">
                            <p><strong>ID:</strong> <?= $s['IDsala'] ?></p>
                            <p><strong>Capacidade:</strong> <?= $s['capacidade'] ?></p>
                            <p><strong>Crianças:</strong> <?= $cri ?></p>
                            <p><strong>Educadores:</strong> <?= $edu ?></p>
                        </div>

                        <div class="flex gap-3">

                            <!-- Ícone Editar -->
                            <button onclick="window.location.href='editarsala.php?id=<?= $s['IDsala'] ?>'"
                                class="text-gray-500 hover:text-yellow-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                </svg>
                            </button>

                            <!-- Ícone Eliminar -->
                            <button onclick="eliminarSala(<?= $s['IDsala'] ?>)"
                                class="text-gray-500 hover:text-red-600 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 011-1h4a1 1 0 011 1m-6 0h6" />
                                </svg>
                            </button>

                        </div>
                    </div>

                <?php } ?>

                </div>
            </div>
        </main>
    </div>

<!-- Modal de Eliminar -->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Eliminação</h2>

        <p class="text-gray-700 mb-6">
            Tens a certeza que desejas eliminar esta sala?
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="fecharModal()"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                Cancelar
            </button>

            <button id="btnConfirmarEliminar"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Eliminar
            </button>
        </div>
    </div>
</div>

<!-- SCRIPT para eliminar sala -->
<script>
    let idSalaParaEliminar = null;

    function eliminarSala(id) {
        idSalaParaEliminar = id;
        const modal = document.getElementById("modalEliminar");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function fecharModal() {
        const modal = document.getElementById("modalEliminar");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        idSalaParaEliminar = null;
    }

    document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

        if (idSalaParaEliminar === null) return;

        fetch("listarsala.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "eliminar_id=" + idSalaParaEliminar
        })
        .then(r => r.text())
        .then(res => {

            res = res.trim();

            if (res === "ok") {
                fecharModal();
                mostrarMensagem("Sala eliminada com sucesso.", "green");
                setTimeout(() => location.reload(), 1200);
                return;
            }

            if (res === "erro_dependencias") {
                fecharModal();
                mostrarMensagem("Não é possível eliminar esta sala porque existem crianças ou educadores associados.", "red");
                return;
            }

            console.log("Resposta inesperada:", res);
            mostrarMensagem("Erro ao eliminar sala.", "red");
        });
    });

    function mostrarMensagem(texto, cor) {
        const div = document.createElement("div");
        div.className = `fixed top-5 right-5 px-4 py-2 rounded shadow-lg text-white bg-${cor}-600`;
        div.textContent = texto;
        document.body.appendChild(div);

        setTimeout(() => div.remove(), 2500);
    }
</script>

</body>
</html>
