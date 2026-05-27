<?php
session_start();
include "DBConnection.php";

// Apenas funcionários podem aceder
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit;
}

$nome = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Encarregados de Educação</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">

        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
            Página do Funcionário
        </h1>

        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Listar Encarregados de Educação
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Nome</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Telefone</th>
                        <th class="p-3 text-left">Data de Nascimento</th>
                        <th class="p-3 text-left">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // Buscar apenas encarregados ativos
                    $query = "SELECT * FROM utilizador 
                              WHERE estado = 1 AND tipo = 'encarregado'
                              ORDER BY IDutl";

                    $result = mysqli_query($link, $query);

                    if (!$result) {
                        die("Erro na query: " . mysqli_error($link));
                    }

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo "
                        <tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$row['IDutl']}</td>
                            <td class='p-3'>{$row['nome']}</td>
                            <td class='p-3'>{$row['email']}</td>
                            <td class='p-3'>{$row['telefone']}</td>
                            <td class='p-3'>{$row['datanascimento']}</td>

                            <td class='p-3 flex gap-2'>
                                <a href='editareefun.php?id={$row['IDutl']}'
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
            <a href="funcionario.php"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-block">
                Página Inicial
            </a>
        </div>

    </div>

</body>
</html>
