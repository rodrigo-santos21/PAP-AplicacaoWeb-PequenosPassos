<?php
session_start();
include "DBConnection.php";

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

// Apenas educadores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDedu = $_SESSION['id'];

// Buscar sala do educador
$resSala = mysqli_query($link, "SELECT IDsala FROM educador WHERE IDutl = $IDedu AND estado = 1");
$sala = mysqli_fetch_assoc($resSala);
$IDsala = $sala['IDsala'] ?? null;

if (!$IDsala) {
    die("Erro: Educador sem sala atribuída.");
}

// Data selecionada (ou hoje)
$hoje = date('Y-m-d');
$dataSelecionada = $_GET['data'] ?? $hoje;

// Buscar menu do dia selecionado
$menu = mysqli_fetch_assoc(mysqli_query($link, "
    SELECT * FROM menu_semana WHERE data = '$dataSelecionada' AND estado = 1
"));

/* ================================
   FILTROS
================================ */
$pesquisa = $_GET['pesquisa'] ?? "";

/* ================================
   BUSCAR CRIANÇAS DA SALA (SEM JOIN)
================================ */
$where = "WHERE IDsala = $IDsala AND estado = 1";

if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND nome LIKE '%$p%'";
}

$resCriancas = mysqli_query($link, "
    SELECT * FROM crianca
    $where
    ORDER BY nome ASC
");

/* Converter para array */
$criancasArray = [];
while ($c = mysqli_fetch_assoc($resCriancas)) {
    $criancasArray[] = $c;
}

/* ================================
   PAGINAÇÃO POR CRIANÇAS
================================ */
$registosPorPagina = 1; // número de crianças por página
$totalRegistos = count($criancasArray);
$totalPaginas = max(1, ceil($totalRegistos / $registosPorPagina));

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;

$offset = ($paginaAtual - 1) * $registosPorPagina;

$criancasPagina = array_slice($criancasArray, $offset, $registosPorPagina);

/* ================================
   PRESERVAR FILTROS NA PAGINAÇÃO
================================ */
$queryStringFiltros = "";
foreach ($_GET as $key => $value) {
    if ($key !== "pagina") {
        $queryStringFiltros .= "&$key=" . urlencode($value);
    }
}

?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Refeições — Educador</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function marcar(IDcri, refeicao, valor) {

        fetch("marcar_refeicao.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "IDcri=" + IDcri + "&refeicao=" + refeicao + "&valor=" + valor + "&data=<?= $dataSelecionada ?>"
        })
        .then(r => r.text())
        .then(res => {
            if (res.trim() === "ok") {
                mostrarMensagem("Registo atualizado.");
            } else {
                mostrarMensagem("Erro ao atualizar.", true);
            }
        });
    }

    function mostrarMensagem(texto, erro = false) {
        const div = document.createElement("div");
        div.className = "fixed top-5 right-5 px-4 py-2 rounded shadow-lg text-white " +
                        (erro ? "bg-red-600" : "bg-green-600");
        div.textContent = texto;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 2000);
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

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">

    <!-- WRAPPER FLEX RESPONSIVO -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_educador.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_educador.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-6">
            Refeições — <?= date('d/m/Y', strtotime($dataSelecionada)) ?>
        </h1>

        <form method="GET" id="filtrosForm"
            class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                md:grid-cols-[2fr_auto] gap-4">

            <!-- 🔍 PESQUISA -->
            <div>
                <label class="font-semibold dark:text-gray-200">Pesquisar criança:</label>
                <input type="text" name="pesquisa" id="pesquisaInput"
                    placeholder="Nome..."
                    value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                    class="border border-gray-300 dark:border-gray-600 
                           p-2 rounded w-full bg-white dark:bg-gray-700 dark:text-gray-100">
            </div>

            <!-- ✖️ RESET -->
            <div class="flex mt-6 items-center justify-end">
                <button type="button"
                    onclick="window.location.href='educador_refeicoes.php?data=<?= $dataSelecionada ?>'"
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

            <!-- Manter a data selecionada -->
            <input type="hidden" name="data" value="<?= $dataSelecionada ?>">

        </form>

        <script>
        const form = document.getElementById('filtrosForm');
        const inputPesquisa = document.getElementById('pesquisaInput');

        form.addEventListener('submit', function (e) {
            const url = new URL(window.location.href);
            url.searchParams.delete('pagina');
            setTimeout(() => {
                window.location.href = url.pathname + '?' + new URLSearchParams(new FormData(form)).toString();
            }, 0);
            e.preventDefault();
        });

        if (inputPesquisa) {
            inputPesquisa.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.submit();
                }
            });
        }
        </script>

        <!-- NAVEGAÇÃO ENTRE DIAS -->
        <div class="flex gap-4 mb-6">

            <!-- DIA ANTERIOR -->
            <?php
            $anterior = date('Y-m-d', strtotime($dataSelecionada . ' -1 day'));
            $diaSemanaAnterior = date('N', strtotime($anterior));

            if ($diaSemanaAnterior > 5) {
                $anterior = date('Y-m-d', strtotime('last friday', strtotime($anterior)));
            }
            ?>
            <a href="educador_refeicoes.php?data=<?= $anterior ?>"
               class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                      rounded hover:bg-gray-400 dark:hover:bg-gray-600">
                ← Dia anterior
            </a>

            <!-- HOJE -->
            <a href="educador_refeicoes.php?data=<?= date('Y-m-d') ?>"
               class="px-4 py-2 bg-blue-500 dark:bg-blue-700 text-white rounded hover:bg-blue-600 dark:hover:bg-blue-600">
                Hoje
            </a>

            <!-- DIA SEGUINTE -->
            <?php
            $seguinte = date('Y-m-d', strtotime($dataSelecionada . ' +1 day'));
            $diaSemanaSeguinte = date('N', strtotime($seguinte));

            if ($diaSemanaSeguinte > 5) {
                $seguinte = date('Y-m-d', strtotime('next monday', strtotime($seguinte)));
            }
            ?>
            <a href="educador_refeicoes.php?data=<?= $seguinte ?>"
               class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                      rounded hover:bg-gray-400 dark:hover:bg-gray-600">
                Dia seguinte →
            </a>

        </div>

        <!-- MENU DO DIA -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-4">Menu do Dia</h2>

            <?php if ($menu): ?>
                <p class="dark:text-gray-100"><strong>Lanche da manhã:</strong> <?= $menu['lanche_manha'] ?></p>
                <p class="dark:text-gray-100"><strong>Almoço:</strong> <?= $menu['almoco'] ?></p>
                <p class="dark:text-gray-100"><strong>Lanche da tarde:</strong> <?= $menu['lanche_tarde'] ?></p>
            <?php else: ?>
                <p class="text-red-600 font-semibold dark:text-red-400">
                    Ainda não foi definido menu para este dia.
                </p>
            <?php endif; ?>
        </div>

        <!-- LISTA DE CRIANÇAS -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">

            <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-6">Marcar Refeições</h2>

            <?php
            $temMenu = $menu ? true : false;
            $disabled = $temMenu ? "" : "opacity-40 cursor-not-allowed";
            ?>

            <?php if (empty($criancasPagina)): ?>

                <p class="text-center text-gray-600 dark:text-gray-300 text-lg py-10">
                    Nenhuma criança encontrada com os filtros aplicados.
                </p>

            <?php else: ?>

                <?php foreach ($criancasPagina as $c): ?>

                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded shadow">

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3">
                        <?= $c['nome'] ?>
                    </h3>

                    <!-- LANCHE MANHÃ -->
                    <p class="font-semibold dark:text-gray-200">Lanche da manhã:</p>
                    <div class="flex gap-2 mb-3">

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_manha', 2)\"" : "" ?>
                                class="px-3 py-1 bg-green-600 dark:bg-green-700 text-white rounded 
                                       hover:bg-green-700 dark:hover:bg-green-600 <?= $disabled ?>">
                            Tudo
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_manha', 1)\"" : "" ?>
                                class="px-3 py-1 bg-yellow-500 dark:bg-yellow-600 text-white rounded 
                                       hover:bg-yellow-600 dark:hover:bg-yellow-500 <?= $disabled ?>">
                            Parcial
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_manha', 0)\"" : "" ?>
                                class="px-3 py-1 bg-red-600 dark:bg-red-700 text-white rounded 
                                       hover:bg-red-700 dark:hover:bg-red-600 <?= $disabled ?>">
                            Nada
                        </button>

                    </div>

                    <!-- ALMOÇO -->
                    <p class="font-semibold dark:text-gray-200">Almoço:</p>
                    <div class="flex gap-2 mb-3">

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'almoco', 2)\"" : "" ?>
                                class="px-3 py-1 bg-green-600 dark:bg-green-700 text-white rounded 
                                       hover:bg-green-700 dark:hover:bg-green-600 <?= $disabled ?>">
                            Tudo
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'almoco', 1)\"" : "" ?>
                                class="px-3 py-1 bg-yellow-500 dark:bg-yellow-600 text-white rounded 
                                       hover:bg-yellow-600 dark:hover:bg-yellow-500 <?= $disabled ?>">
                            Parcial
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'almoco', 0)\"" : "" ?>
                                class="px-3 py-1 bg-red-600 dark:bg-red-700 text-white rounded 
                                       hover:bg-red-700 dark:hover:bg-red-600 <?= $disabled ?>">
                            Nada
                        </button>

                    </div>

                    <!-- LANCHE TARDE -->
                    <p class="font-semibold dark:text-gray-200">Lanche da tarde:</p>
                    <div class="flex gap-2">

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_tarde', 2)\"" : "" ?>
                                class="px-3 py-1 bg-green-600 dark:bg-green-700 text-white rounded 
                                       hover:bg-green-700 dark:hover:bg-green-600 <?= $disabled ?>">
                            Tudo
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_tarde', 1)\"" : "" ?>
                                class="px-3 py-1 bg-yellow-500 dark:bg-yellow-600 text-white rounded 
                                       hover:bg-yellow-600 dark:hover:bg-yellow-500 <?= $disabled ?>">
                            Parcial
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_tarde', 0)\"" : "" ?>
                                class="px-3 py-1 bg-red-600 dark:bg-red-700 text-white rounded 
                                       hover:bg-red-700 dark:hover:bg-red-600 <?= $disabled ?>">
                            Nada
                        </button>

                    </div>

                </div>

            <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <!-- PAGINAÇÃO -->
        <?php if ($totalPaginas > 1): ?>
        <div class="flex justify-center mt-10 text-center">
            <div class="flex items-center space-x-2">

                <!-- PRIMEIRA -->
                <a href="?pagina=1<?= $queryStringFiltros ?>&data=<?= $dataSelecionada ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    ««
                </a>

                <!-- ANTERIOR -->
                <a href="?pagina=<?= max(1, $paginaAtual - 1) ?><?= $queryStringFiltros ?>&data=<?= $dataSelecionada ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    «
                </a>

                <?php
                    $inicio = max(1, $paginaAtual - 2);
                    $fim = min($totalPaginas, $inicio + 4);

                    if ($fim - $inicio < 4) {
                        $inicio = max(1, $fim - 4);
                    }

                    for ($i = $inicio; $i <= $fim; $i++):
                ?>

                <!-- NÚMEROS -->
                <a href="?pagina=<?= $i ?><?= $queryStringFiltros ?>&data=<?= $dataSelecionada ?>"
                class="w-12 h-12 flex items-center justify-center rounded
                <?= $i == $paginaAtual 
                        ? 'bg-blue-600 dark:bg-blue-700 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                    <?= $i ?>
                </a>

                <?php endfor; ?>

                <!-- SEGUINTE -->
                <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) ?><?= $queryStringFiltros ?>&data=<?= $dataSelecionada ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    »
                </a>

                <!-- ÚLTIMA -->
                <a href="?pagina=<?= $totalPaginas ?><?= $queryStringFiltros ?>&data=<?= $dataSelecionada ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    »»
                </a>

            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
