<?php
session_start();
include "DBConnection.php";

/* ============================================================
   PAGINAÇÃO
============================================================ */
$registosPorPagina = 9;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

/* ============================================================
   FILTROS
============================================================ */
$pesquisa   = $_GET['pesquisa']   ?? "";
$ordem      = $_GET['ordem']      ?? "";
$sala       = $_GET['sala']       ?? "";

/* ============================================================
   WHERE DINÂMICO (SEM JOIN)
============================================================ */
$where = "WHERE estado = 1";

/* Sala */
if (!empty($sala)) {
    $idSala = (int)$sala;
    $where .= " AND IDsala = $idSala";
}

/* Pesquisa */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);

    // Buscar IDutl dos educadores que correspondem à pesquisa
    $resIDs = mysqli_query($link, "
        SELECT IDutl FROM utilizador 
        WHERE estado = 1 AND (nome LIKE '%$p%' OR email LIKE '%$p%')
    ");

    $listaIDs = [];
    while ($u = mysqli_fetch_assoc($resIDs)) {
        $listaIDs[] = $u['IDutl'];
    }

    if (count($listaIDs) > 0) {
        $ids = implode(",", $listaIDs);
        $where .= " AND IDutl IN ($ids)";
    } else {
        $where .= " AND 1=0"; // força zero resultados
    }
}

/* Ordenação */
$ordemSQL = "ORDER BY IDedu DESC";

if ($ordem === "az")  $ordemSQL = "ORDER BY (SELECT nome FROM utilizador u WHERE u.IDutl = educador.IDutl) ASC";
if ($ordem === "za")  $ordemSQL = "ORDER BY (SELECT nome FROM utilizador u WHERE u.IDutl = educador.IDutl) DESC";
if ($ordem === "old") $ordemSQL = "ORDER BY IDedu ASC";

/* ============================================================
   CONTAGEM TOTAL
============================================================ */
$resTotal = mysqli_query($link, "SELECT IDedu FROM educador $where");
$totalRegistos = mysqli_num_rows($resTotal);

$totalPaginas = ceil($totalRegistos / $registosPorPagina);
if ($totalPaginas < 1) $totalPaginas = 1;

if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;

$offset = ($paginaAtual - 1) * $registosPorPagina;

/* ============================================================
   QUERY PRINCIPAL
============================================================ */
$resEdu = mysqli_query($link, "
    SELECT IDedu, IDutl, IDsala
    FROM educador
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

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas funcionários podem aceder
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit;
}

$nome = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Educadores</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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
            <?php include("sidebar_funcionario.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_funcionario.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar Educadores </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>
            
            <form method="GET" id="filtrosForm"
            class="bg-white p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                md:grid-cols-[2fr_1fr_1fr_auto] gap-4">

            <!-- 🔍 PESQUISA -->
            <div>
                <label class="font-semibold">Pesquisar:</label>
                <input type="text" name="pesquisa" id="pesquisaInput"
                    placeholder="Nome ou email..."
                    value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                    class="border p-2 rounded w-full">
            </div>

            <!-- 🔤 ORDEM -->
            <div>
                <label class="font-semibold">Ordenar por:</label>
                <select name="ordem" class="border p-2 rounded w-full"
                        onchange="document.getElementById('filtrosForm').submit()">
                    <option value="">Mais recentes</option>
                    <option value="az"  <?= ($_GET['ordem'] ?? '')=='az'?'selected':'' ?>>A → Z</option>
                    <option value="za"  <?= ($_GET['ordem'] ?? '')=='za'?'selected':'' ?>>Z → A</option>
                    <option value="old" <?= ($_GET['ordem'] ?? '')=='old'?'selected':'' ?>>Mais antigos</option>
                </select>
            </div>

            <!-- 🧒 SALA -->
            <div>
                <label class="font-semibold">Sala:</label>
                <select name="sala" class="border p-2 rounded w-full"
                        onchange="document.getElementById('filtrosForm').submit()">
                    <option value="">-- Todas --</option>

                    <?php
                    $resSala = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado = 1 ORDER BY nome ASC");
                    while ($s = mysqli_fetch_assoc($resSala)):
                    ?>
                        <option value="<?= $s['IDsala'] ?>" <?= ($_GET['sala'] ?? '') == $s['IDsala'] ? 'selected' : '' ?>>
                            <?= $s['nome'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- ✖️ RESET -->
            <div class="flex mt-6 items-center justify-end">
                <button type="button"
                    onclick="window.location.href='listaredufun.php'"
                    class="text-gray-500 hover:text-red-600 transition text-2xl"
                    title="Limpar filtros">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
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

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                    <?php if ($totalRegistos == 0): ?>

                        <p class="col-span-3 text-center text-gray-600 text-lg py-10 flex flex-col items-center">
                            <span class="text-5xl mb-3">🔍</span>
                            Nenhum educador encontrado com os filtros aplicados.
                        </p>

                    <?php else: ?>
                        <?php
                        while ($edu = mysqli_fetch_assoc($resEdu)) {

                        $IDutl = $edu['IDutl'];
                        $IDsala = $edu['IDsala'];

                        /* Buscar dados do utilizador */
                        $nome = "—";
                        $email = "—";
                        $telefone = "—";

                        $resU = mysqli_query($link, "SELECT nome, email, telefone FROM utilizador WHERE IDutl = $IDutl AND estado = 1");
                        if ($resU && mysqli_num_rows($resU) > 0) {
                            $u = mysqli_fetch_assoc($resU);
                            $nome = $u['nome'];
                            $email = $u['email'];
                            $telefone = $u['telefone'];
                        }

                        /* Buscar nome da sala */
                        $salaNome = "—";
                        if (!empty($IDsala)) {
                            $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = $IDsala");
                            if ($resSala && mysqli_num_rows($resSala) > 0) {
                                $s = mysqli_fetch_assoc($resSala);
                                $salaNome = $s['nome'];
                            }
                        }

                        ?>
                            <!-- GRIDS CARDS -->
                            <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                                <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $nome ?></h2>

                                <div class="text-gray-700 space-y-1 mb-4">
                                    <p><strong>ID:</strong> <?= $IDutl ?></p>
                                    <p><strong>Email:</strong> <?= $email ?></p>
                                    <p><strong>Telefone:</strong> <?= $telefone ?></p>
                                    <p><strong>Sala:</strong> <?= $salaNome ?></p>
                                </div>

                                <div class="flex gap-3">

                                    <!-- Ícone Editar -->
                                    <button onclick="window.location.href='editaredufun.php?id=<?= $IDutl ?>'"
                                        class="text-gray-500 hover:text-yellow-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
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
                        class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">««</a>

                        <!-- ANTERIOR -->
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

                        <!-- NÚMEROS -->
                        <a href="?pagina=<?= $i ?><?= $queryStringFiltros ?>"
                        class="w-12 h-12 flex items-center justify-center rounded 
                        <?= $i == $paginaAtual ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                            <?= $i ?>
                        </a>

                        <?php endfor; ?>

                        <!-- SEGUINTE -->
                        <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) ?><?= $queryStringFiltros ?>"
                        class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»</a>

                        <!-- ÚLTIMA -->
                        <a href="?pagina=<?= $totalPaginas ?><?= $queryStringFiltros ?>"
                        class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»»</a>

                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
