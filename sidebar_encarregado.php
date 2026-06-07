<aside class="w-1/5 bg-white shadow-lg p-6 fixed left-0 top-0 h-screen overflow-y-auto no-scrollbar">

    <div class="flex flex-col h-full justify-between">

        <!-- TOPO: LOGO + MENU -->
        <div>
            <!-- LOGO + TEXTO -->
            <div class="flex items-center space-x-3 mb-8">
                <a href="encarregado.php" class="flex items-center space-x-3">
                    <img src="imagens/logo.png" class="w-18 h-12 object-cover rounded-lg" alt="Logo">
                    <span class="text-2xl font-bold text-blue-400">Pequenos Passos</span>
                </a>
            </div>

            <div class="border-t-2 border-blue-400 pt-8">

            <?php $pagina = basename($_SERVER['PHP_SELF']); ?>

            <nav class="space-y-3">
                <a href="encarregado.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'encarregado.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Página Inicial
                </a>

                <a href="adicionarcriee.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarcriee.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Criança
                </a>

                <a href="listarcriee.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarcriee.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Crianças
                </a>

                <a href="listarocoee.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarocoee.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Ocorrência
                </a>

                <a href="listaratvee.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listaratvee.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Atividades
                </a>

                <a href="listarreuee.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarreuee.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Reuniões
                </a>

                <a href="encarregado_refeicoes.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'encarregado_refeicoes.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Refeições
                </a>

                <a href="encarregado_presencas.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'encarregado_presencas.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Ver Presenças
                </a>
            </nav>

            </div>
        </div>

        <!-- FUNDO: PERFIL + LOGOUT -->
        <div class="mt-8 border-t-2 border-blue-400 pt-6">

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

            <a href="logout.php"
            class="flex items-center justify-center gap-2 w-full text-center px-4 py-2 
                    bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold mb-6">

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

</aside>
