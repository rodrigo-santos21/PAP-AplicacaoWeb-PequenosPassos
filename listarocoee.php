<?php
session_start();
include "DBConnection.php";

// Apenas encarregados podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDEE = intval($_SESSION['id']);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Ocorrências — Encarregado de Educação</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">

        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
            Ocorrências — Encarregado de Educação
        </h1>

        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Apenas ocorrências das suas crianças
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Data</th>
                        <th class="p-3 text-left">Criança</th>
                        <th class="p-3 text-left">Tipo</th>
                        <th class="p-3 text-left">Gravidade</th>
                        <th class="p-3 text-left">Descrição</th>
                        <th class="p-3 text-left">Criado por</th>
                    </tr>
                </thead>

                <tbody>
                <?php

                // 1) Buscar todas as crianças associadas ao encarregado (SEM JOIN)
                $criancasEE = [];
                $resCri = mysqli_query($link, "
                    SELECT IDcri FROM crianca 
                    WHERE IDutl = $IDEE AND estado = 1
                ");

                while ($c = mysqli_fetch_assoc($resCri)) {
                    $criancasEE[] = intval($c['IDcri']);
                }

                if (empty($criancasEE)) {
                    echo "<tr><td colspan='7' class='p-4 text-center text-gray-500'>
                            Não tem crianças associadas.
                          </td></tr>";
                } else {

                    // 2) Buscar ocorrências das crianças do EE (SEM JOIN)
                    foreach ($criancasEE as $IDcri) {

                        $result = mysqli_query($link, "
                            SELECT * FROM ocorrencia
                            WHERE estado = 1 AND IDcri = $IDcri
                            ORDER BY IDoc ASC
                        ");

                        while ($o = mysqli_fetch_assoc($result)) {

                            $IDeduCriador = intval($o['IDedu']);

                            // Nome da criança (SEM JOIN)
                            $criNome = "—";
                            $resC = mysqli_query($link, "SELECT nome FROM crianca WHERE IDcri = $IDcri");
                            if ($resC && mysqli_num_rows($resC) > 0) {
                                $cri = mysqli_fetch_assoc($resC);
                                $criNome = $cri['nome'];
                            }

                            // Nome do educador criador (SEM JOIN)
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

                            // Tipo final com tipo_outro
                            if ($o['tipo'] === "Outro" && !empty($o['tipo_outro'])) {
                                $tipoFinal = "Outro (" . $o['tipo_outro'] . ")";
                            } else {
                                $tipoFinal = $o['tipo'];
                            }

                            // Descrição curta
                            $desc = strlen($o['descricao']) > 40
                                    ? substr($o['descricao'], 0, 40) . "..."
                                    : $o['descricao'];

                            echo "
                            <tr class='border-b hover:bg-gray-100'>
                                <td class='p-3'>{$o['IDoc']}</td>
                                <td class='p-3'>{$o['datahora']}</td>
                                <td class='p-3'>{$criNome}</td>
                                <td class='p-3'>{$tipoFinal}</td>
                                <td class='p-3'>{$o['gravidade']}</td>
                                <td class='p-3'>{$desc}</td>
                                <td class='p-3'>{$eduNome}</td>
                            </tr>";
                        }
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
