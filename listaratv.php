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
$educador   = $_GET['educador']   ?? "";
$criador    = $_GET['criador']    ?? "";
$dataFiltro = $_GET['dataFiltro'] ?? "";
$ordem      = $_GET['ordem']      ?? "";

/* ============================================================
   WHERE DINÂMICO
============================================================ */
$where = "WHERE estado = 1";

/* 🔍 Pesquisa (título + descrição) */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND (titulo LIKE '%$p%' OR descricao LIKE '%$p%')";
}

/* 👤 Educador responsável */
if (!empty($educador)) {
    $idEdu = (int)$educador;
    $where .= " AND IDedu = $idEdu";
}

/* 🧑‍💼 Criador */
if (!empty($criador)) {
    $idCri = (int)$criador;
    $where .= " AND criadopor = $idCri";
}

/* 📅 Data (últimos X dias) */
if (!empty($dataFiltro)) {
    $dias = (int)$dataFiltro;
    $where .= " AND datahora >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)";
}

/* 🔤 Ordenação */
$ordemSQL = "ORDER BY IDatv DESC"; // padrão

if ($ordem === "old") $ordemSQL = "ORDER BY IDatv ASC";
if ($ordem === "az")  $ordemSQL = "ORDER BY titulo ASC";
if ($ordem === "za")  $ordemSQL = "ORDER BY titulo DESC";

/* ============================================================
   CONTAGEM TOTAL COM FILTROS
============================================================ */
$sqlTotal = "SELECT COUNT(*) AS total FROM atividade $where";
$resultTotal = mysqli_query($link, $sqlTotal);
$totalRegistos = mysqli_fetch_assoc($resultTotal)['total'];

$totalPaginas = ceil($totalRegistos / $registosPorPagina);
if ($totalPaginas < 1) $totalPaginas = 1;

if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;

$offset = ($paginaAtual - 1) * $registosPorPagina;

/* ============================================================
   QUERY PRINCIPAL COM FILTROS
============================================================ */
$sql = "
    SELECT * FROM atividade
    $where
    $ordemSQL
    LIMIT $offset, $registosPorPagina
";

$resAtv = mysqli_query($link, $sql);
$totalResultados = mysqli_num_rows($resAtv);

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

// Verifica se o utilizador é administrador
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

