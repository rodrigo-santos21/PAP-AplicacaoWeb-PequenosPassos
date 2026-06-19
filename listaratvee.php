<?php
session_start();
include "DBConnection.php";

/* ================================
   VALIDAR ENCARREGADO
================================ */
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDEE = intval($_SESSION['id']);

/* ================================
   FILTROS
================================ */
$pesquisa  = $_GET['pesquisa']  ?? "";
$criancaF  = $_GET['crianca']   ?? "";
$ordem     = $_GET['ordem']     ?? "";

/* ================================
   BUSCAR CRIANÇAS DO EE (SEM JOIN)
================================ */
$criancas = [];
$resCri = mysqli_query($link, "
    SELECT IDcri, nome 
    FROM crianca 
    WHERE estado = 1 AND IDutl = $IDEE
");

while ($c = mysqli_fetch_assoc($resCri)) {
    $criancas[] = $c;
}

/* ================================
   BUSCAR TODAS AS ATIVIDADES (SEM JOIN)
================================ */
$atividades = [];

foreach ($criancas as $cri) {

    $IDcri = $cri['IDcri'];
    $nomeCri = $cri['nome'];

    $resRel = mysqli_query($link, "
        SELECT IDatv, realizada 
        FROM crianca_atividade 
        WHERE IDcri = $IDcri AND estado = 1
    ");

    while ($rel = mysqli_fetch_assoc($resRel)) {

        $IDatv = $rel['IDatv'];
        $realizada = $rel['realizada'];

        $resAtv = mysqli_query($link, "
            SELECT * FROM atividade 
            WHERE IDatv = $IDatv AND estado = 1
        ");

        $a = mysqli_fetch_assoc($resAtv);
        if (!$a) continue;

        $atividades[] = [
            'atividade'  => $a,
            'crianca'    => $nomeCri,
            'realizada'  => $realizada
        ];
    }
}

/* ================================
   APLICAR FILTROS AO ARRAY
================================ */

/* Criança */
if (!empty($criancaF)) {
    $atividades = array_filter($atividades, function($x) use ($criancaF) {
        return $x['atividade']['IDatv'] && $x['crianca'] && intval($x['atividade']['IDcri']) === intval($criancaF);
    });
}

/* Pesquisa */
if (!empty($pesquisa)) {
    $p = strtolower($pesquisa);
    $atividades = array_filter($atividades, function($x) use ($p) {
        return strpos(strtolower($x['atividade']['titulo']), $p) !== false ||
               strpos(strtolower($x['atividade']['descricao']), $p) !== false;
    });
}

/* ================================
   ORDENAR
================================ */
if ($ordem === "old") {
    usort($atividades, fn($a,$b) => $a['atividade']['IDatv'] - $b['atividade']['IDatv']);
}
elseif ($ordem === "az") {
    usort($atividades, fn($a,$b) => strcmp($a['atividade']['titulo'], $b['atividade']['titulo']));
}
elseif ($ordem === "za") {
    usort($atividades, fn($a,$b) => strcmp($b['atividade']['titulo'], $a['atividade']['titulo']));
}
else {
    // Mais recentes
    usort($atividades, fn($a,$b) => $b['atividade']['IDatv'] - $a['atividade']['IDatv']);
}

/* ================================
   PAGINAÇÃO
================================ */
$registosPorPagina = 9;
$totalRegistos = count($atividades);
$totalPaginas = max(1, ceil($totalRegistos / $registosPorPagina));

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;

$offset = ($paginaAtual - 1) * $registosPorPagina;

$atividadesPagina = array_slice($atividades, $offset, $registosPorPagina);

/* ================================
   PRESERVAR FILTROS NA PAGINAÇÃO
================================ */
$queryStringFiltros = "";
foreach ($_GET as $key => $value) {
    if ($key !== "pagina") {
        $queryStringFiltros .= "&$key=" . urlencode($value);
    }
}

/* ================================
   FOTO DO UTILIZADOR
================================ */
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
?>

<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Atividades das Suas Crianças</title>
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

    <!-- WRAPPER -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_encarregado.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_encarregado.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Listar atividades das suas crianças
            </h1>

            <a href="encarregado.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                   rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>

            <!-- FILTROS -->
            <form method="GET" id="filtrosForm"
                class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                       md:grid-cols-[2fr_1fr_1fr_auto] gap-4">

                <!-- PESQUISA -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Pesquisar:</label>
                    <input type="text" name="pesquisa" id="pesquisaInput"
                        placeholder="Título ou descrição..."
                        value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-900 dark:text-gray-100">
                </div>

                <!-- CRIANÇA -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Criança:</label>
                    <select name="crianca"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-900 dark:text-gray-100"
                        onchange="filtrosForm.submit()">
                        <option value="">Todas</option>

                        <?php
                        $resCri2 = mysqli_query($link, "
                            SELECT IDcri, nome FROM crianca 
                            WHERE estado = 1 AND IDutl = $IDEE
                            ORDER BY nome ASC
                        ");
                        while ($c = mysqli_fetch_assoc($resCri2)):
                        ?>
                            <option value="<?= $c['IDcri'] ?>" <?= ($criancaF==$c['IDcri']?'selected':'') ?>>
                                <?= $c['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- ORDEM -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Ordenar por:</label>
                    <select name="ordem"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-900 dark:text-gray-100"
                        onchange="filtrosForm.submit()">
                        <option value="">Mais recentes</option>
                        <option value="old" <?= ($ordem=='old'?'selected':'') ?>>Mais antigas</option>
                        <option value="az"  <?= ($ordem=='az'?'selected':'') ?>>A → Z</option>
                        <option value="za"  <?= ($ordem=='za'?'selected':'') ?>>Z → A</option>
                    </select>
                </div>

                <!-- RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='listaratvee.php'"
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

            <!-- LISTAGEM -->
            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                <?php
                $resCri = mysqli_query($link, "
                    SELECT IDcri, nome 
                    FROM crianca 
                    WHERE estado = 1 AND IDutl = $IDEE
                ");

                if (mysqli_num_rows($resCri) === 0) {
                    echo "
                    <div class='col-span-3 text-center text-gray-500 dark:text-gray-300'>
                        Não existem crianças associadas à sua conta.
                    </div>";
                }

                foreach ($atividadesPagina as $item) {

                    $a = $item['atividade'];
                    $nomeCri = $item['crianca'];
                    $realizada = $item['realizada'];

                    /* Buscar responsável */
                    $responsavel = "—";

                    if (!empty($a['IDedu'])) {

                        $resEdu = mysqli_query($link, "
                            SELECT IDutl FROM educador WHERE IDedu = {$a['IDedu']}
                        ");

                        if ($resEdu && mysqli_num_rows($resEdu) > 0) {
                            $edu = mysqli_fetch_assoc($resEdu);

                            $resU = mysqli_query($link, "
                                SELECT nome FROM utilizador WHERE IDutl = {$edu['IDutl']}
                            ");

                            if ($resU && mysqli_num_rows($resU) > 0) {
                                $u = mysqli_fetch_assoc($resU);
                                $responsavel = $u['nome'];
                            }
                        }

                    } else {
                        $resAdmin = mysqli_query($link, "
                            SELECT nome FROM utilizador WHERE IDutl = {$a['criadopor']}
                        ");

                        if ($resAdmin && mysqli_num_rows($resAdmin) > 0) {
                            $adm = mysqli_fetch_assoc($resAdmin);
                            $responsavel = $adm['nome'];
                        }
                    }

                    /* Descrição curta */
                    $desc = strlen($a['descricao']) > 60
                            ? substr($a['descricao'], 0, 60) . "..."
                            : $a['descricao'];

                    /* Realizada? */
                    $estadoRealizada = $realizada == 1
                        ? "<span class='text-green-600 font-semibold'>Sim</span>"
                        : "<span class='text-red-600 font-semibold'>Não</span>";
                ?>

                    <div class="bg-blue-50 dark:bg-gray-700 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                            <?= $a['titulo'] ?>
                        </h2>

                        <div class="text-gray-700 dark:text-gray-200 space-y-1 mb-4">
                            <p><strong>ID:</strong> <?= $IDatv ?></p>
                            <p><strong>Criança:</strong> <?= $nomeCri ?></p>
                            <p><strong>Data/Hora:</strong> <?= $a['datahora'] ?></p>
                            <p><strong>Responsável:</strong> <?= $responsavel ?></p>
                            <p><strong>Realizada:</strong> <?= $estadoRealizada ?></p>
                            <p><strong>Descrição:</strong> <?= $desc ?></p>
                        </div>

                    </div>
                <?php } ?>
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
