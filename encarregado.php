<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit();
}

?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Página do Encarregado de Educação</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico"> <!-- ícone da tab do browser -->
</head>

<body class="bg-gray-100 overflow-y-auto min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Bem-vindo, <?php echo $_SESSION['user']; ?> (Encarregado de Educação)
        </h2>

        <nav class="space-y-4">
            <a href="veratv.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Ver Atividade</a>
            <a href="verreu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Ver Reunião</a>
            <a href="versala.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Ver Sala</a>
            <a href="adicionarcri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar Criança</a>
            <a href="listacri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Criança</a>
            <a href="veroco.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Ver Ocorrência</a>
            <a href="listapre.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Histórico de Presenças</a>
            <a href="perfil.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Perfil</a>
        </nav>

        <a href="logout.php"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 block text-center mt-6">
            Terminar Sessão
        </a>
    </div>
</body>
</html>