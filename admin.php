<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Página do Administrador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 overflow-y-auto min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Bem-vindo, <?php echo $_SESSION['user']; ?> (Administrador)
        </h2>

        <nav class="space-y-4">
            <a href="adicionarutl.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar utilizador</a>
            <a href="listarutl.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Lista Utilizadores</a>
            <a href="adicionaratv.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar atividade</a>
            <a href="listaratv.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Atividade</a>
            <a href="adicionarreu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar Reunião</a>
            <a href="listarreu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Reunião</a>
            <a href="adicionaroco.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Marcar ocorrência</a>
            <a href="listaroco.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Ocorrência</a>
            <a href="adicionarcri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar Criança</a>
            <a href="listacri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Criança</a>
            <a href="listapre.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Histórico de Presenças</a>
            <a href="logs.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Consultar Logs</a>
        </nav>

        <a href="logout.php"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 block text-center mt-6">
            Terminar Sessão
        </a>
    </div>
</body>
</html>