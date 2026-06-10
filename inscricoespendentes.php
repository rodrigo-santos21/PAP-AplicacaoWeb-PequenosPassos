<?php
    session_start();
    include("DBConnection.php");

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
    $tipo       = $_GET['tipo']       ?? "";
    $analise    = $_GET['analise']    ?? "";

    /* ============================================================
    WHERE DINÂMICO
    ============================================================ */
    $where = "WHERE aprovado = 0 AND tipo != 'superadmin'";

    /* 🔍 Pesquisa */
    if (!empty($pesquisa)) {
        $p = mysqli_real_escape_string($link, $pesquisa);
        $where .= " AND (nome LIKE '%$p%' OR email LIKE '%$p%')";
    }

    /* 👤 Tipo */
    if (!empty($tipo)) {
        $t = mysqli_real_escape_string($link, $tipo);
        $where .= " AND tipo = '$t'";
    }

    /* 📝 Estado da análise */
    if ($analise === "livre") {
        $where .= " AND analise_por IS NULL";
    }
    if ($analise === "ocupado") {
        $where .= " AND analise_por IS NOT NULL";
    }

    /* 🔤 Ordenação */
    $ordemSQL = "ORDER BY analise_por IS NOT NULL, IDutl DESC";

    if ($ordem === "az")  $ordemSQL = "ORDER BY nome ASC";
    if ($ordem === "za")  $ordemSQL = "ORDER BY nome DESC";
    if ($ordem === "old") $ordemSQL = "ORDER BY IDutl ASC";

    /* ============================================================
    CONTAGEM TOTAL
    ============================================================ */
    $sqlTotal = "SELECT COUNT(*) AS total FROM utilizador $where";
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
        SELECT * FROM utilizador
        $where
        $ordemSQL
        LIMIT $offset, $registosPorPagina
    ";
    $result = mysqli_query($link, $sql);

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
    if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
        header("Location: index.php?erro=permissao");
        exit();
    }

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Inscrições Pendentes</title>
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

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Inscrições Pendentes </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>

            <form method="GET" id="filtrosForm"
                class="bg-white p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                    md:grid-cols-[2fr_1fr_1fr_1fr_auto] gap-4">

                <!-- 🔍 PESQUISA -->
                <div class="relative">
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

                <!-- 👤 TIPO -->
                <div>
                    <label class="font-semibold">Tipo:</label>
                    <select name="tipo" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>
                        <option value="administrador" <?= ($_GET['tipo'] ?? '')=='administrador'?'selected':'' ?>>Administrador</option>
                        <option value="educador" <?= ($_GET['tipo'] ?? '')=='educador'?'selected':'' ?>>Educador</option>
                        <option value="encarregado" <?= ($_GET['tipo'] ?? '')=='encarregado'?'selected':'' ?>>Encarregado</option>
                        <option value="funcionario" <?= ($_GET['tipo'] ?? '')=='funcionario'?'selected':'' ?>>Funcionário</option>
                    </select>
                </div>

                <!-- 📝 ESTADO -->
                <div>
                    <label class="font-semibold">Estado:</label>
                    <select name="analise" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>
                        <option value="livre"   <?= ($_GET['analise'] ?? '')=='livre'?'selected':'' ?>>Disponível</option>
                        <option value="ocupado" <?= ($_GET['analise'] ?? '')=='ocupado'?'selected':'' ?>>Em análise</option>
                    </select>
                </div>

                <!-- ✖️ RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='inscricoespendentes.php'"
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

                <?php if (mysqli_num_rows($result) === 0): ?>

                    <p class="text-center text-gray-600">Não existem inscrições pendentes.</p>

                <?php else: ?>

                    <!-- GRID DE CARDS -->
                    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                    <?php while ($u = mysqli_fetch_assoc($result)): ?>

                        <div class="bg-blue-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                            <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $u['nome'] ?></h2>

                            <div class="text-gray-700 space-y-1 mb-4">
                                <p><strong>Email:</strong> <?= $u['email'] ?></p>
                                <p><strong>Data Nasc.:</strong> <?= $u['datanascimento'] ?></p>
                                <p><strong>Telefone:</strong> <?= $u['telefone'] ?></p>

                                <p>
                                    <strong>Estado:</strong>
                                    <?php if ($u['analise_por']): ?>
                                        <span class="text-red-600">
                                            Em análise por Funcionário #<?= $u['analise_por'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-green-600">Disponível</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="flex justify-start">
                                <a href="analisa_inscricao.php?id=<?= $u['IDutl'] ?>"
                                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                                    Analisar
                                </a>
                            </div>

                        </div>

                    <?php endwhile; ?>

                    </div>

                <?php endif; ?>

            </div>
            <!-- PAGINAÇÃO -->
            <?php if ($totalPaginas > 1): ?>
            <div class="flex justify-center mt-10 text-center">
                <div class="flex items-center space-x-2">

                    <!-- PRIMEIRA -->
                    <a href="?pagina=1<?= $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">««</a>

                    <!-- ANTERIOR -->
                    <a href="?pagina=<?= max(1, $paginaAtual - 1) . $queryStringFiltros ?>"
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
                    <a href="?pagina=<?= $i . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center rounded 
                    <?= $i == $paginaAtual ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                        <?= $i ?>
                    </a>

                    <?php endfor; ?>

                    <!-- SEGUINTE -->
                    <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»</a>

                    <!-- ÚLTIMA -->
                    <a href="?pagina=<?= $totalPaginas . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»»</a>

                </div>
            </div>
            <?php endif; ?>
                         
        </main>
    </div>

</body>
</html>
