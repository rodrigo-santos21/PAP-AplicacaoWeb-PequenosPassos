<?php
session_start();
include("DBConnection.php");

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

// Buscar logs
$sql = "SELECT * FROM logs ORDER BY datahora DESC"; //ordena pela data de hoje para a antiga
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registo de Logs</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-5xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">
            Registo de Logs
        </h2>

        <?php if (mysqli_num_rows($result) === 0): ?>
            <p class="text-center text-gray-600">Não existem logs registados.</p>
        <?php else: ?>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse bg-white shadow rounded-lg">
                    <thead>
                        <tr class="bg-blue-600 text-white">
                            <th class="p-3 text-left">Descrição</th>
                            <th class="p-3 text-left">Utilizador</th>
                            <th class="p-3 text-left">Email</th>
                            <th class="p-3 text-left">Tipo</th>
                            <th class="p-3 text-left">Data e Hora</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($result)): ?>

                            <?php
                            // Buscar dados do utilizador associado ao log
                            $IDutl = $log['IDutl'];
                            $sqlUser = "SELECT nome, email, tipo FROM utilizador WHERE IDutl = $IDutl";
                            $resUser = mysqli_query($link, $sqlUser);
                            $user = mysqli_fetch_assoc($resUser);
                            ?>

                            <tr class="border-b hover:bg-gray-100">
                                <td class="p-3"><?= $log['descricao'] ?></td>

                                <td class="p-3">
                                    <?= $user['nome'] ?? "<i>Utilizador removido</i>" ?>
                                </td>

                                <td class="p-3">
                                    <?= $user['email'] ?? "-" ?>
                                </td>

                                <td class="p-3">
                                    <?= $user['tipo'] ?? "-" ?>
                                </td>

                                <td class="p-3"><?= $log['datahora'] ?></td>
                            </tr>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="admin.php"
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Voltar
            </a>
        </div>

    </div>

</body>
</html>