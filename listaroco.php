<?php
session_start();
include "DBConnection.php";

/* ============================================================
   PAGINAÇÃO
============================================================ */
$registosPorPagina = 1;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

/* ============================================================
   FILTROS
============================================================ */
$pesquisa   = $_GET['pesquisa']   ?? "";
$ordem      = $_GET['ordem']      ?? "";
$crianca    = $_GET['crianca']    ?? "";
$educador   = $_GET['educador']   ?? "";
$gravidade  = $_GET['gravidade']  ?? "";
$tipo       = $_GET['tipo']       ?? "";
$dataFiltro = $_GET['dataFiltro'] ?? "";

/* ============================================================
   WHERE DINÂMICO
============================================================ */
$where = "WHERE estado = 1";

/* 🔍 Pesquisa */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND (descricao LIKE '%$p%' OR tipo LIKE '%$p%' OR tipo_outro LIKE '%$p%')";
}

/* 🧒 Criança */
if (!empty($crianca)) {
    $idCri = (int)$crianca;
    $where .= " AND IDcri = $idCri";
}

/* 👩‍🏫 Educador criador */
if (!empty($educador)) {
    $idEdu = (int)$educador;
    $where .= " AND IDedu = $idEdu";
}

/* ⚠ Gravidade */
if (!empty($gravidade)) {
    $g = mysqli_real_escape_string($link, $gravidade);
    $where .= " AND gravidade = '$g'";
}

/* 📝 Tipo */
if (!empty($tipo)) {
    $t = mysqli_real_escape_string($link, $tipo);
    $where .= " AND tipo = '$t'";
}

