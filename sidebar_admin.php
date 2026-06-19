<?php $pagina = basename($_SERVER['PHP_SELF']); ?>

<aside class="w-1/5 bg-white dark:bg-gray-800 shadow-lg p-6 fixed left-0 top-0 h-screen overflow-y-auto no-scrollbar">

    <div class="flex flex-col h-full justify-between">

        <!-- TOPO -->
        <div>

            <!-- LOGO -->
            <div class="flex items-center space-x-3 mb-8">
                <a href="admin.php" class="flex items-center space-x-3">
                    <img src="imagens/logo.png" class="w-18 h-12 object-cover rounded-lg" alt="Logo">
                    <span class="text-2xl font-bold text-blue-400 dark:text-blue-300">Pequenos Passos</span>
                </a>
            </div>

            <div class="border-t-2 border-blue-400 dark:border-blue-500 pt-8">

            <!-- MENU -->
            <nav class="space-y-3">

                <?php
                function linkAdmin($file, $text, $pagina, $icon)
                {
                    $ativo = $pagina === $file;

                    $classeAtivo = "text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-gray-800 
                                    border-l-4 border-blue-600 dark:border-blue-400";

                    $classeNormal = "text-gray-700 dark:text-gray-300 
                                     hover:text-blue-600 dark:hover:text-blue-400 
                                     hover:bg-gray-100 dark:hover:bg-gray-800";

                    return '
                    <a href="'.$file.'" 
                       class="flex items-center px-2 py-2 font-bold rounded-md transition '.($ativo ? $classeAtivo : $classeNormal).'">
                        '.$icon.'
                        '.$text.'
                    </a>';
                }

                echo linkAdmin("admin.php", "Página Inicial", $pagina, '<i class="fa-solid fa-house mr-2"></i>');
                echo linkAdmin("adicionarutl.php", "Adicionar Utilizador", $pagina, '<i class="fa-solid fa-user-plus mr-2"></i>');
                echo linkAdmin("listarutl.php", "Lista Utilizadores", $pagina, '<i class="fa-solid fa-users mr-2"></i>');
                echo linkAdmin("adicionaratv.php", "Adicionar Atividade", $pagina, '<i class="fa-solid fa-plus mr-2"></i>');
                echo linkAdmin("listaratv.php", "Listar Atividades", $pagina, '<i class="fa-solid fa-list mr-2"></i>');
                echo linkAdmin("adicionarreu.php", "Adicionar Reunião", $pagina, '<i class="fa-solid fa-calendar-plus mr-2"></i>');
                echo linkAdmin("listarreu.php", "Listar Reuniões", $pagina, '<i class="fa-solid fa-calendar-days mr-2"></i>');
                echo linkAdmin("adicionarsala.php", "Adicionar Sala", $pagina, '<i class="fa-solid fa-door-open mr-2"></i>');
                echo linkAdmin("listarsala.php", "Listar Salas", $pagina, '<i class="fa-solid fa-building mr-2"></i>');
                echo linkAdmin("adicionarcri.php", "Adicionar Criança", $pagina, '<i class="fa-solid fa-child mr-2"></i>');
                echo linkAdmin("listacri.php", "Listar Crianças", $pagina, '<i class="fa-solid fa-children mr-2"></i>');
                echo linkAdmin("listaroco.php", "Listar Ocorrências", $pagina, '<i class="fa-solid fa-triangle-exclamation mr-2"></i>');
                echo linkAdmin("adicionar_menu_semana.php", "Adicionar Refeições", $pagina, '<i class="fa-solid fa-utensils mr-2"></i>');
                echo linkAdmin("listarrefeicao.php", "Listar Refeições", $pagina, '<i class="fa-solid fa-bowl-food mr-2"></i>');
                echo linkAdmin("admin_presencas.php", "Presenças", $pagina, '<i class="fa-solid fa-user-check mr-2"></i>');
                echo linkAdmin("logs.php", "Consultar Logs", $pagina, '<i class="fa-solid fa-file-lines mr-2"></i>');
                ?>

            </nav>

            </div>
        </div>

        <!-- FUNDO -->
        <div class="mt-8 border-t-2 border-blue-400 dark:border-blue-500 pt-6">

            <!-- PERFIL -->
            <a href="perfil.php"
            class="flex items-center space-x-3 mb-4 px-2 py-2 rounded-md transition
            <?= $pagina === 'perfil.php'
                ? 'text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-gray-900 border-l-4 border-blue-600 dark:border-blue-400'
                : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800' ?>">

                <img src="<?= $fotoPerfil ?>" class="w-12 h-12 rounded-full object-cover border dark:border-gray-600">

                <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100 truncate max-w-[180px]"><?= $_SESSION['user']; ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Administrador</p>
                </div>
            </a>

            <!-- LOGOUT -->
            <a href="logout.php"
            class="flex items-center justify-center gap-2 w-full text-center px-4 py-2 
                   bg-red-600 dark:bg-red-700 text-white hover:bg-red-700 dark:hover:bg-red-600 font-semibold mb-6">

                <i class="fa-solid fa-right-from-bracket"></i>
                Terminar Sessão
            </a>

        </div>

    </div>

</aside>
