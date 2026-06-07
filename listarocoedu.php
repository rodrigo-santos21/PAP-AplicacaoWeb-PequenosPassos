<?php
session_start();
include "DBConnection.php";

// ===============================
// PROCESSAR ELIMINAÇÃO (AJAX)
// ===============================
if (isset($_GET['action']) && $_GET['action'] === "delete") {

    $id = intval($_POST['id']);
    $IDutl = intval($_SESSION['id']);

    // Buscar IDedu do utilizador
    $resEdu = mysqli_query($link, "SELECT IDedu FROM educador WHERE IDutl = $IDutl AND estado = 1");
    if (!$resEdu || mysqli_num_rows($resEdu) == 0) {
        echo "erro";
        exit;
    }
    $IDedu = intval(mysqli_fetch_assoc($resEdu)['IDedu']);

    // Verificar se a ocorrência pertence a uma criança do educador
    $check = mysqli_query($link, "
        SELECT o.IDoc 
        FROM ocorrencia o
        JOIN crianca_educador ce ON ce.IDcri = o.IDcri
        WHERE o.IDoc = $id AND ce.IDedu = $IDedu AND ce.estado = 1
    ");

    if (mysqli_num_rows($check) == 0) {
        echo "erro";
        exit;
    }

    // Soft delete
    mysqli_query($link, "UPDATE ocorrencia SET estado = 0 WHERE IDoc = $id");

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");
    mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                         VALUES ('Ocorrência desativada (ID $id)', '$fdatahora', $IDutl)");

    echo "ok";
    exit;
}

// ===============================
// BUSCAR FOTO DO UTILIZADOR
// ===============================
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas educadores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

// Buscar IDedu correto
$res = mysqli_query($link, "SELECT IDedu FROM educador WHERE IDutl = $IDutl AND estado = 1");
if (!$res || mysqli_num_rows($res) == 0) {
    die("Erro: Educador não encontrado.");
}
$row = mysqli_fetch_assoc($res);
$IDedu = intval($row['IDedu']);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Ocorrências do Educador</title>
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

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar Ocorrências das crianças da creche </h1>

            <a href="educador.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>
    
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php

                $query = "
                    SELECT * FROM ocorrencia
                    WHERE estado = 1
                    ORDER BY IDoc DESC
                ";

                $result = mysqli_query($link, $query);

                while ($o = mysqli_fetch_assoc($result)) {

                    $IDcri = intval($o['IDcri']);

                    // Verificar se a criança pertence ao educador
                    $resRel = mysqli_query($link, "
                        SELECT 1 FROM crianca_educador
                        WHERE IDcri = $IDcri AND IDedu = $IDedu AND estado = 1
                    ");

                    if (mysqli_num_rows($resRel) == 0) continue;

                    // Nome da criança
                    $criNome = "—";
                    $resCri = mysqli_query($link, "SELECT nome FROM crianca WHERE IDcri = $IDcri AND estado = 1");
                    if ($resCri && mysqli_num_rows($resCri) > 0) {
                        $cri = mysqli_fetch_assoc($resCri);
                        $criNome = $cri['nome'];
                    }

                    // Nome do educador criador
                    $eduNome = "—";
                    $IDeduCriador = intval($o['IDedu']);

                    if ($IDeduCriador == 0) {
                        $eduNome = "Administrador";
                    } else {
                        $resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDedu = $IDeduCriador");
                        if ($resEdu && mysqli_num_rows($resEdu) > 0) {
                            $edu = mysqli_fetch_assoc($resEdu);
                            $IDutlCriador = intval($edu['IDutl']);

                            $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutlCriador");
                            if ($resU && mysqli_num_rows($resU) > 0) {
                                $u = mysqli_fetch_assoc($resU);
                                $eduNome = $u['nome'];
                            }
                        }
                    }

                    // Tipo final
                    if ($o['tipo'] === "Outro" && !empty($o['tipo_outro'])) {
                        $tipoFinal = "Outro (" . $o['tipo_outro'] . ")";
                    } else {
                        $tipoFinal = $o['tipo'];
                    }

                    // Descrição curta
                    $desc = strlen($o['descricao']) > 60
                            ? substr($o['descricao'], 0, 60) . "..."
                            : $o['descricao'];
                ?>

                    <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 mb-2">Ocorrência #<?= $o['IDoc'] ?></h2>

                        <div class="text-gray-700 space-y-1 mb-4">
                            <p><strong>Data:</strong> <?= $o['datahora'] ?></p>
                            <p><strong>Criança:</strong> <?= $criNome ?></p>
                            <p><strong>Tipo:</strong> <?= $tipoFinal ?></p>
                            <p><strong>Gravidade:</strong> <?= $o['gravidade'] ?></p>
                            <p><strong>Criado por:</strong> <?= $eduNome ?></p>
                            <p><strong>Descrição:</strong> <?= $desc ?></p>
                        </div>

                        <div class="flex gap-3">

                            <!-- Editar -->
                            <button onclick="window.location.href='editarocoedu.php?id=<?= $o['IDoc'] ?>'"
                                class="text-gray-500 hover:text-yellow-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                </svg>
                            </button>

                            <!-- Eliminar -->
                            <button onclick="eliminarOcorrencia(<?= $o['IDoc'] ?>)"
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

<!-- MODAL ELIMINAR OCORRÊNCIA -->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Eliminação</h2>

        <p class="text-gray-700 mb-6">
            Tens a certeza que desejas eliminar esta ocorrência?
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


<!-- SCRIPT para eliminar ocorrência -->
<script>
let idOcorrenciaParaEliminar = null;

function eliminarOcorrencia(id) {
    idOcorrenciaParaEliminar = id;
    const modal = document.getElementById("modalEliminar");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function fecharModal() {
    const modal = document.getElementById("modalEliminar");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    idOcorrenciaParaEliminar = null;
}

document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

    if (idOcorrenciaParaEliminar === null) return;

    fetch("listarocoedu.php?action=delete", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + idOcorrenciaParaEliminar
    })
    .then(r => r.text())
    .then(res => {

        res = res.trim();

        if (res === "ok") {
            fecharModal();
            mostrarMensagem("Ocorrência eliminada com sucesso.", "green");
            setTimeout(() => location.reload(), 1200);
            return;
        }

        mostrarMensagem("Erro ao eliminar ocorrência.", "red");
    });
});
</script>

</body>
</html>
