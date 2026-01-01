<?php
session_start();

if(!isset($_SESSION['user'])){
    header("Location:index.php");
    exit;
}

$nome = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Utilizadores</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <?php include "DBConnection.php"; ?>

    <div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
            Página do Administrador
        </h1>
        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Listar Utilizadores
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Utilizador</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Password</th>
                        <th class="p-3 text-left">Tipo</th>
                        <th class="p-3 text-left">Data de Nascimento</th>
                        <th class="p-3 text-left">Telefone</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $query = "SELECT * FROM utilizador ORDER BY IDutl";
                    $result = mysqli_query($link, $query);

                    while($row = mysqli_fetch_array($result)) {
                        echo "
                        <tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$row['IDutl']}</td>
                            <td class='p-3'>{$row['nome']}</td>
                            <td class='p-3'>{$row['email']}</td>
                            <td class='p-3 text-gray-500'>Definida</td>
                            <td class='p-3'>{$row['tipo']}</td>
                            <td class='p-3'>{$row['datanascimento']}</td>
                            <td class='p-3'>{$row['telefone']}</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 text-center">
            <button
                type="button"
                onclick="window.location.href='admin.php';"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
            >
                Página Inicial
            </button>
        </div>
    </div>
</body>
</html>