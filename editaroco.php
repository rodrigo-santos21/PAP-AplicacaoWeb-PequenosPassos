<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault.png";

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

// Verificar ID da ocorrência
if (!isset($_GET['id'])) {
    header("Location: listaroco.php?erro=sem_id");
    exit();
}

$IDoc = intval($_GET['id']);
$IDutl = intval($_SESSION['id']);

// Buscar ocorrência (SEM JOIN)
$resOc = mysqli_query($link, "SELECT * FROM ocorrencia WHERE IDoc = $IDoc AND estado = 1");
$oc = mysqli_fetch_assoc($resOc);

if (!$oc) {
    header("Location: listaroco.php?erro=nao_existe");
    exit();
}

$IDcri = intval($oc['IDcri']);
$IDeduCriador = intval($oc['IDedu']);

// Buscar nome da criança (SEM JOIN)
$criNome = "—";
$resCri = mysqli_query($link, "SELECT nome FROM crianca WHERE IDcri = $IDcri");
if ($resCri && mysqli_num_rows($resCri) > 0) {
    $cri = mysqli_fetch_assoc($resCri);
    $criNome = $cri['nome'];
}

// Buscar nome do educador criador (SEM JOIN)
$eduNome = "—";
$resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDedu = $IDeduCriador");
if ($resEdu && mysqli_num_rows($resEdu) > 0) {
    $edu = mysqli_fetch_assoc($resEdu);
    $IDutlCriador = intval($edu['IDutl']);

    $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutlCriador");
    if ($resU && mysqli_num_rows($resU) > 0) {
        $u = mysqli_fetch_assoc($resU);
        $eduNome = $u['nome'];
    }
}

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = mysqli_real_escape_string($link, $_POST['tipo']);
    $gravidade = mysqli_real_escape_string($link, $_POST['gravidade']);
    $descricao = mysqli_real_escape_string($link, $_POST['descricao']);

    // Se for "Outro", guardar o texto personalizado
    if ($tipo === "Outro") {
        $tipo_outro = mysqli_real_escape_string($link, $_POST['tipo_outro']);
    } else {
        $tipo_outro = null;
    }

    // Atualizar ocorrência
    $stmt = mysqli_prepare($link, "
        UPDATE ocorrencia
        SET tipo = ?, tipo_outro = ?, descricao = ?, gravidade = ?
        WHERE IDoc = ?
    ");

    mysqli_stmt_bind_param($stmt, "ssssi",
        $tipo, $tipo_outro, $descricao, $gravidade, $IDoc
    );

    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        // Log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Ocorrência editada pelo admin (ID $IDoc)', '$fdatahora', '$IDutl')");

        header("Location: listaroco.php?sucesso=editado");
        exit();
    } else {
        $erro = "Erro ao atualizar ocorrência.";
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Ocorrência (Admin)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function toggleOutro() {
        let tipo = document.getElementById("tipoSelect").value;
        document.getElementById("outroCampo").style.display = (tipo === "Outro") ? "block" : "none";
    }
    </script>
</head>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 min-h-screen">

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside class="w-1/5 bg-white shadow-lg p-6 flex flex-col justify-between fixed left-0 top-0 h-screen overflow-y-auto no-scrollbar">

            <!-- LOGO + TEXTO -->
            <div class="flex items-center space-x-3 mb-8">
                <a href="admin.php" class="flex items-center space-x-3">
                <img src="imagens/logo.png" class="w-18 h-12 object-cover rounded-lg" alt="Logo">
                <span class="text-2xl font-bold text-blue-400">Pequenos Passos</span>
                </a>
            </div>

            <div class="border-t-2 border-blue-400 pt-8">

            <!-- MENU -->
            <?php $pagina = basename($_SERVER['PHP_SELF']); ?> <!-- Devolve a página atual-->

            <nav class="space-y-3 flex-1">
                <a href="admin.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'admin.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Página Inicial
                </a>

                <a href="adicionarutl.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarutl.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Utilizador
                </a>

                <a href="listarutl.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarutl.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Lista Utilizadores
                </a>

                <a href="adicionaratv.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionaratv.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Atividade
                </a>

                <a href="listaratv.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listaratv.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Atividades
                </a>

                <a href="adicionarreu.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarreu.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Reunião
                </a>

                <a href="listarreu.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarreu.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Reuniões
                </a>

                <a href="adicionarsala.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarsala.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Sala
                </a>

                <a href="listarsala.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarsala.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Salas
                </a>

                <a href="adicionarcri.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarcri.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Criança
                </a>

                <a href="listacri.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listacri.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Crianças
                </a>

                <a href="listaroco.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listaroco.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Ocorrências
                </a>

                <a href="admin_presencas.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'admin_presencas.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Presenças
                </a>

                <a href="logs.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'logs.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Consultar Logs
                </a>
            </nav>

            <!-- PERFIL + LOGOUT -->
            <div class="mt-8 border-t-2 border-blue-400 pt-6">

                <!-- PERFIL (AGORA É UM LINK) -->
                <a href="perfil.php"
                class="flex items-center space-x-3 mb-4 px-2 py-2 rounded-md transition
                <?= $pagina === 'perfil.php' 
                        ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' 
                        : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">

                    <img src="<?= $fotoPerfil ?>" class="w-12 h-12 rounded-full object-cover border" alt="Foto de Perfil">

                    <div>
                        <p class="font-semibold text-gray-800 truncate max-w-[180px]"><?= $_SESSION['user']; ?></p>
                        <p class="text-sm text-gray-500">Administrador</p>
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
        </aside>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Editar Ocorrência </h1>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <p class="text-center text-gray-600 mb-4">
                    Criança: <b><?= $criNome ?></b><br>
                    Criado por: <b><?= $eduNome ?></b>
                </p>

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select name="tipo" id="tipoSelect" onchange="toggleOutro()"
                                class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                            <option value="Doença" <?= $oc['tipo'] === 'Doença' ? 'selected' : '' ?>>Doença</option>
                            <option value="Queda" <?= $oc['tipo'] === 'Queda' ? 'selected' : '' ?>>Queda</option>
                            <option value="Comportamento" <?= $oc['tipo'] === 'Comportamento' ? 'selected' : '' ?>>Comportamento</option>
                            <option value="Agressão" <?= $oc['tipo'] === 'Agressão' ? 'selected' : '' ?>>Agressão</option>
                            <option value="Outro" <?= $oc['tipo'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>

                    <div id="outroCampo" style="display: <?= $oc['tipo'] === 'Outro' ? 'block' : 'none' ?>;">
                        <label class="block text-sm font-medium text-gray-700">Especificar outro tipo</label>
                        <input type="text" name="tipo_outro"
                            value="<?= $oc['tipo_outro'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gravidade</label>
                        <select name="gravidade" class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                            <option value="Leve" <?= $oc['gravidade'] === 'Leve' ? 'selected' : '' ?>>Leve</option>
                            <option value="Moderada" <?= $oc['gravidade'] === 'Moderada' ? 'selected' : '' ?>>Moderada</option>
                            <option value="Grave" <?= $oc['gravidade'] === 'Grave' ? 'selected' : '' ?>>Grave</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" rows="4"
                                class="mt-1 w-full px-4 py-2 border rounded-lg"
                                required><?= $oc['descricao'] ?></textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="listaroco.php"
                        class="w-[40%] px-4 py-2 bg-gray-500 text-white text-center rounded-lg hover:bg-gray-600">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="w-[40%] px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Guardar Alterações
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>

</body>
</html>
