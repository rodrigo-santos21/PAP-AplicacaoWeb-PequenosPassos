<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit();
}

?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Página do Educador</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico"> <!-- ícone da tab do browser -->
</head>

<body class="bg-gray-100 overflow-y-auto min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Bem-vindo, <?php echo $_SESSION['user']; ?> (Educador)
        </h2>

        <nav class="space-y-4">
            <a href="listarcriedu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar crianças da sua sala</a>
            <a href="adicionaroco.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Marcar ocorrência</a>
            <a href="listarocoedu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Ocorrência</a>
            <a href="adicionaratvedu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar atividade</a>
            <a href="listaratvedu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar as suas atividades</a>
            <a href="listarreuedu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Reunião à qual pertence</a>
            <a href="educador_presencas.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Presenças</a>
            <a href="perfil.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Perfil</a>

        </nav>

        <a href="logout.php"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 block text-center mt-6">
            Terminar Sessão
        </a>
    </div>
</body>
</html>