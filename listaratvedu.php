<?php
session_start();
include "DBConnection.php";

// Verifica se é educador
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDutl = $_SESSION['id'];

/* ================================
   1) BUSCAR ID DO EDUCADOR + SALA
================================ */
$resEdu = mysqli_query($link, "
    SELECT IDedu, IDsala 
    FROM educador 
    WHERE IDutl = $IDutl AND estado = 1
");

if (!$resEdu || mysqli_num_rows($resEdu) === 0) {
    die("Erro: Educador não encontrado.");
}

$edu    = mysqli_fetch_assoc($resEdu);
$IDedu  = $edu['IDedu'];
$IDsala = $edu['IDsala'];

/* ================================
   2) PROCESSO DE ELIMINAÇÃO VIA AJAX
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = intval($_POST['eliminar_id']);

    // Verificar se a atividade pertence ao educador
    $resCheck = mysqli_query($link, "
        SELECT IDatv FROM atividade 
        WHERE IDatv = $id AND criadopor = $IDutl AND estado = 1
    ");

    if (mysqli_num_rows($resCheck) == 0) {
        echo "erro_permissao";
        exit;
    }

    // 1) Soft delete da atividade
    $stmt = mysqli_prepare($link, "UPDATE atividade SET estado = 0 WHERE IDatv = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $success = mysqli_stmt_execute($stmt);

    // 2) Soft delete das relações com crianças
    if ($success) {
        mysqli_query($link, "
            UPDATE crianca_atividade 
            SET estado = 0 
            WHERE IDatv = $id
        ");
    }

    // 3) Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Educador eliminou atividade (ID $id)', '$fdatahora', '$IDutl')
        ");
    }

    echo $success ? "ok" : "erro";
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Atividades da Sala</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function eliminarAtividade(id) {
        if (confirm("Tem a certeza que deseja eliminar esta atividade?")) {

            fetch("listaratvedu.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "eliminar_id=" + encodeURIComponent(id)
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "ok") {
                    alert("Atividade eliminada com sucesso.");
                    location.reload();
                } 
                else if (data.trim() === "erro_permissao") {
                    alert("Não tem permissão para eliminar esta atividade.");
                }
                else {
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
            Atividades da Sala
        </h1>

        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Atividades criadas por si
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Título</th>
                        <th class="p-3 text-left">Data/Hora</th>
                        <th class="p-3 text-left">Crianças</th>
                        <th class="p-3 text-left">Realizadas</th>
                        <th class="p-3 text-left">Descrição</th>
                        <th class="p-3 text-left">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php

                    /* ================================
                       3) LISTAR APENAS ATIVIDADES DO EDUCADOR
                    ================================= */
                    $resAtv = mysqli_query($link, "
                        SELECT * 
                        FROM atividade
                        WHERE criadopor = $IDutl
                        AND estado = 1
                        ORDER BY IDatv DESC
                    ");

                    while ($a = mysqli_fetch_assoc($resAtv)) {

                        $IDatv = $a['IDatv'];

                        // Contar crianças associadas
                        $resCount = mysqli_query($link, "
                            SELECT COUNT(*) AS total 
                            FROM crianca_atividade 
                            WHERE IDatv = $IDatv AND estado = 1
                        ");
                        $totalCri = mysqli_fetch_assoc($resCount)['total'];

                        // Contar crianças que realizaram
                        $resReal = mysqli_query($link, "
                            SELECT COUNT(*) AS total 
                            FROM crianca_atividade 
                            WHERE IDatv = $IDatv AND estado = 1 AND realizada = 1
                        ");
                        $totalReal = mysqli_fetch_assoc($resReal)['total'];

                        // Descrição curta
                        $desc = strlen($a['descricao']) > 40
                                ? substr($a['descricao'], 0, 40) . "..."
                                : $a['descricao'];

                        echo "
                        <tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$a['IDatv']}</td>
                            <td class='p-3'>{$a['titulo']}</td>
                            <td class='p-3'>{$a['datahora']}</td>
                            <td class='p-3'>{$totalCri}</td>
                            <td class='p-3'>{$totalReal}</td>
                            <td class='p-3'>{$desc}</td>

                            <td class='p-3 flex gap-2'>
                                <a href='editaratvedu.php?id={$a['IDatv']}'
                                class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition'>
                                Editar
                                </a>

                                <button onclick='eliminarAtividade({$a['IDatv']})'
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
            <a href="educador.php"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-block">
                Página Inicial
            </a>
        </div>
    </div>
</body>
</html>
