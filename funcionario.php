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

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit();
}

?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Página do Funcionário</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico"> <!-- ícone da tab do browser -->
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

    <!-- WRAPPER FLEX RESPONSIVO -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_funcionario.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_funcionario.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 mb-8">
                Dashboard do Funcionário / Bem-vindo, <?= $_SESSION['user']; ?>
            </h1>

            <!-- CARDS RESPONSIVOS -->
            <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Inscricoes Pendentes</h3>
                    <p class="text-gray-600 mb-4">Gerir contas pendentes.</p>
                    <a href="inscricoespendentes.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Crianças Pendentes</h3>
                    <p class="text-gray-600 mb-4">Gerir crianças pendentes.</p>
                    <a href="criancaspendentes.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Crianças</h3>
                    <p class="text-gray-600 mb-4">Gerir dados das crianças.</p>
                    <a href="listarcrifun.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Encarregados de Educação</h3>
                    <p class="text-gray-600 mb-4">Gerir dados dos encarregados.</p>
                    <a href="listareefun.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Educadores</h3>
                    <p class="text-gray-600 mb-4">Gerir dados dos educadores.</p>
                    <a href="listaredufun.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Reuniões</h3>
                    <p class="text-gray-600 mb-4">Gerir reuniões e participantes.</p>
                    <a href="listarreufun.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Ocorrências</h3>
                    <p class="text-gray-600 mb-4">Registos e acompanhamento.</p>
                    <a href="listarocofun.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Refeições</h3>
                    <p class="text-gray-600 mb-4">Gerir refeições da creche</p>
                    <a href="funcionario_refeicoes.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Presenças</h3>
                    <p class="text-gray-600 mb-4">Gerir presenças das crianças</p>
                    <a href="funcionario_presencas.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>

                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-xl transition">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Logs</h3>
                    <p class="text-gray-600 mb-4">Registos de utilização</p>
                    <a href="logs.php" class="text-green-600 font-semibold hover:underline">Ver mais →</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>