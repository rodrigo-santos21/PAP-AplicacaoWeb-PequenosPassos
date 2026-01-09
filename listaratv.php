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

    // Eliminar atividade
    $stmt = mysqli_prepare($link, "DELETE FROM atividade WHERE IDatv = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $success = mysqli_stmt_execute($stmt);

    // Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Eliminação de Atividade', '$fdatahora', '{$_SESSION['id']}')");
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
    <title>Listar Atividades</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
    // Função para eliminar utilizador via AJAX
    function eliminarAtividade(id) {
        if (confirm("Tem a certeza que deseja eliminar este atividade?")) {

            fetch("listaratv.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "eliminar_id=" + encodeURIComponent(id)
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "ok") {
                    alert("Atividade eliminada com sucesso.");
                    location.reload();
                } else {
                    alert("Erro ao eliminar atividade.");
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
            Listar Atividades
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Título</th>
                        <th class="p-3 text-left">Criado Por</th>
                        <th class="p-3 text-left">Descrição</th>
                        <th class="p-3 text-left">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $query = "
                        SELECT a.IDatv, a.titulo, a.descricao, u.nome
                        FROM atividade a, utilizador u
                        WHERE a.criadopor = u.IDutl
                        ORDER BY a.IDatv
                    ";

                    $result = mysqli_query($link, $query);

                    while ($row = mysqli_fetch_array($result)) {

                    echo "<tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$row['IDatv']}</td>
                            <td class='p-3'>{$row['titulo']}</td>
                            <td class='p-3'>{$row['nome']}</td>
                            <td class='p-3'>{$row['descricao']}</td>
                            <td class='p-3 flex gap-2'>";

                        echo "
                            <a href='editaratv.php?id={$row['IDatv']}'
                            class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition'>
                            Editar
                            </a>

                            <button onclick='eliminarAtividade({$row['IDatv']})'
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