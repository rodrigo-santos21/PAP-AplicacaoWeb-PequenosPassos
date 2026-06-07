<?php
// Detecta a página atual
$pagina = basename($_SERVER['PHP_SELF']);
?>

<!-- HEADER MOBILE -->
<div class="lg:hidden bg-white shadow-md p-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-gray-800">Superadmin</h1>

    <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" 
            class="text-3xl text-gray-700">
        ☰
    </button>
</div>

<!-- MENU MOBILE DROPDOWN -->
<div id="mobileMenu" class="hidden lg:hidden bg-white shadow-md p-4 space-y-2">

    <!-- PÁGINA INICIAL -->
    <a href="superadmin.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'superadmin.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Página Inicial
    </a>

    <!-- ADICIONAR UTILIZADOR -->
    <a href="adicionarutlsuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'adicionarutlsuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Adicionar Utilizador
    </a>

    <!-- LISTAR UTILIZADORES -->
    <a href="listarutlsuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarutlsuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Lista Utilizadores
    </a>

    <!-- ADICIONAR ATIVIDADE -->
    <a href="adicionaratvsuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'adicionaratvsuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Adicionar Atividade
    </a>

    <!-- LISTAR ATIVIDADES -->
    <a href="listaratvsuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listaratvsuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Atividades
    </a>

    <!-- ADICIONAR REUNIÃO -->
    <a href="adicionarreusuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'adicionarreusuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Adicionar Reunião
    </a>

    <!-- LISTAR REUNIÕES -->
    <a href="listarreusuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarreusuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Reuniões
    </a>

    <!-- ADICIONAR SALA -->
    <a href="adicionarsalasuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'adicionarsalasuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Adicionar Sala
    </a>

    <!-- LISTAR SALAS -->
    <a href="listarsalasuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarsalasuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Salas
    </a>

    <!-- ADICIONAR CRIANÇA -->
    <a href="adicionarcrisuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'adicionarcrisuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Adicionar Criança
    </a>

    <!-- LISTAR CRIANÇAS -->
    <a href="listarcrisuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarcrisuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Crianças
    </a>

    <!-- LISTAR OCORRÊNCIAS -->
    <a href="listarocosuper.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarocosuper.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Ocorrências
    </a>

    <!-- ADICIONAR REFEIÇÕES -->
    <a href="adicionar_menu_semana.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'adicionar_menu_semana.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Adicionar Refeições
    </a>

    <!-- LISTAR REFEIÇÕES -->
    <a href="listarrefeicao.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarrefeicao.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Refeições
    </a>

    <!-- PRESENÇAS -->
    <a href="superadmin_presencas.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'superadmin_presencas.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Presenças
    </a>

    <!-- LOGS -->
    <a href="logs.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'logs.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Consultar Logs
    </a>

    <!-- INATIVOS -->
    <a href="superadmin_inativos.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'superadmin_inativos.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Gerir Inativos
    </a>

</div>
