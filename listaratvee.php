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

// Verifica se é encarregado
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDEE = $_SESSION['id']; // ID do encarregado

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Atividades das Suas Crianças</title>
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
            include("sidebar_encarregado.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar atividades das suas crianças </h1>
    
            <a href="encarregado.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>
            
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php

                /* ================================
                1) BUSCAR CRIANÇAS DO ENCARREGADO
                ================================= */
                $resCri = mysqli_query($link, "
                    SELECT IDcri, nome 
                    FROM crianca 
                    WHERE estado = 1 AND IDutl = $IDEE
                ");

                if (mysqli_num_rows($resCri) === 0) {
                    echo "
                    <div class='col-span-3 text-center text-gray-500'>
                        Não existem crianças associadas à sua conta.
                    </div>";
                }

                while ($cri = mysqli_fetch_assoc($resCri)) {

                    $IDcri = $cri['IDcri'];
                    $nomeCri = $cri['nome'];

                    /* ================================
                    2) BUSCAR ATIVIDADES DA CRIANÇA
                    ================================= */
                    $resRel = mysqli_query($link, "
                        SELECT IDatv, realizada 
                        FROM crianca_atividade 
                        WHERE IDcri = $IDcri AND estado = 1
                    ");

                    while ($rel = mysqli_fetch_assoc($resRel)) {

                        $IDatv = $rel['IDatv'];
                        $realizada = $rel['realizada'];

                        /* ================================
                        3) BUSCAR DADOS DA ATIVIDADE
                        ================================= */
                        $resAtv = mysqli_query($link, "
                            SELECT * FROM atividade 
                            WHERE IDatv = $IDatv AND estado = 1
                        ");

                        $a = mysqli_fetch_assoc($resAtv);
                        if (!$a) continue;

                        /* ================================
                        4) BUSCAR RESPONSÁVEL
                        ================================= */
                        $responsavel = "—";

                        if (!empty($a['IDedu'])) {

                            // Buscar IDutl do educador
                            $resEdu = mysqli_query($link, "
                                SELECT IDutl FROM educador WHERE IDedu = {$a['IDedu']}
                            ");

                            if ($resEdu && mysqli_num_rows($resEdu) > 0) {
                                $edu = mysqli_fetch_assoc($resEdu);

                                // Buscar nome do utilizador
                                $resU = mysqli_query($link, "
                                    SELECT nome FROM utilizador WHERE IDutl = {$edu['IDutl']}
                                ");

                                if ($resU && mysqli_num_rows($resU) > 0) {
                                    $u = mysqli_fetch_assoc($resU);
                                    $responsavel = $u['nome'];
                                }
                            }

                        } else {
                            // Responsável é o admin que criou
                            $resAdmin = mysqli_query($link, "
                                SELECT nome FROM utilizador WHERE IDutl = {$a['criadopor']}
                            ");

                            if ($resAdmin && mysqli_num_rows($resAdmin) > 0) {
                                $adm = mysqli_fetch_assoc($resAdmin);
                                $responsavel = $adm['nome'];
                            }
                        }

                        /* ================================
                        5) DESCRIÇÃO CURTA
                        ================================= */
                        $desc = strlen($a['descricao']) > 60
                                ? substr($a['descricao'], 0, 60) . "..."
                                : $a['descricao'];

                        /* ================================
                        6) REALIZADA?
                        ================================= */
                        $estadoRealizada = $realizada == 1
                            ? "<span class='text-green-600 font-semibold'>Sim</span>"
                            : "<span class='text-red-600 font-semibold'>Não</span>";
                ?>

                    <div class="bg-blue-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $a['titulo'] ?></h2>

                        <div class="text-gray-700 space-y-1 mb-4">
                            <p><strong>ID:</strong> <?= $IDatv ?></p>
                            <p><strong>Criança:</strong> <?= $nomeCri ?></p>
                            <p><strong>Data/Hora:</strong> <?= $a['datahora'] ?></p>
                            <p><strong>Responsável:</strong> <?= $responsavel ?></p>
                            <p><strong>Realizada:</strong> <?= $estadoRealizada ?></p>
                            <p><strong>Descrição:</strong> <?= $desc ?></p>
                        </div>

                    </div>

                <?php
                    }
                }
                ?>

                </div>
            </div>
        </main>
    </div>

</body>
</html>
