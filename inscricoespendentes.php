<?php
    session_start();
    include("DBConnection.php");

    //BUSCA A FOTO DE PERFIL DO UTILIZADOR
    $IDutl = $_SESSION['id'];

    $stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
    mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
    mysqli_stmt_execute($stmtFoto);
    $resFoto = mysqli_stmt_get_result($stmtFoto);
    $foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

    $fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

    // Apenas funcionários podem aceder
    if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
        header("Location: index.php?erro=permissao");
        exit();
    }

    // Buscar utilizadores pendentes
    $sql = "SELECT * FROM utilizador WHERE aprovado = 0 ORDER BY analise_por IS NOT NULL";
    $result = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Inscrições Pendentes</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 min-h-screen">

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php
            include("sidebar_funcionario.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Inscrições Pendentes </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <?php if (mysqli_num_rows($result) === 0): ?>

                    <p class="text-center text-gray-600">Não existem inscrições pendentes.</p>

                <?php else: ?>

                    <!-- GRID DE CARDS -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                    <?php while ($u = mysqli_fetch_assoc($result)): ?>

                        <div class="bg-blue-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                            <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $u['nome'] ?></h2>

                            <div class="text-gray-700 space-y-1 mb-4">
                                <p><strong>Email:</strong> <?= $u['email'] ?></p>
                                <p><strong>Data Nasc.:</strong> <?= $u['datanascimento'] ?></p>
                                <p><strong>Telefone:</strong> <?= $u['telefone'] ?></p>

                                <p>
                                    <strong>Estado:</strong>
                                    <?php if ($u['analise_por']): ?>
                                        <span class="text-red-600">
                                            Em análise por Funcionário #<?= $u['analise_por'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-green-600">Disponível</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="flex justify-start">
                                <a href="analisa_inscricao.php?id=<?= $u['IDutl'] ?>"
                                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                                    Analisar
                                </a>
                            </div>

                        </div>

                    <?php endwhile; ?>

                    </div>

                <?php endif; ?>

            </div>

        </main>
    </div>

</body>
</html>
