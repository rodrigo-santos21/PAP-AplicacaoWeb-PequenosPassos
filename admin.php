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
            <a href="listarutl.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Lista Utilizadores</a>
            <a href="logs.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Consultar Logs</a>
            <a href="adicionarutl.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar utilizador</a>
            <a href="editarutl.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Editar utilizador</a>
            <a href="removerutl.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Eliminar utilizador</a>
            <a href="adicionaratv.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar atividade</a>
            <a href="editaratv.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Editar atividade</a>
            <a href="removeratv.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Eliminar atividade</a>
            <a href="adicionarreu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar Reunião</a>
            <a href="editarreu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Editar Reunião</a>
            <a href="removerreu.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Remover Reuião</a>
            <a href="adicionaroco.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Marcar ocorrência</a>
            <a href="editaroco.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Editar ocorrência</a>
            <a href="removeroco.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Remover ocorrência</a>
            <a href="adicionarcri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Adcionar Criança</a>
            <a href="editarcri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Editar Criança</a>
            <a href="removercri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Remover Criança</a>
            <a href="listacri.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Listar Criança</a>
            <a href="listapre.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Histórico de Presenças</a>
            <a href="editarpre.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Editar Presença</a>
            <a href="removerpre.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Remover Presença</a>
        </nav>

        <form method="post" action="logout.php" class="mt-6">
            <button 
                type="button" 
                onclick="window.location.href='index.php';"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Terminar Sessão</button>
        </form>
    </div>
</body>
</html>