<?php
session_start();
include("DBConnection.php");

// Apenas funcionários podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit();
}

// Buscar todas as crianças pendentes (estado = 0)
$sql = "SELECT * FROM crianca WHERE aprovado = 0 AND estado = 0 ORDER BY analise_por IS NOT NULL, nome ASC";
$result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Crianças Pendentes</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Crianças Pendentes
        </h2>

        <?php if (mysqli_num_rows($result) === 0): ?>
            <p class="text-center text-gray-600">Não existem crianças pendentes.</p>
        <?php else: ?>

            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="p-3">Nome</th>
                        <th class="p-3">Data Nasc.</th>
                        <th class="p-3">Sexo</th>
                        <th class="p-3">Encarregado</th>
                        <th class="p-3">Estado</th>
                        <th class="p-3 text-center">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($c = mysqli_fetch_assoc($result)): ?>

                        <?php
                        // Buscar o encarregado
                        $IDenc = $c['IDutl'];
                        $resEnc = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDenc");
                        $enc = mysqli_fetch_assoc($resEnc);
                        $nomeEncarregado = $enc ? $enc['nome'] : "Desconhecido";
                        ?>

                        <tr class="border-b">
                            <td class="p-3"><?= $c['nome'] ?></td>
                            <td class="p-3"><?= $c['datanascimento'] ?></td>
                            <td class="p-3"><?= $c['sexo'] ?></td>
                            <td class="p-3"><?= $nomeEncarregado ?></td>

                            <td class="p-3">
                                <?php if ($c['analise_por']): ?>
                                    <span class="text-red-600 text-sm">
                                        Em análise por Funcionário #<?= $c['analise_por'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-green-600 text-sm">Disponível</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 text-center">
                                <a href="analisa_crianca.php?id=<?= $c['IDcri'] ?>"
                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Analisar
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="funcionario.php"
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Voltar
            </a>
        </div>
    </div>

</body>
</html>