<?php
session_start();
include "DBConnection.php";

// BUSCA A FOTO DE PERFIL DO SUPERADMIN
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas superadmins podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDutl = intval($_SESSION['id']);

/* ============================================================
   PROCESSO DE DESATIVAÇÃO (SOFT DELETE)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desativar_id'])) {

    $id = intval($_POST['desativar_id']);

    mysqli_query($link, "UPDATE ocorrencia SET estado = 0 WHERE IDoc = $id");

    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Ocorrência desativada pelo superadmin (ID $id)', '$fdatahora', '$IDutl')
    ");

    echo "ok";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Ocorrências — Superadmin</title>
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

<div class="flex min-h-screen">

    <!-- SIDEBAR SUPERADMIN -->
    <?php include("sidebar_superadmin.php"); ?>

    <!-- CONTEÚDO -->
    <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto no-scrollbar">

        <h1 class="text-3xl font-bold text-gray-800 mb-8">Ocorrências da Creche</h1>

        <a href="superadmin.php"
           class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
            ← Voltar
        </a>

        <div class="w-full bg-white shadow-lg rounded-lg p-8">

            <!-- GRID DE CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php
                $result = mysqli_query($link, "
                    SELECT * FROM ocorrencia
                    WHERE estado = 1
                    ORDER BY IDoc DESC
                ");

                while ($o = mysqli_fetch_assoc($result)) {

                    $IDcri = intval($o['IDcri']);
                    $IDeduCriador = intval($o['IDedu']);

                    // Nome da criança
                    $criNome = "—";
                    $resCri = mysqli_query($link, "SELECT nome FROM crianca WHERE IDcri = $IDcri");
                    if ($resCri && mysqli_num_rows($resCri) > 0) {
                        $criNome = mysqli_fetch_assoc($resCri)['nome'];
                    }

                    // Nome do educador criador
                    $eduNome = "—";
                    $resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDedu = $IDeduCriador");
                    if ($resEdu && mysqli_num_rows($resEdu) > 0) {
                        $IDutlCriador = mysqli_fetch_assoc($resEdu)['IDutl'];

                        $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutlCriador");
                        if ($resU && mysqli_num_rows($resU) > 0) {
                            $eduNome = mysqli_fetch_assoc($resU)['nome'];
                        }
                    }

                    // Tipo final
                    $tipoFinal = ($o['tipo'] === "Outro" && !empty($o['tipo_outro']))
                                ? "Outro (" . $o['tipo_outro'] . ")"
                                : $o['tipo'];

                    // Descrição curta
                    $desc = strlen($o['descricao']) > 60
                            ? substr($o['descricao'], 0, 60) . "..."
                            : $o['descricao'];
                ?>

                <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                    <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $criNome ?></h2>

                    <p class="text-gray-700"><strong>ID:</strong> <?= $o['IDoc'] ?></p>
                    <p class="text-gray-700"><strong>Data:</strong> <?= $o['datahora'] ?></p>
                    <p class="text-gray-700"><strong>Tipo:</strong> <?= $tipoFinal ?></p>
                    <p class="text-gray-700"><strong>Gravidade:</strong> <?= $o['gravidade'] ?></p>
                    <p class="text-gray-700"><strong>Criado por:</strong> <?= $eduNome ?></p>

                    <p class="text-gray-600 mt-2"><strong>Descrição:</strong> <?= $desc ?></p>

                    <div class="flex gap-3 justify-end mt-4">

                        <!-- EDITAR -->
                        <button onclick="window.location.href='editarocosuper.php?id=<?= $o['IDoc'] ?>'"
                            class="text-gray-500 hover:text-yellow-500 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                            </svg>
                        </button>

                        <!-- ELIMINAR -->
                        <button onclick="desativarOcorrencia(<?= $o['IDoc'] ?>)"
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

<!-- MODAL DE CONFIRMAÇÃO -->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Desativação</h2>

        <p class="text-gray-700 mb-6">
            Tens a certeza que desejas desativar esta ocorrência?
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="fecharModal()"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                Cancelar
            </button>

            <button id="btnConfirmarEliminar"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Desativar
            </button>
        </div>
    </div>
</div>

<script>
    let idParaEliminar = null;

    function desativarOcorrencia(id) {
        idParaEliminar = id;
        const modal = document.getElementById("modalEliminar");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function fecharModal() {
        const modal = document.getElementById("modalEliminar");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        idParaEliminar = null;
    }

    document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

        if (idParaEliminar === null) return;

        fetch("listarocosuper.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "desativar_id=" + idParaEliminar
        })
        .then(r => r.text())
        .then(res => {
            res = res.trim();

            if (res === "ok") {
                fecharModal();
                mostrarMensagem("Ocorrência desativada com sucesso.", "green");
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarMensagem("Erro ao desativar ocorrência.", "red");
            }
        });
    });

    function mostrarMensagem(texto, cor) {
        const div = document.createElement("div");
        div.className = `fixed top-5 right-5 px-4 py-2 rounded shadow-lg text-white bg-${cor}-600`;
        div.textContent = texto;
        document.body.appendChild(div);

        setTimeout(() => div.remove(), 2000);
    }
</script>

</body>
</html>
