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
$tipo       = $_GET['tipo']       ?? "";
$gravidade  = $_GET['gravidade']  ?? "";
$crianca    = $_GET['crianca']    ?? "";
$educador   = $_GET['educador']   ?? "";
$ordem      = $_GET['ordem']      ?? "";

/* ============================================================
   WHERE DINÂMICO
============================================================ */
$where = "WHERE estado = 1";

/* 🔍 Pesquisa (descrição) */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND descricao LIKE '%$p%'";
}

/* 👶 Criança */
if (!empty($crianca)) {
    $idc = (int)$crianca;
    $where .= " AND IDcri = $idc";
}

/* 👨‍🏫 Educador criador */
if (!empty($educador)) {
    $ide = (int)$educador;
    $where .= " AND IDedu = $ide";
}

/* 📝 Tipo */
if (!empty($tipo)) {
    $t = mysqli_real_escape_string($link, $tipo);
    $where .= " AND tipo = '$t'";
}

/* ⚠ Gravidade */
if (!empty($gravidade)) {
    $g = mysqli_real_escape_string($link, $gravidade);
    $where .= " AND gravidade = '$g'";
}

/* 🔤 Ordenação */
$ordemSQL = "ORDER BY IDoc DESC";

if ($ordem === "old") $ordemSQL = "ORDER BY IDoc ASC";
if ($ordem === "az")  $ordemSQL = "ORDER BY tipo ASC";
if ($ordem === "za")  $ordemSQL = "ORDER BY tipo DESC";

/* ============================================================
   CONTAGEM TOTAL
============================================================ */
$resTotal = mysqli_query($link, "SELECT IDoc FROM ocorrencia $where");
$totalRegistos = mysqli_num_rows($resTotal);

$totalPaginas = max(1, ceil($totalRegistos / $registosPorPagina));

if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;

$offset = ($paginaAtual - 1) * $registosPorPagina;

/* ============================================================
   QUERY PRINCIPAL
============================================================ */
$result = mysqli_query($link, "
    SELECT * FROM ocorrencia
    $where
    $ordemSQL
    LIMIT $offset, $registosPorPagina
");

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

// Apenas funcionários podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDutl = intval($_SESSION['id']);

?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Ocorrências — Funcionário</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">

    <!-- WRAPPER FLEX RESPONSIVO -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_funcionario.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_funcionario.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Listar Ocorrências
            </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                   rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>

            <form method="GET" id="filtrosForm"
                class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                       md:grid-cols-[2fr_1fr_1fr_1fr_1fr_1fr_auto] gap-4">

                <!-- 🔍 PESQUISA -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Pesquisar:</label>
                    <input type="text" name="pesquisa" id="pesquisaInput"
                        placeholder="Descrição..."
                        value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100">
                </div>

                <!-- 📝 TIPO -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Tipo:</label>
                    <select name="tipo"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="filtrosForm.submit()">
                        <option value="">Todos</option>
                        <option value="Doença" <?= ($tipo=='Doença'?'selected':'') ?>>Doença</option>
                        <option value="Queda" <?= ($tipo=='Queda'?'selected':'') ?>>Queda</option>                    
                        <option value="Comportamento" <?= ($tipo=='Comportamento'?'selected':'') ?>>Comportamento</option>
                        <option value="Agressão" <?= ($tipo=='Agressão'?'selected':'') ?>>Agressão</option>
                        <option value="Outro" <?= ($tipo=='Outro'?'selected':'') ?>>Outro</option>
                    </select>
                </div>

                <!-- ⚠ GRAVIDADE -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Gravidade:</label>
                    <select name="gravidade"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="filtrosForm.submit()">
                        <option value="">Todas</option>
                        <option value="Leve" <?= ($gravidade=='Leve'?'selected':'') ?>>Leve</option>
                        <option value="Moderada" <?= ($gravidade=='Moderada'?'selected':'') ?>>Moderada</option>
                        <option value="Grave" <?= ($gravidade=='Grave'?'selected':'') ?>>Grave</option>
                    </select>
                </div>

                <!-- 👶 CRIANÇA -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Criança:</label>
                    <select name="crianca"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="filtrosForm.submit()">
                        <option value="">Todas</option>
                        <?php
                        $resCri = mysqli_query($link, "SELECT IDcri, nome FROM crianca WHERE estado=1 ORDER BY nome ASC");
                        while ($c = mysqli_fetch_assoc($resCri)):
                        ?>
                            <option value="<?= $c['IDcri'] ?>" <?= ($crianca==$c['IDcri']?'selected':'') ?>>
                                <?= $c['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 👨‍🏫 EDUCATOR -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Criado por:</label>
                    <select name="educador"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="filtrosForm.submit()">
                        <option value="">Todos</option>
                        <?php
                        $resEdu = mysqli_query($link, "
                            SELECT e.IDedu, u.nome 
                            FROM educador e 
                            JOIN utilizador u ON u.IDutl = e.IDutl
                            WHERE e.estado=1 AND u.estado=1
                            ORDER BY u.nome ASC
                        ");
                        while ($e = mysqli_fetch_assoc($resEdu)):
                        ?>
                            <option value="<?= $e['IDedu'] ?>" <?= ($educador==$e['IDedu']?'selected':'') ?>>
                                <?= $e['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 🔤 ORDEM -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Ordenar:</label>
                    <select name="ordem"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100"
                        onchange="filtrosForm.submit()">
                        <option value="">Mais recentes</option>
                        <option value="old" <?= ($ordem=='old'?'selected':'') ?>>Mais antigas</option>
                        <option value="az" <?= ($ordem=='az'?'selected':'') ?>>Tipo A→Z</option>
                        <option value="za" <?= ($ordem=='za'?'selected':'') ?>>Tipo Z→A</option>
                    </select>
                </div>

                <!-- RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='listarocofun.php'"
                        class="text-gray-500 dark:text-gray-300 hover:text-red-600 transition text-2xl"
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
            input.addEventListener("keydown", e => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    filtrosForm.submit();
                }
            });
            input.addEventListener("blur", () => filtrosForm.submit());
            </script>

            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                    <?php if ($totalRegistos == 0): ?>

                        <p class="col-span-3 text-center text-gray-600 dark:text-gray-300 text-lg py-10 flex flex-col items-center">
                            <span class="text-5xl mb-3">🔍</span>
                            Nenhuma ocorrência encontrada com os filtros aplicados.
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
                                $cri = mysqli_fetch_assoc($resCri);
                                $criNome = $cri['nome'];
                            }

                            // Nome do educador criador
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

                            // Tipo final
                            if ($o['tipo'] === "Outro" && !empty($o['tipo_outro'])) {
                                $tipoFinal = "Outro (" . $o['tipo_outro'] . ")";
                            } else {
                                $tipoFinal = $o['tipo'];
                            }

                            // Descrição curta
                            $desc = strlen($o['descricao']) > 60
                                    ? substr($o['descricao'], 0, 60) . "..."
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
                    <a href="?pagina=<?= max(1, $paginaAtual - 1) ?><?= $queryStringFiltros ?>"
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
                    <a href="?pagina=<?= $i ?><?= $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center rounded
                    <?= $i == $paginaAtual 
                        ? 'bg-blue-600 dark:bg-blue-700 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                        <?= $i ?>
                    </a>

                    <?php endfor; ?>

                    <!-- SEGUINTE -->
                    <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) ?><?= $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">»</a>

                    <!-- ÚLTIMA -->
                    <a href="?pagina=<?= $totalPaginas ?><?= $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">»»</a>

                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>
