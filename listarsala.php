<?php
session_start();
include "DBConnection.php";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

/* ============================================================
   PROCESSAR ELIMINAÇÃO (TEM DE VIR ANTES DE QUALQUER HTML)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = intval($_POST['eliminar_id']);

    // Verificar dependências
    $cri = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS total FROM crianca WHERE IDsala = $id AND estado = 1"
    ))['total'];

    $edu = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS total FROM educador WHERE IDsala = $id AND estado = 1"
    ))['total'];

    if ($cri > 0 || $edu > 0) {
        echo "erro_dependencias";
        exit;
    }

    // Eliminar sala (soft delete)
    mysqli_query($link, "UPDATE sala SET estado = 0 WHERE IDsala = $id");

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");
    $IDutl = $_SESSION['id'];

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Sala eliminada (ID $id)', '$fdatahora', '$IDutl')
    ");

    echo "ok";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Salas</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function eliminarSala(id) {
        if (!confirm("Tem a certeza que deseja eliminar esta sala?")) return;

        fetch("listarsala.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "eliminar_id=" + id
        })
        .then(r => r.text())
        .then(res => {

            res = res.trim(); // limpar espaços e quebras de linha

            if (res === "ok") {
                alert("Sala eliminada com sucesso.");
                location.reload();
                return;
            }

            if (res === "erro_dependencias") {
                alert("Não é possível eliminar esta sala porque existem crianças ou educadores associados.");
                return;
            }

            console.log("Resposta inesperada:", res);
            alert("Erro ao eliminar sala.");
        });
    }
    </script>
</head>

<body class="bg-gray-100 min-h-screen">

<div class="max-w-4xl mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">

    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">
        Lista de Salas
    </h1>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white shadow rounded-lg">
            <thead>
                <tr class="bg-blue-600 text-white">
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Nome</th>
                    <th class="p-3 text-left">Capacidade</th>
                    <th class="p-3 text-left">Crianças</th>
                    <th class="p-3 text-left">Educadores</th>
                    <th class="p-3 text-left">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $res = mysqli_query($link, "SELECT * FROM sala WHERE estado = 1 ORDER BY IDsala");

                while ($s = mysqli_fetch_assoc($res)) {

                    $IDsala = $s['IDsala'];

                    // Contar crianças
                    $cri = mysqli_fetch_assoc(mysqli_query($link,
                        "SELECT COUNT(*) AS total FROM crianca WHERE IDsala = $IDsala AND estado = 1"
                    ))['total'];

                    // Contar educadores
                    $edu = mysqli_fetch_assoc(mysqli_query($link,
                        "SELECT COUNT(*) AS total FROM educador WHERE IDsala = $IDsala AND estado = 1"
                    ))['total'];

                    echo "
                    <tr class='border-b hover:bg-gray-100'>
                        <td class='p-3'>{$s['IDsala']}</td>
                        <td class='p-3'>{$s['nome']}</td>
                        <td class='p-3'>{$s['capacidade']}</td>
                        <td class='p-3'>$cri</td>
                        <td class='p-3'>$edu</td>

                        <td class='p-3 flex gap-2'>
                            <a href='editarsala.php?id={$s['IDsala']}'
                               class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500'>
                                Editar
                            </a>

                            <button onclick='eliminarSala({$s['IDsala']})'
                                class='px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700'>
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
           class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Voltar
        </a>
    </div>

</div>

</body>
</html>
