<?php
session_start();
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

<body class="bg-gray-100 overflow-y-auto min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Bem-vindo, <?php echo $_SESSION['user']; ?> (Funcionário)
        </h2>

        <nav class="space-y-4">
            <a href="inscricoespendentes.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Inscrições Pendentes</a>
            <a href="criancaspendentes.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Crianças Pendentes</a>
            <a href="listarcrifun.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Crianças</a>
            <a href="listareefun.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Encarregados de Educação</a>
            <a href="listaredufun.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Educadores</a>
            <a href="listarreufun.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Ver Reuniões</a>
            <a href="listarocofun.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Ocorrências</a>
            <a href="funcionario_presencas.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Presenças</a>
            <a href="logs.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Logs</a>
            <a href="perfil.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Perfil</a>
        </nav>

        <a href="logout.php"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 block text-center mt-6">
            Terminar Sessão
        </a>
    </div>
</body>
</html>