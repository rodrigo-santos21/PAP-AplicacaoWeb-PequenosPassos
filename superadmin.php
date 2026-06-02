<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit();
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Página do Superadministrador</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body class="bg-gray-100 overflow-y-auto min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Bem-vindo, <?php echo $_SESSION['user']; ?> (Superadministrador)
        </h2>

        <nav class="space-y-4">

            <!-- GESTÃO DE UTILIZADORES -->
            <a href="adicionarutlsuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Adicionar Utilizador
            </a>

            <a href="listarutlsuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Lista de Utilizadores
            </a>

            <!-- GESTÃO DE ATIVIDADES -->
            <a href="adicionaratvsuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Adicionar Atividade
            </a>

            <a href="listaratvsuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Listar Atividades
            </a>

            <!-- GESTÃO DE REUNIÕES -->
            <a href="adicionarreusuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Adicionar Reunião
            </a>

            <a href="listarreusuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Listar Reuniões
            </a>

            <!-- GESTÃO DE SALAS -->
            <a href="adicionarsalasuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Adicionar Sala
            </a>

            <a href="listarsalasuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Listar Salas
            </a>

            <!-- GESTÃO DE CRIANÇAS -->
            <a href="adicionarcrisuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Adicionar Criança
            </a>

            <a href="listarcrisuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Listar Crianças
            </a>

            <!-- OCORRÊNCIAS -->
            <a href="listarocosuper.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Listar Ocorrências
            </a>

            <!-- PRESENÇAS -->
            <a href="superadmin_presencas.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Presenças
            </a>

            <!-- LOGS -->
            <a href="logs.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Consultar Logs
            </a>

            <!-- PERFIL -->
            <a href="perfil.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Perfil
            </a>

            <!-- NOVO: INATIVOS -->
            <a href="superadmin_inativos.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
               Gerir Inativos
            </a>

        </nav>

        <a href="logout.php"
           class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 block text-center mt-6">
           Terminar Sessão
        </a>

    </div>
</body>
</html>
