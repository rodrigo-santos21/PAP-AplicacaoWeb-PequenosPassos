<?php
session_start();
include "DBConnection.php";

//verifica se o utilizador é administrador e se está autenticado
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

// PROCESSO DE ELIMINAÇÃO VIA AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = $_POST['eliminar_id'];

    // Eliminar reunião
    $stmt = mysqli_prepare($link, "DELETE FROM reuniao WHERE IDreu = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $success = mysqli_stmt_execute($stmt);

    // Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Eliminação de reunião', '$fdatahora', '{$_SESSION['id']}')");
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
    <title>Listar Reuniões</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
    // Função para eliminar reunião via AJAX
    function eliminarReuniao(id) {
        if (confirm("Tem a certeza que deseja eliminar este reunião?")) {

            fetch("listarreu.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "eliminar_id=" + encodeURIComponent(id)
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "ok") {
                    alert("Reunião eliminada com sucesso.");
                    location.reload();
                } else {
                    alert("Erro ao eliminar reunião.");
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
            Listar Reuniões
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Título</th>
                        <th class="p-3 text-left">Data e Hora</th>
                        <th class="p-3 text-left">Localidade</th>
                        <th class="p-3 text-left">Objetivo</th>
                        <th class="p-3 text-left">Criado por</th>
                        <th class="p-3 text-left">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $query = "
                        SELECT r.IDreu, r.titulo, r.datahora, r.localidade, r.objetivo, u.nome
                        FROM reuniao r, utilizador u
                        WHERE r.criadopor = u.IDutl
                        ORDER BY r.IDreu
                    ";

                    $result = mysqli_query($link, $query);

                    while ($row = mysqli_fetch_array($result)) {

                    echo "<tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$row['IDreu']}</td>
                            <td class='p-3'>{$row['titulo']}</td>
                            <td class='p-3'>{$row['datahora']}</td>
                            <td class='p-3'>{$row['localidade']}</td>
                            <td class='p-3'>{$row['objetivo']}</td>
                            <td class='p-3'>{$row['nome']}</td>
                            <td class='p-3 flex gap-2'>";

                        echo "
                            <a href='editarreu.php?id={$row['IDreu']}'
                            class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition'>
                            Editar
                            </a>

                            <button onclick='eliminarReuniao({$row['IDreu']})'
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