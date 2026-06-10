<?php
$pagina = basename($_SERVER['PHP_SELF']);
?>

<!-- HEADER MOBILE -->
<div class="lg:hidden bg-white shadow-md p-4 flex justify-between items-center">

    <!-- LOGO + TEXTO -->
    <a href="encarregado.php" class="flex items-center space-x-3">
        <img src="imagens/logo.png" class="w-14 h-10 object-cover rounded-lg" alt="Logo">
        <span class="text-xl font-bold text-blue-400">Pequenos Passos</span>
    </a>

    <!-- BOTÃO HAMBÚRGUER -->
    <button onclick="document.getElementById('mobileMenuEncarregado').classList.toggle('hidden')" 
            class="text-3xl text-gray-700">
        ☰
    </button>
</div>

<!-- MENU MOBILE DROPDOWN -->
<div id="mobileMenuEncarregado" class="hidden lg:hidden bg-white shadow-md p-4 space-y-2">

    <!-- ===================== -->
    <!--       LINKS MENU      -->
    <!-- ===================== -->

    <a href="encarregado.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'encarregado.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Página Inicial
    </a>

    <a href="adicionarcriee.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'adicionarcriee.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Adicionar Criança
    </a>

    <a href="listarcriee.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarcriee.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Crianças
    </a>

    <a href="listarocoee.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarocoee.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Ocorrências
    </a>

    <a href="listaratvee.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listaratvee.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Atividades
    </a>

    <a href="listarreuee.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'listarreuee.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Reuniões
    </a>

    <a href="encarregado_refeicoes.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'encarregado_refeicoes.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Listar Refeições
    </a>

    <a href="encarregado_presencas.php"
        class="block px-3 py-2 font-bold rounded-md transition
        <?= $pagina === 'encarregado_presencas.php'
            ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
            : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">
        Ver Presenças
    </a>

    <!-- ===================== -->
    <!--   PERFIL + LOGOUT     -->
    <!-- ===================== -->

    <div class="mt-6 border-t pt-4">

        <!-- PERFIL -->
        <a href="perfil.php"
            class="flex items-center space-x-3 mb-4 px-2 py-2 rounded-md transition
            <?= $pagina === 'perfil.php'
                ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600'
                : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">

            <img src="<?= $fotoPerfil ?>" class="w-12 h-12 rounded-full object-cover border" alt="Foto de Perfil">

            <div>
                <p class="font-semibold text-gray-800 truncate max-w-[180px]"><?= $_SESSION['user']; ?></p>
                <p class="text-sm text-gray-500">Encarregado de Educação</p>
            </div>
        </a>

        <!-- LOGOUT -->
        <a href="logout.php"
            class="flex items-center justify-center gap-2 w-full text-center px-4 py-2 
                   bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">

            <svg xmlns="http://www.w3.org/2000/svg"
                width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" class="lucide lucide-log-out">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
            </svg>

            Terminar Sessão
        </a>

    </div>

</div>