// PROCESSO DE ELIMINAÇÃO VIA AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = intval($_POST['eliminar_id']);

    // 1) Soft delete da atividade
    $stmt = mysqli_prepare($link, "UPDATE atividade SET estado = 0 WHERE IDatv = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 2) Soft delete das relações com crianças
    if ($success) {
        mysqli_query($link, "
            UPDATE crianca_atividade 
            SET estado = 0 
            WHERE IDatv = $id
        ");
    }

    // 3) Log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Admin eliminou atividade (ID $id)', '$fdatahora', '{$_SESSION['id']}')
        ");
    }

    echo $success ? "ok" : "erro";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Atividades</title>
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

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_admin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_admin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar Atividades da creche </h1>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">

                <a href="admin.php"
                class="mb-6 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700">
                    ← Voltar
                </a>

                <a href="adicionaratv.php"
                class="mb-6 px-4 py-2 bg-green-600 text-white rounded-md font-semibold hover:bg-green-700">
                    + Adicionar Atividade
                </a>

            </div>
            
            <form method="GET" id="filtrosForm"
                class="bg-white p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                    md:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] gap-4">

                <!-- 🔍 PESQUISA -->
                <div class="relative">
                    <label class="font-semibold">Pesquisar:</label>
                    <input type="text" name="pesquisa" id="pesquisaInput"
                        placeholder="Título ou descrição..."
                        value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                        class="border p-2 rounded w-full">
                </div>

                <!-- 🔤 ORDEM -->
                <div>
                    <label class="font-semibold">Ordenar por:</label>
                    <select name="ordem" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">Mais recentes</option>
                        <option value="old" <?= ($_GET['ordem'] ?? '')=='old'?'selected':'' ?>>Mais antigos</option>
                        <option value="az"  <?= ($_GET['ordem'] ?? '')=='az'?'selected':'' ?>>A → Z</option>
                        <option value="za"  <?= ($_GET['ordem'] ?? '')=='za'?'selected':'' ?>>Z → A</option>
                    </select>
                </div>

                <!-- 👤 EDUCATOR RESPONSÁVEL -->
                <div>
                    <label class="font-semibold">Educador:</label>
                    <select name="educador" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>

                        <?php
                        $resEdu = mysqli_query($link, "SELECT IDedu, IDutl FROM educador WHERE estado = 1 ORDER BY IDedu ASC");
                        while ($ed = mysqli_fetch_assoc($resEdu)):
                            $idEdu = $ed['IDedu'];
                            $resNome = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$ed['IDutl']} AND estado = 1");
                            $nomeEdu = mysqli_num_rows($resNome) ? mysqli_fetch_assoc($resNome)['nome'] : "Educador #$idEdu";
                        ?>
                            <option value="<?= $idEdu ?>" <?= ($_GET['educador'] ?? '') == $idEdu ? 'selected' : '' ?>>
                                <?= $nomeEdu ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 🧑‍💼 CRIADOR -->
                <div>
                    <label class="font-semibold">Criado por:</label>
                    <select name="criador" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>

                        <?php
                        $resCri = mysqli_query($link, "
                            SELECT DISTINCT criadopor 
                            FROM atividade 
                            WHERE estado = 1 AND criadopor IS NOT NULL
                        ");

                        while ($cr = mysqli_fetch_assoc($resCri)):
                            $idCriador = $cr['criadopor'];
                            $resNome = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $idCriador AND estado = 1");
                            $nomeCriador = mysqli_num_rows($resNome) ? mysqli_fetch_assoc($resNome)['nome'] : "Utilizador #$idCriador";
                        ?>
                            <option value="<?= $idCriador ?>" <?= ($_GET['criador'] ?? '') == $idCriador ? 'selected' : '' ?>>
                                <?= $nomeCriador ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 📅 DATA -->
                <div>
                    <label class="font-semibold">Data:</label>
                    <select name="dataFiltro" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todas --</option>
                        <option value="1"  <?= ($_GET['dataFiltro'] ?? '')=='1'?'selected':'' ?>>Últimas 24h</option>
                        <option value="7"  <?= ($_GET['dataFiltro'] ?? '')=='7'?'selected':'' ?>>Últimos 7 dias</option>
                        <option value="30" <?= ($_GET['dataFiltro'] ?? '')=='30'?'selected':'' ?>>Últimos 30 dias</option>
                    </select>
                </div>

                <!-- ✖️ RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='listaratv.php'"
                        class="text-gray-500 hover:text-red-600 transition text-2xl"
                        title="Limpar filtros">
                        <!-- SVG OBRIGATÓRIO -->
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

                    <?php if ($totalResultados == 0): ?>
                        <p class="col-span-3 text-center text-gray-600 text-lg py-10">
                            <span class="text-4xl mb-2">🔍</span>
                            Nenhum resultado encontrado para a pesquisa.
                        </p>
                    <?php else: ?>

                    <?php

                        while ($a = mysqli_fetch_assoc($resAtv)) {

                            $IDatv = $a['IDatv'];

                            // Criador
                            $nomeCriador = "—";
                            if (!empty($a['criadopor'])) {
                                $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$a['criadopor']}");
                                if ($resU && mysqli_num_rows($resU) > 0) {
                                    $nomeCriador = mysqli_fetch_assoc($resU)['nome'];
                                }
                            }

                            // Educador responsável
                            $nomeEdu = "—";
                            if (!empty($a['IDedu'])) {
                                $resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDedu = {$a['IDedu']}");
                                if ($resEdu && mysqli_num_rows($resEdu) > 0) {
                                    $edu = mysqli_fetch_assoc($resEdu);
                                    $resU2 = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$edu['IDutl']}");
                                    if ($resU2 && mysqli_num_rows($resU2) > 0) {
                                        $nomeEdu = mysqli_fetch_assoc($resU2)['nome'];
                                    }
                                }
                            }

                            // Contagens
                            $totalCri = mysqli_fetch_assoc(mysqli_query($link, "
                                SELECT COUNT(*) AS total 
                                FROM crianca_atividade 
                                WHERE IDatv = $IDatv AND estado = 1
                            "))['total'];

                            $totalReal = mysqli_fetch_assoc(mysqli_query($link, "
                                SELECT COUNT(*) AS total 
                                FROM crianca_atividade 
                                WHERE IDatv = $IDatv AND estado = 1 AND realizada = 1
                            "))['total'];

                            // Descrição curta
                            $desc = strlen($a['descricao']) > 40
                                    ? substr($a['descricao'], 0, 40) . "..."
                                    : $a['descricao'];
                        ?>

                        <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                            <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $a['titulo'] ?></h2>

                            <div class="text-gray-700 space-y-1 mb-4">
                                <p><strong>Data/Hora:</strong> <?= $a['datahora'] ?></p>
                                <p><strong>Criado por:</strong> <?= $nomeCriador ?></p>
                                <p><strong>Responsável:</strong> <?= $nomeEdu ?></p>
                                <p><strong>Crianças:</strong> <?= $totalCri ?></p>
                                <p><strong>Realizadas:</strong> <?= $totalReal ?></p>
                                <p><strong>Descrição:</strong> <?= $desc ?></p>
                            </div>

                            <div class="flex gap-3">

                                <!-- Ícone Editar -->
                                <button onclick="window.location.href='editaratv.php?id=<?= $IDatv ?>'"
                                    class="text-gray-500 hover:text-yellow-500 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                    </svg>
                                </button>

                                <!-- Ícone Eliminar -->
                                <button onclick="eliminarAtividade(<?= $IDatv ?>)"
                                    class="text-gray-500 hover:text-red-600 transition">
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

<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Eliminação</h2>

        <p class="text-gray-700 mb-6">
            Tens a certeza que desejas eliminar esta atividade?
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="fecharModal()"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                Cancelar
            </button>

            <button id="btnConfirmarEliminar"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Eliminar
            </button>
        </div>
    </div>
</div>

<!-- SCRIPT para eliminar atividade -->
<script>
    let idParaEliminar = null;

    function eliminarAtividade(id) {
        idParaEliminar = id;
        const modal = document.getElementById("modalEliminar");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function fecharModal() {
        const modal = document.getElementById("modalEliminar");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        idParaEliminar = null;
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

            if (idParaEliminar === null) return;

            fetch("listaratv.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "eliminar_id=" + idParaEliminar
            })
            .then(r => r.text())
            .then(res => {
                res = res.trim();

                if (res === "ok") {
                    fecharModal();
                    mostrarMensagem("Atividade eliminada com sucesso.", "green");
                    setTimeout(() => location.reload(), 1200);
                } else {
                    mostrarMensagem("Erro ao eliminar atividade.", "red");
                }
            });
        });
        function mostrarMensagem(texto, cor) {
            const div = document.createElement("div");
            div.className = `fixed top-5 right-5 px-4 py-2 rounded shadow-lg text-white bg-${cor}-600`;
            div.textContent = texto;
            document.body.appendChild(div);

            setTimeout(() => div.remove(), 2000);
        }
    });

</script>
</body>
</html>
