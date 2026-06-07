<?php
session_start();
include "DBConnection.php";

/* ================================
   0) PROCESSAR ELIMINAÇÃO VIA AJAX
================================ */
if (isset($_GET['action']) && $_GET['action'] === "delete") {

    $id = intval($_POST['id']);
    $IDutl = intval($_SESSION['id']);

    // Verificar se a atividade pertence ao educador
    $resCheck = mysqli_query($link, "
        SELECT IDatv FROM atividade 
        WHERE IDatv = $id AND criadopor = $IDutl AND estado = 1
    ");

    if (mysqli_num_rows($resCheck) == 0) {
        echo "erro_permissao";
        exit;
    }

    // Soft delete da atividade
    mysqli_query($link, "UPDATE atividade SET estado = 0 WHERE IDatv = $id");

    // Soft delete das relações com crianças
    mysqli_query($link, "
        UPDATE crianca_atividade 
        SET estado = 0 
        WHERE IDatv = $id
    ");

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");
    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Educador eliminou atividade (ID $id)', '$fdatahora', '$IDutl')
    ");

    echo "ok";
    exit;
}

/* ================================
   1) BUSCAR FOTO DO UTILIZADOR
================================ */
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

/* ================================
   2) VERIFICAR PERMISSÃO
================================ */
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

/* ================================
   3) BUSCAR ID DO EDUCADOR
================================ */
$resEdu = mysqli_query($link, "
    SELECT IDedu, IDsala 
    FROM educador 
    WHERE IDutl = $IDutl AND estado = 1
");

if (!$resEdu || mysqli_num_rows($resEdu) === 0) {
    die("Erro: Educador não encontrado.");
}

$edu    = mysqli_fetch_assoc($resEdu);
$IDedu  = $edu['IDedu'];
$IDsala = $edu['IDsala'];

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Atividades da Sala</title>
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
            include("sidebar_educador.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar atividades das crianças da creche criadas por si </h1>

            <a href="educador.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>
    
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php
                $resAtv = mysqli_query($link, "
                    SELECT * 
                    FROM atividade
                    WHERE criadopor = $IDutl
                    AND estado = 1
                    ORDER BY IDatv DESC
                ");

                while ($a = mysqli_fetch_assoc($resAtv)) {

                    $IDatv = $a['IDatv'];

                    // Contar crianças associadas
                    $resCount = mysqli_query($link, "
                        SELECT COUNT(*) AS total 
                        FROM crianca_atividade 
                        WHERE IDatv = $IDatv AND estado = 1
                    ");
                    $totalCri = mysqli_fetch_assoc($resCount)['total'];

                    // Contar crianças que realizaram
                    $resReal = mysqli_query($link, "
                        SELECT COUNT(*) AS total 
                        FROM crianca_atividade 
                        WHERE IDatv = $IDatv AND estado = 1 AND realizada = 1
                    ");
                    $totalReal = mysqli_fetch_assoc($resReal)['total'];

                    // Descrição curta
                    $desc = strlen($a['descricao']) > 60
                            ? substr($a['descricao'], 0, 60) . "..."
                            : $a['descricao'];
                ?>

                    <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $a['titulo'] ?></h2>

                        <div class="text-gray-700 space-y-1 mb-4">
                            <p><strong>ID:</strong> <?= $a['IDatv'] ?></p>
                            <p><strong>Data/Hora:</strong> <?= $a['datahora'] ?></p>
                            <p><strong>Crianças:</strong> <?= $totalCri ?></p>
                            <p><strong>Realizadas:</strong> <?= $totalReal ?></p>
                            <p><strong>Descrição:</strong> <?= $desc ?></p>
                        </div>

                        <div class="flex gap-3">

                            <!-- Editar -->
                            <button onclick="window.location.href='editaratvedu.php?id=<?= $a['IDatv'] ?>'"
                                class="text-gray-500 hover:text-yellow-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                </svg>
                            </button>

                            <!-- Eliminar -->
                            <button onclick="eliminarAtividade(<?= $a['IDatv'] ?>)"
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
    
<!-- MODAL ELIMINAR ATIVIDADE -->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Eliminação</h2>

        <p class="text-gray-700 mb-6">
            Tens a certeza que desejas eliminar esta atividade?
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

<!-- SCRIPT para eliminar atividade -->
<script>
let idAtividadeParaEliminar = null;

function eliminarAtividade(id) {
    idAtividadeParaEliminar = id;
    const modal = document.getElementById("modalEliminar");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function fecharModal() {
    const modal = document.getElementById("modalEliminar");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    idAtividadeParaEliminar = null;
}

document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

    if (idAtividadeParaEliminar === null) return;

    fetch("listaratvedu.php?action=delete", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + idAtividadeParaEliminar
    })
    .then(r => r.text())
    .then(res => {

        res = res.trim();

        if (res === "ok") {
            fecharModal();
            mostrarMensagem("Atividade eliminada com sucesso.", "green");
            setTimeout(() => location.reload(), 1200);
            return;
        }

        if (res === "erro_permissao") {
            mostrarMensagem("Não tem permissão para eliminar esta atividade.", "red");
            fecharModal();
            return;
        }

        mostrarMensagem("Erro ao eliminar atividade.", "red");
    });
});
</script>

</body>
</html>
