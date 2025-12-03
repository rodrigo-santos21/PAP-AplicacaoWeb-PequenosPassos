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
    <title>Painel do Administrador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Bem-vindo, <?php echo $_SESSION['user']; ?> (Administrador)
        </h2>

        <nav class="space-y-4">
            <a href="gestao_utilizadores.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Gestão de Utilizadores</a>
            <a href="logs.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Consultar Logs</a>
            <a href="estatisticas.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Estatísticas</a>
        </nav>

        <form method="post" action="logout.php" class="mt-6">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Terminar Sessão</button>
        </form>
    </div>
</body>
</html>