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

$IDenc = intval($_SESSION['id']);

/* ================================
   FILTROS
================================ */
$pesquisa = $_GET['pesquisa'] ?? "";
$salaF    = $_GET['sala'] ?? "";

/* ================================
   BUSCAR FILHOS (SEM JOIN)
================================ */
$where = "WHERE IDutl = $IDenc AND estado = 1";

if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND nome LIKE '%$p%'";
}

if (!empty($salaF)) {
    $where .= " AND IDsala = " . intval($salaF);
}

$resFilhos = mysqli_query($link, "
    SELECT * FROM crianca
    $where
    ORDER BY nome ASC
");

/* Converter para array */
$filhos = [];
while ($f = mysqli_fetch_assoc($resFilhos)) {
    $filhos[] = $f;
}

/* ================================
   PAGINAÇÃO POR CRIANÇAS
================================ */
$registosPorPagina = 1; // 3 crianças por página
$totalRegistos = count($filhos);
$totalPaginas = max(1, ceil($totalRegistos / $registosPorPagina));

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;

$offset = ($paginaAtual - 1) * $registosPorPagina;

$filhosPagina = array_slice($filhos, $offset, $registosPorPagina);

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

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

/* ================================
   SEMANA ATUAL
================================ */
$hoje = date('Y-m-d');
$base = $_GET['data'] ?? $hoje;

$diaSemana = date('N', strtotime($base));
$segunda = date('Y-m-d', strtotime($base . " -" . ($diaSemana - 1) . " days"));
$sexta   = date('Y-m-d', strtotime($segunda . " +4 days"));

/* ================================
   BUSCAR MENUS DA SEMANA
================================ */
$menus = mysqli_query($link, "
    SELECT * FROM menu_semana
    WHERE data BETWEEN '$segunda' AND '$sexta'
    ORDER BY data ASC
");

$menuDia = [];
while ($m = mysqli_fetch_assoc($menus)) {
    $menuDia[$m['data']] = $m;
}

function estado($v) {
    if ($v === null) return "<span class='estado-nenhum'>—</span>";
    if ($v == 2) return "<span class='estado-tudo'>🟢 Tudo</span>";
    if ($v == 1) return "<span class='estado-parcial'>🟡 Parcial</span>";
    if ($v == 0) return "<span class='estado-nada'>🔴 Nada</span>";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Refeições — Encarregado</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">

    <style>
        .estado-tudo { color: #16a34a; font-weight: bold; }
        .estado-parcial { color: #ca8a04; font-weight: bold; }
        .estado-nada { color: #dc2626; font-weight: bold; }
        .estado-nenhum { color: #6b7280; font-weight: bold; }
    </style>
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

    <!-- WRAPPER FLEX RESPONSIVO -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_encarregado.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_encarregado.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            Refeições da Semana (<?= date('d/m', strtotime($segunda)) ?> - <?= date('d/m', strtotime($sexta)) ?>)
        </h1>

        <a href="encarregado.php"
        class="mb-6 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700">
            ← Voltar
        </a>

        <form method="GET" id="filtrosForm"
            class="bg-white p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                md:grid-cols-[2fr_1fr_auto] gap-4">

            <!-- 🔍 PESQUISA -->
            <div>
                <label class="font-semibold">Pesquisar criança:</label>
                <input type="text" name="pesquisa" id="pesquisaInput"
                    placeholder="Nome..."
                    value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                    class="border p-2 rounded w-full">
            </div>

            <!-- 🏫 SALA -->
            <div>
                <label class="font-semibold">Sala:</label>
                <select name="sala" class="border p-2 rounded w-full"
                        onchange="filtrosForm.submit()">
                    <option value="">Todas</option>

                    <?php
                    $resSalas = mysqli_query($link, "
                        SELECT IDsala, nome FROM sala WHERE estado = 1 ORDER BY nome ASC
                    ");
                    while ($s = mysqli_fetch_assoc($resSalas)):
                    ?>
                        <option value="<?= $s['IDsala'] ?>" <?= ($salaF==$s['IDsala']?'selected':'') ?>>
                            <?= $s['nome'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- ✖️ RESET -->
            <div class="flex mt-6 items-center justify-end">
                <button type="button"
                    onclick="window.location.href='encarregado_refeicoes.php'"
                    class="text-gray-500 hover:text-red-600 transition text-2xl"
                    title="Limpar filtros">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </button>
            </div>

        </form>

        <script>
        const form = document.getElementById('filtrosForm');
        const inputPesquisa = document.getElementById('pesquisaInput');

        // sempre que o form for submetido, remove o parâmetro "pagina"
        form.addEventListener('submit', function (e) {
            // deixa submeter normalmente, só vamos limpar a query
            const url = new URL(window.location.href);
            url.searchParams.delete('pagina');
            // redireciona já com os novos filtros e sem pagina
            // (o browser vai voltar sempre à página 1)
            setTimeout(() => {
                window.location.href = url.pathname + '?' + new URLSearchParams(new FormData(form)).toString();
            }, 0);
            e.preventDefault();
        });

        // enter na pesquisa também submete o form
        if (inputPesquisa) {
            inputPesquisa.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.submit();
                }
            });
        }
        </script>

        <!-- NAVEGAÇÃO SEMANAL -->
        <div class="flex gap-4 mb-6">

            <a href="encarregado_refeicoes.php?data=<?= date('Y-m-d', strtotime($segunda . ' -7 days')) ?>"
               class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                ← Semana anterior
            </a>

            <a href="encarregado_refeicoes.php?data=<?= date('Y-m-d') ?>"
               class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Semana atual
            </a>

            <a href="encarregado_refeicoes.php?data=<?= date('Y-m-d', strtotime($segunda . ' +7 days')) ?>"
               class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Semana seguinte →
            </a>

        </div>

        <!-- LISTA DE FILHOS (COM PAGINAÇÃO) -->
        <?php if (empty($filhosPagina)): ?>

            <p class="text-center text-gray-600 text-lg py-10">
                Nenhuma criança encontrada com os filtros aplicados.
            </p>

        <?php else: ?>

            <?php foreach ($filhosPagina as $f): ?>

                <div class="bg-white shadow rounded-lg p-6 mb-10">

                    <h2 class="text-2xl font-bold text-gray-700 mb-4">
                        <?= $f['nome'] ?>
                    </h2>

                    <!-- TABELA SEMANAL -->
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-3 border">Dia</th>
                                <th class="p-3 border">Menu</th>
                                <th class="p-3 border">Lanche manhã</th>
                                <th class="p-3 border">Almoço</th>
                                <th class="p-3 border">Lanche tarde</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php
                        for ($i = 0; $i < 5; $i++):
                            $dia = date('Y-m-d', strtotime($segunda . " +$i days"));

                            // Buscar refeições marcadas
                            $ref = mysqli_fetch_assoc(mysqli_query($link, "
                                SELECT * FROM refeicao_crianca
                                WHERE IDcri = {$f['IDcri']} AND data = '$dia'
                            "));

                            // Menu do dia
                            $m = $menuDia[$dia] ?? null;
                        ?>
                            <tr class="bg-gray-50">
                                <td class="p-3 border font-semibold">
                                    <?= date('d/m', strtotime($dia)) ?>
                                </td>

                                <td class="p-3 border">
                                    <?php if ($m): ?>
                                        <strong>Lanche manhã:</strong> <?= $m['lanche_manha'] ?><br>
                                        <strong>Almoço:</strong> <?= $m['almoco'] ?><br>
                                        <strong>Lanche tarde:</strong> <?= $m['lanche_tarde'] ?>
                                    <?php else: ?>
                                        <span class="text-red-600 font-semibold">Sem menu</span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-3 border"><?= estado($ref['lanche_manha'] ?? null) ?></td>
                                <td class="p-3 border"><?= estado($ref['almoco'] ?? null) ?></td>
                                <td class="p-3 border"><?= estado($ref['lanche_tarde'] ?? null) ?></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>
        
        <!-- paginação -->
        <?php if ($totalPaginas > 1): ?>
        <div class="flex justify-center mt-10 text-center">
            <div class="flex items-center space-x-2">

                <a href="?pagina=1<?= $queryStringFiltros ?>"
                class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">««</a>

                <a href="?pagina=<?= max(1, $paginaAtual - 1) ?><?= $queryStringFiltros ?>"
                class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">«</a>

                <?php
                    $inicio = max(1, $paginaAtual - 2);
                    $fim = min($totalPaginas, $inicio + 4);

                    if ($fim - $inicio < 4) {
                        $inicio = max(1, $fim - 4);
                    }

                    for ($i = $inicio; $i <= $fim; $i++):
                ?>

                <a href="?pagina=<?= $i ?><?= $queryStringFiltros ?>"
                class="w-12 h-12 flex items-center justify-center rounded 
                <?= $i == $paginaAtual ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                    <?= $i ?>
                </a>

                <?php endfor; ?>

                <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) ?><?= $queryStringFiltros ?>"
                class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»</a>

                <a href="?pagina=<?= $totalPaginas ?><?= $queryStringFiltros ?>"
                class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»»</a>

            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
