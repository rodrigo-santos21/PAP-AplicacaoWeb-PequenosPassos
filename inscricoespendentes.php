<?php
    session_start();
    include("DBConnection.php");

    // Apenas administradores podem aceder
    if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
        header("Location: index.php?erro=permissao");
        exit();
    }

    // Buscar utilizadores pendentes
    $sql = "SELECT * FROM utilizador WHERE aprovado = 0";
    $result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Inscrições Pendentes</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Inscrições Pendentes
        </h2>

        <?php if (mysqli_num_rows($result) === 0): ?>
            <p class="text-center text-gray-600">Não existem inscrições pendentes.</p>
        <?php else: ?>

            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="p-3">Nome</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Data Nasc.</th>
                        <th class="p-3">Telefone</th>
                        <th class="p-3 text-center">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($result)): ?>
                        <tr class="border-b">
                            <td class="p-3"><?= $u['nome'] ?></td>
                            <td class="p-3"><?= $u['email'] ?></td>
                            <td class="p-3"><?= $u['datanascimento'] ?></td>
                            <td class="p-3"><?= $u['telefone'] ?></td>

                            <td class="p-3 text-center">
                                <a href="aprovar.php?id=<?= $u['IDutl'] ?>"
                                   class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Aprovar
                                </a>

                                <a href="rejeitar.php?id=<?= $u['IDutl'] ?>"
                                   class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 ml-2">
                                    Rejeitar
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

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
