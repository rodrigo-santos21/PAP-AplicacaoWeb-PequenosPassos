<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

// Buscar tema do utilizador
$stmtTema = mysqli_prepare($link, "SELECT tema FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtTema, "i", $IDutl);
mysqli_stmt_execute($stmtTema);
$resTema = mysqli_stmt_get_result($stmtTema);
$tema = mysqli_fetch_assoc($resTema)['tema'] ?? 'light';

// Atualizar sessão
$_SESSION['tema'] = $tema;


$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit();
}

?>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Página do Educador</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico"> <!-- ícone da tab do browser -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">

    <!-- WRAPPER FLEX RESPONSIVO -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_educador.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_educador.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Dashboard do Educador / Bem-vindo, <?= $_SESSION['user']; ?>
            </h1>

            <!-- CARDS RESPONSIVOS -->
            <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-2xl dark:hover:shadow-gray-400 transition">
                    <div class="flex items-center mb-2">
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-100">Crianças</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Gerir dados das crianças.</p>
                    <a href="listarcriedu.php" class="text-green-600 dark:text-green-400 font-semibold hover:underline">Ver mais →</a>
                </div>
                
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-2xl dark:hover:shadow-gray-400 transition">
                    <div class="flex items-center mb-2">
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-100">Ocorrências</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Registos e acompanhamento.</p>
                    <a href="listarocoedu.php" class="text-green-600 dark:text-green-400 font-semibold hover:underline">Ver mais →</a>
                </div>
                
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-2xl dark:hover:shadow-gray-400 transition">
                    <div class="flex items-center mb-2">
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-100">Atividades</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Criar e gerir atividades.</p>
                    <a href="listaratvedu.php" class="text-green-600 dark:text-green-400 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-2xl dark:hover:shadow-gray-400 transition">
                    <div class="flex items-center mb-2">
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-100">Reuniões</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Gerir reuniões e participantes.</p>
                    <a href="listarreuedu.php" class="text-green-600 dark:text-green-400 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-2xl dark:hover:shadow-gray-400 transition">
                    <div class="flex items-center mb-2">
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-100">Refeições</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Refeições das crianças associadas a si.</p>
                    <a href="educador_refeicoes.php" class="text-green-600 dark:text-green-400 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hover:shadow-2xl dark:hover:shadow-gray-400 transition">
                    <div class="flex items-center mb-2">
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-100">Presenças</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Gerir presenças das crianças associadas a si.</p>
                    <a href="educador_presencas.php" class="text-green-600 dark:text-green-400 font-semibold hover:underline">Ver mais →</a>
                </div>

            </div>

        </main>
    </div>
</body>
</html>