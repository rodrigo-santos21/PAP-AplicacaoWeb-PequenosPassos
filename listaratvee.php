<?php
session_start();
include "DBConnection.php";

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

<body class="bg-gray-100 min-h-screen">

<div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">

    <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
        Atividades das Suas Crianças
    </h1>

    <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
        Apenas atividades associadas às crianças da sua conta
    </h3>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white shadow rounded-lg">
            <thead>
                <tr class="bg-blue-600 text-white">
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Criança</th>
                    <th class="p-3 text-left">Título</th>
                    <th class="p-3 text-left">Data/Hora</th>
                    <th class="p-3 text-left">Responsável</th>
                    <th class="p-3 text-left">Realizada</th>
                    <th class="p-3 text-left">Descrição</th>
                </tr>
            </thead>

            <tbody>
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
                <tr>
                    <td colspan='7' class='p-4 text-center text-gray-500'>
                        Não existem crianças associadas à sua conta.
                    </td>
                </tr>";
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
                    $desc = strlen($a['descricao']) > 40
                            ? substr($a['descricao'], 0, 40) . "..."
                            : $a['descricao'];

                    /* ================================
                       6) REALIZADA?
                    ================================= */
                    $estadoRealizada = $realizada == 1
                        ? "<span class='text-green-600 font-semibold'>Sim</span>"
                        : "<span class='text-red-600 font-semibold'>Não</span>";

                    echo "
                    <tr class='border-b hover:bg-gray-100'>
                        <td class='p-3'>$IDatv</td>
                        <td class='p-3'>$nomeCri</td>
                        <td class='p-3'>{$a['titulo']}</td>
                        <td class='p-3'>{$a['datahora']}</td>
                        <td class='p-3'>$responsavel</td>
                        <td class='p-3'>$estadoRealizada</td>
                        <td class='p-3'>$desc</td>
                    </tr>";
                }
            }
            ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6 text-center">
        <a href="encarregado.php"
            class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-block">
            Página Inicial
        </a>
    </div>

</div>

</body>
</html>
