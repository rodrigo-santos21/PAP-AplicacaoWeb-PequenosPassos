<?php
session_start();
include "DBConnection.php";

if(!isset($_SESSION['user'])){
    header("Location:index.php");
    exit;
}

// PROCESSO DE ELIMINAÇÃO VIA AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = $_POST['eliminar_id'];

    // Eliminar utilizador
    $stmt = mysqli_prepare($link, "DELETE FROM utilizador WHERE IDutl = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $success = mysqli_stmt_execute($stmt);

    // Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Eliminação de Conta', '$fdatahora', '$id')");
    }

    echo $success ? "ok" : "erro";
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

    <script>
    // Função para eliminar utilizador via AJAX
    function eliminarUtilizador(id) {
        if (confirm("Tem a certeza que deseja eliminar este utilizador?")) {

            fetch("listarutl.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "eliminar_id=" + encodeURIComponent(id)
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "ok") {
                    alert("Utilizador eliminado com sucesso.");
                    location.reload();
                } else {
                    alert("Erro ao eliminar utilizador.");
                }
            });
        }
    }
    </script>

</head>

<body class="bg-gray-100 min-h-screen">

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
                        <th class="p-3 text-left">Ações</th>
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
                            <td class='p-3 flex gap-2'>
                                <a href='editarutl.php?id={$row['IDutl']}'
                                   class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition'>
                                   Editar
                                </a>

                                <button onclick='eliminarUtilizador({$row['IDutl']})'
                                    class=\"px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition\">
                                    Eliminar
                                </button>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 text-center">
            <a href="admin.php"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-block">
                Página Inicial
            </a>
        </div>
    </div>
</body>
</html>