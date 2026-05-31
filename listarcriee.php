<?php
session_start();
include "DBConnection.php";

// Verifica se o utilizador é encarregado de educação
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDEE = intval($_SESSION['id']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>As Minhas Crianças</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">

        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
            Crianças — Encarregado de Educação
        </h1>

        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Apenas as crianças associadas à sua conta
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Nome</th>
                        <th class="p-3 text-left">Data Nasc.</th>
                        <th class="p-3 text-left">Sexo</th>
                        <th class="p-3 text-left">Sala</th>
                        <th class="p-3 text-left">Educador</th>
                        <th class="p-3 text-left">Observações</th>
                        <th class="p-3 text-left">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php

                    // Buscar apenas as crianças do encarregado (SEM JOIN)
                    $query = "
                        SELECT * FROM crianca 
                        WHERE estado = 1 AND IDutl = $IDEE
                        ORDER BY IDcri
                    ";

                    $result = mysqli_query($link, $query);

                    if (!$result || mysqli_num_rows($result) === 0) {
                        echo "
                        <tr>
                            <td colspan='8' class='p-4 text-center text-gray-500'>
                                Não existem crianças associadas à sua conta.
                            </td>
                        </tr>";
                    }

                    while ($cri = mysqli_fetch_assoc($result)) {

                        // Buscar sala (SEM JOIN)
                        $salaNome = "—";
                        if (!empty($cri['IDsala'])) {
                            $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = {$cri['IDsala']}");
                            if ($resSala && mysqli_num_rows($resSala) > 0) {
                                $sala = mysqli_fetch_assoc($resSala);
                                $salaNome = $sala['nome'];
                            }
                        }

                        // Buscar educador associado (SEM JOIN)
                        $eduNome = "—";

                        $resEduRel = mysqli_query($link, "
                            SELECT IDedu FROM crianca_educador 
                            WHERE IDcri = {$cri['IDcri']} AND estado = 1
                        ");

                        if ($resEduRel && mysqli_num_rows($resEduRel) > 0) {
                            $rel = mysqli_fetch_assoc($resEduRel);
                            $IDedu = intval($rel['IDedu']);

                            // Buscar IDutl do educador
                            $resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDedu = $IDedu");
                            if ($resEdu && mysqli_num_rows($resEdu) > 0) {
                                $edu = mysqli_fetch_assoc($resEdu);
                                $IDutlEdu = intval($edu['IDutl']);

                                // Buscar nome do utilizador
                                $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutlEdu");
                                if ($resU && mysqli_num_rows($resU) > 0) {
                                    $u = mysqli_fetch_assoc($resU);
                                    $eduNome = $u['nome'];
                                }
                            }
                        }

                        // Sexo formatado
                        $sexo = $cri['sexo'] === "M" ? "Masculino" :
                                ($cri['sexo'] === "F" ? "Feminino" : "Indefinido");

                        // Observações
                        $obs = !empty($cri['observacoes']) ? $cri['observacoes'] : "—";

                        echo "
                        <tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$cri['IDcri']}</td>
                            <td class='p-3'>{$cri['nome']}</td>
                            <td class='p-3'>{$cri['datanascimento']}</td>
                            <td class='p-3'>{$sexo}</td>
                            <td class='p-3'>{$salaNome}</td>
                            <td class='p-3'>{$eduNome}</td>
                            <td class='p-3'>{$obs}</td>

                            <td class='p-3'>
                                <a href='editarcriee.php?id={$cri['IDcri']}'
                                    class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition'>
                                    Editar
                                </a>
                            </td>
                        </tr>";
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
