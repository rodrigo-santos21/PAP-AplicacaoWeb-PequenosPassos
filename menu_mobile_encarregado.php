<?php
$pagina = basename($_SERVER['PHP_SELF']);
?>

<!-- HEADER MOBILE -->
<div class="lg:hidden bg-white dark:bg-gray-800 dark:text-gray-100 shadow-md p-4 flex justify-between items-center">

    <!-- LOGO + TEXTO -->
    <a href="encarregado.php" class="flex items-center space-x-3">
        <img src="imagens/logo.png" class="w-14 h-10 object-cover rounded-lg" alt="Logo">
        <span class="text-xl font-bold text-blue-400 dark:text-blue-300">Pequenos Passos</span>
    </a>

    <!-- BOTÃO HAMBÚRGUER -->
    <button onclick="document.getElementById('mobileMenuEncarregado').classList.toggle('hidden')" 
            class="text-3xl text-gray-700 dark:text-gray-200">
        ☰
    </button>
</div>

<!-- MENU MOBILE DROPDOWN -->
<div id="mobileMenuEncarregado" class="hidden lg:hidden bg-white dark:bg-gray-800 dark:text-gray-100 shadow-md p-4 space-y-2">

<?php
function linkMobileEE($file, $text, $pagina) {
    $ativo = $pagina === $file;

    $classeAtivo = "text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-gray-800 
                    border-l-4 border-blue-600 dark:border-blue-400";

    $classeNormal = "text-gray-700 dark:text-gray-300 
                     hover:text-blue-600 dark:hover:text-blue-400 
                     hover:bg-gray-100 dark:hover:bg-gray-800";

    return '
    <a href="'.$file.'" 
       class="block px-3 py-2 font-bold rounded-md transition '.($ativo ? $classeAtivo : $classeNormal).'">
        '.$text.'
    </a>';
}

echo linkMobileEE("encarregado.php", "Página Inicial", $pagina);
echo linkMobileEE("adicionarcriee.php", "Adicionar Criança", $pagina);
echo linkMobileEE("listarcriee.php", "Listar Crianças", $pagina);
echo linkMobileEE("listarocoee.php", "Listar Ocorrências", $pagina);
echo linkMobileEE("listaratvee.php", "Listar Atividades", $pagina);
echo linkMobileEE("listarreuee.php", "Listar Reuniões", $pagina);
echo linkMobileEE("encarregado_refeicoes.php", "Listar Refeições", $pagina);
echo linkMobileEE("encarregado_presencas.php", "Ver Presenças", $pagina);
?>

<!-- PERFIL + LOGOUT -->
<div class="mt-6 border-t border-gray-300 dark:border-gray-700 pt-4">

    <!-- PERFIL -->
    <a href="perfil.php"
        class="flex items-center space-x-3 mb-4 px-2 py-2 rounded-md transition
        <?= $pagina === 'perfil.php'
            ? 'text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-gray-800 border-l-4 border-blue-600 dark:border-blue-400'
            : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800' ?>">

        <img src="<?= $fotoPerfil ?>" class="w-12 h-12 rounded-full object-cover border dark:border-gray-600" alt="Foto de Perfil">

        <div>
            <p class="font-semibold text-gray-800 dark:text-gray-100 truncate max-w-[180px]"><?= $_SESSION['user']; ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Encarregado de Educação</p>
        </div>
    </a>

    <!-- LOGOUT -->
    <a href="logout.php"
        class="flex items-center justify-center gap-2 w-full text-center px-4 py-2 
               bg-red-600 dark:bg-red-700 text-white rounded-lg hover:bg-red-700 dark:hover:bg-red-600 font-semibold">

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
