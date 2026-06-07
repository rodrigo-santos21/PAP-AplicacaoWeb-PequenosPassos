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

// Apenas funcionários podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDutl = intval($_SESSION['id']);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Ocorrências — Funcionário</title>
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
            include("sidebar_funcionario.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar Ocorrências </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php

                // Buscar todas as ocorrências ativas (SEM JOIN)
                $query = "
                    SELECT * FROM ocorrencia
                    WHERE estado = 1
                    ORDER BY IDoc ASC
                ";

                $result = mysqli_query($link, $query);

                while ($o = mysqli_fetch_assoc($result)) {

                    $IDcri = intval($o['IDcri']);
                    $IDeduCriador = intval($o['IDedu']);

                    // Nome da criança
                    $criNome = "—";
                    $resCri = mysqli_query($link, "SELECT nome FROM crianca WHERE IDcri = $IDcri");
                    if ($resCri && mysqli_num_rows($resCri) > 0) {
                        $cri = mysqli_fetch_assoc($resCri);
                        $criNome = $cri['nome'];
                    }

                    // Nome do educador criador
                    $eduNome = "—";
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
                            <p><strong>Descrição:</strong> <?= $desc ?></p>
                            <p><strong>Criado por:</strong> <?= $eduNome ?></p>
                        </div>

                    </div>

                <?php } ?>

                </div>

            </div>
        </main>
    </div>

</body>
</html>