/* 📅 Data */
if ($dataFiltro === "hoje") {
    $where .= " AND DATE(datahora) = CURDATE()";
}
if ($dataFiltro === "semana") {
    $where .= " AND datahora >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
}
if ($dataFiltro === "mes") {
    $where .= " AND datahora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

/* 🔤 Ordenação */
$ordemSQL = "ORDER BY IDoc DESC";

if ($ordem === "az")  $ordemSQL = "ORDER BY tipo ASC";
if ($ordem === "za")  $ordemSQL = "ORDER BY tipo DESC";
if ($ordem === "old") $ordemSQL = "ORDER BY IDoc ASC";

/* ============================================================
   CONTAGEM TOTAL
============================================================ */
$sqlTotal = "SELECT COUNT(*) AS total FROM ocorrencia $where";
$resultTotal = mysqli_query($link, $sqlTotal);
$totalRegistos = mysqli_fetch_assoc($resultTotal)['total'];

$totalPaginas = ceil($totalRegistos / $registosPorPagina);
if ($totalPaginas < 1) $totalPaginas = 1;

if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;

$offset = ($paginaAtual - 1) * $registosPorPagina;

/* ============================================================
   QUERY PRINCIPAL
============================================================ */
$sql = "
    SELECT * FROM ocorrencia
    $where
    $ordemSQL
    LIMIT $offset, $registosPorPagina
";

$result = mysqli_query($link, $sql);
$totalResultados = mysqli_num_rows($result);

/* ============================================================
   PRESERVAR FILTROS NA PAGINAÇÃO
============================================================ */
$queryStringFiltros = "";

foreach ($_GET as $key => $value) {
    if ($key !== "pagina") {
        $queryStringFiltros .= "&$key=" . urlencode($value);
    }
}

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

// Buscar tema do utilizador
$stmtTema = mysqli_prepare($link, "SELECT tema FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtTema, "i", $IDutl);
mysqli_stmt_execute($stmtTema);
$resTema = mysqli_stmt_get_result($stmtTema);
$tema = mysqli_fetch_assoc($resTema)['tema'] ?? 'light';

// Atualizar sessão
$_SESSION['tema'] = $tema;


$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDutl = intval($_SESSION['id']);

// PROCESSO DE DESATIVAÇÃO (SOFT DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desativar_id'])) {

    $id = intval($_POST['desativar_id']);

    // Soft delete direto (admin pode tudo)
    mysqli_query($link, "UPDATE ocorrencia SET estado = 0 WHERE IDoc = $id");

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");
    mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                         VALUES ('Ocorrência desativada pelo admin (ID $id)', '$fdatahora', '$IDutl')");

    echo "ok";
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Ocorrências — Administrador</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<!-- SCRIPT global de toast-->
<script>
    function mostrarMensagem(tipo, texto) {
        const box = document.getElementById("msgGlobal");
        const icon = document.getElementById("msgIcon");
        const msg = document.getElementById("msgTexto");

        // Limpar classes antigas
        box.classList.remove("border-blue-600", "border-green-600", "border-yellow-500", "border-red-600");
        msg.classList.remove("text-blue-600", "text-green-600", "text-yellow-500", "text-red-600");

        const icons = {
            adicionar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-blue-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m4.5 12.75 6 6 9-13.5" />
            </svg>`,

            editar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 
                        2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 
                        1.13L6 18l.8-2.685a4.5 4.5 0 0 1 
                        1.13-1.897l8.932-8.931Zm0 0L19.5 
                        7.125M18 14v4.75A2.25 2.25 0 0 1 
                        15.75 21H5.25A2.25 2.25 0 0 1 
                        3 18.75V8.25A2.25 2.25 0 0 1 
                        5.25 6H10" />
            </svg>`,

            reset: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-yellow-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 
                        3.374 1.948 3.374h14.71c1.73 0 
                        2.813-1.874 1.948-3.374L13.949 
                        3.378c-.866-1.5-3.032-1.5-3.898 
                        0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>`,

            eliminar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-red-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21
                        c.342.052.682.107 1.022.166m-1.022-.165L19.5 19.5
                        a2.25 2.25 0 0 1-2.244 2.25H6.744A2.25 2.25 0 0 1
                        4.5 19.5L5.772 5.79m14.456 0a48.108 48.108 0 0 0
                        -3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0
                        a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164
                        -2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09
                        1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>`
        };

        // Aplicar ícone
        icon.innerHTML = icons[tipo];
        msg.textContent = texto;

        // Aplicar cor do texto
        if (tipo === "adicionar") msg.classList.add("text-blue-600");
        if (tipo === "editar") msg.classList.add("text-green-600");
        if (tipo === "reset") msg.classList.add("text-yellow-500");
        if (tipo === "eliminar") msg.classList.add("text-red-600");

        // Aplicar cor da borda
        if (tipo === "adicionar") box.classList.add("border-blue-600");
        if (tipo === "editar") box.classList.add("border-green-600");
        if (tipo === "reset") box.classList.add("border-yellow-500");
        if (tipo === "eliminar") box.classList.add("border-red-600");

        // Mostrar
        box.classList.remove("hidden", "opacity-0");
        box.classList.add("opacity-100");

        // Ocultar após 3 segundos
        setTimeout(() => {
            box.classList.add("opacity-0");
            setTimeout(() => box.classList.add("hidden"), 300);
        }, 3000);
    }
</script>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">

    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal" 
        class="hidden fixed top-5 right-5 bg-white dark:bg-gray-800 dark:text-gray-100 
               shadow-lg border-l-4 border-blue-500 dark:border-blue-400 
               rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

    <!-- WRAPPER -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_admin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_admin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Listar Ocorrências das crianças da creche
            </h1>

            <a href="admin.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                   rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>

            <!-- FILTROS -->
            <form method="GET" id="filtrosForm"
                class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                       md:grid-cols-[2fr_1fr_1fr_1fr_1fr_1fr_auto] gap-4">

                <!-- PESQUISA -->
                <div class="relative">
                    <label class="font-semibold dark:text-gray-200">Pesquisar:</label>
                    <input type="text" name="pesquisa" id="pesquisaInput"
                        placeholder="Descrição, tipo..."
                        value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100">
                </div>

                <!-- ORDEM -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Ordenar por:</label>
                    <select name="ordem"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">Mais recentes</option>
                        <option value="az"  <?= ($_GET['ordem'] ?? '')=='az'?'selected':'' ?>>Tipo A → Z</option>
                        <option value="za"  <?= ($_GET['ordem'] ?? '')=='za'?'selected':'' ?>>Tipo Z → A</option>
                        <option value="old" <?= ($_GET['ordem'] ?? '')=='old'?'selected':'' ?>>Mais antigas</option>
                    </select>
                </div>

                <!-- CRIANÇA -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Criança:</label>
                    <select name="crianca"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todas --</option>

                        <?php
                        $resCri = mysqli_query($link, "SELECT IDcri, nome FROM crianca WHERE estado = 1 ORDER BY nome ASC");
                        while ($c = mysqli_fetch_assoc($resCri)):
                        ?>
                            <option value="<?= $c['IDcri'] ?>" <?= ($_GET['crianca'] ?? '') == $c['IDcri'] ? 'selected' : '' ?>>
                                <?= $c['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- EDUCADOR -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Educador:</label>
                    <select name="educador"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>

                        <?php
                        $resEdu = mysqli_query($link, "SELECT IDedu, IDutl FROM educador WHERE estado = 1");
                        while ($e = mysqli_fetch_assoc($resEdu)):
                            $u = mysqli_fetch_assoc(mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$e['IDutl']}"));
                        ?>
                            <option value="<?= $e['IDedu'] ?>" <?= ($_GET['educador'] ?? '') == $e['IDedu'] ? 'selected' : '' ?>>
                                <?= $u['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- GRAVIDADE -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Gravidade:</label>
                    <select name="gravidade"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todas --</option>
                        <option value="Leve"     <?= ($_GET['gravidade'] ?? '')=='Leve'?'selected':'' ?>>Leve</option>
                        <option value="Moderada" <?= ($_GET['gravidade'] ?? '')=='Moderada'?'selected':'' ?>>Moderada</option>
                        <option value="Grave"    <?= ($_GET['gravidade'] ?? '')=='Grave'?'selected':'' ?>>Grave</option>
                    </select>
                </div>

                <!-- TIPO -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Tipo:</label>
                    <select name="tipo"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>
                        <option value="Queda" <?= ($_GET['tipo'] ?? '')=='Queda'?'selected':'' ?>>Queda</option>
                        <option value="Doença" <?= ($_GET['tipo'] ?? '')=='Doença'?'selected':'' ?>>Doença</option>
                        <option value="Outro" <?= ($_GET['tipo'] ?? '')=='Outro'?'selected':'' ?>>Outro</option>
                    </select>
                </div>

                <!-- RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='listaroco.php'"
                        class="text-gray-500 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-500 transition text-2xl"
                        title="Limpar filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 
                                     3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 
                                     13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </button>
                </div>

            </form>

            <script>
            const input = document.getElementById("pesquisaInput");
            const form = document.getElementById("filtrosForm");

            input.addEventListener("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    form.submit();
                }
            });

            input.addEventListener("blur", function() {
                form.submit();
            });
            </script>

            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <!-- GRID -->
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                    <?php if ($totalResultados == 0): ?>
                        <p class="col-span-3 text-center text-gray-600 dark:text-gray-300 text-lg py-10">
                            <span class="text-4xl mb-2">🔍</span>
                            Nenhuma ocorrência encontrada.
                        </p>
                    <?php else: ?>

                        <?php

                        while ($o = mysqli_fetch_assoc($result)) {

                            $IDcri = intval($o['IDcri']);
                            $IDeduCriador = intval($o['IDedu']);

                            // Nome da criança
                            $criNome = "—";
                            $resCri = mysqli_query($link, "SELECT nome FROM crianca WHERE IDcri = $IDcri");
                            if ($resCri && mysqli_num_rows($resCri) > 0) {
                                $criNome = mysqli_fetch_assoc($resCri)['nome'];
                            }

                            // Nome do educador criador
                            $eduNome = "—";
                            $resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDedu = $IDeduCriador");
                            if ($resEdu && mysqli_num_rows($resEdu) > 0) {
                                $IDutlCriador = mysqli_fetch_assoc($resEdu)['IDutl'];

                                $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutlCriador");
                                if ($resU && mysqli_num_rows($resU) > 0) {
                                    $eduNome = mysqli_fetch_assoc($resU)['nome'];
                                }
                            }

                            // Tipo final
                            $tipoFinal = ($o['tipo'] === "Outro" && !empty($o['tipo_outro']))
                                ? "Outro (" . $o['tipo_outro'] . ")"
                                : $o['tipo'];

                            // Descrição curta
                            $desc = strlen($o['descricao']) > 40
                                    ? substr($o['descricao'], 0, 40) . "..."
                                    : $o['descricao'];
                        ?>

                            <div class="bg-green-50 dark:bg-green-900/20 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                                    Ocorrência #<?= $o['IDoc'] ?>
                                </h2>

                                <div class="text-gray-700 dark:text-gray-200 space-y-1 mb-4">
                                    <p><strong>Data:</strong> <?= $o['datahora'] ?></p>
                                    <p><strong>Criança:</strong> <?= $criNome ?></p>
                                    <p><strong>Tipo:</strong> <?= $tipoFinal ?></p>
                                    <p><strong>Gravidade:</strong> <?= $o['gravidade'] ?></p>
                                    <p><strong>Descrição:</strong> <?= $desc ?></p>
                                    <p><strong>Criado por:</strong> <?= $eduNome ?></p>
                                </div>

                                <div class="flex gap-3">

                                    <!-- Ícone Editar -->
                                    <button onclick="window.location.href='editaroco.php?id=<?= $o['IDoc'] ?>'"
                                        class="text-gray-500 dark:text-gray-300 hover:text-yellow-500  dark:hover:text-yellow-400 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                        </svg>
                                    </button>

                                    <!-- Ícone Eliminar -->
                                    <button onclick="desativarOcorrencia(<?= $o['IDoc'] ?>)"
                                        class="text-gray-500 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 011-1h4a1 1 0 011 1m-6 0h6" />
                                        </svg>
                                    </button>

                                </div>
                            </div>

                        <?php } ?>
                    <?php endif ?>
                </div>
            </div>

            <!-- PAGINAÇÃO -->
            <?php if ($totalPaginas > 1): ?>
            <div class="flex justify-center mt-10 text-center">
                <div class="flex items-center space-x-2">

                    <!-- PRIMEIRA -->
                    <a href="?pagina=1<?= $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">««</a>

                    <!-- ANTERIOR -->
                    <a href="?pagina=<?= max(1, $paginaAtual - 1) . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">«</a>

                    <?php
                        $inicio = max(1, $paginaAtual - 2);
                        $fim = min($totalPaginas, $inicio + 4);

                        if ($fim - $inicio < 4) {
                            $inicio = max(1, $fim - 4);
                        }

                        for ($i = $inicio; $i <= $fim; $i++):
                    ?>

                    <!-- NÚMEROS -->
                    <a href="?pagina=<?= $i . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center rounded
                    <?= $i == $paginaAtual 
                        ? 'bg-blue-600 dark:bg-blue-700 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                        <?= $i ?>
                    </a>

                    <?php endfor; ?>

                    <!-- SEGUINTE -->
                    <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">»</a>

                    <!-- ÚLTIMA -->
                    <a href="?pagina=<?= $totalPaginas . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">»»</a>

                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

<!-- Modal Eliminar Ocorrência-->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
            Confirmar Desativação
        </h2>

        <p class="text-gray-700 dark:text-gray-200 mb-6">
            Tens a certeza que desejas desativar esta ocorrência?
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="fecharModal()"
                class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white rounded 
                       hover:bg-gray-600 dark:hover:bg-gray-500">
                Cancelar
            </button>

            <button id="btnConfirmarEliminar"
                class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white rounded 
                       hover:bg-red-700 dark:hover:bg-red-600">
                Desativar
            </button>
        </div>
    </div>
</div>

<!-- SCRIPT para eliminar ocorrência -->
<script>
    let idOcorrenciaParaEliminar = null;

    function desativarOcorrencia(id) {
        idOcorrenciaParaEliminar = id;
        const modal = document.getElementById("modalEliminar");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function fecharModal() {
        const modal = document.getElementById("modalEliminar");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        idOcorrenciaParaEliminar = null;
    }

    document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

        if (idOcorrenciaParaEliminar === null) return;

        fetch("listaroco.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "desativar_id=" + idOcorrenciaParaEliminar
        })
        .then(r => r.text())
        .then(res => {

            res = res.trim();

            if (res === "ok") {
                fecharModal();
                mostrarMensagem("eliminar", "Ocorrência eliminada com sucesso!");
                setTimeout(() => location.reload(), 1200);
                return;
            }

            mostrarMensagem("reset", "Erro ao atualizar a ocorrência.");
        });
    });
</script>

<!-- TOAST: ocorrência editada -->
<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'editado'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("editar", "Ocorrência atualizada com sucesso!");
});
</script>
<?php endif; ?>

</body>
</html>
