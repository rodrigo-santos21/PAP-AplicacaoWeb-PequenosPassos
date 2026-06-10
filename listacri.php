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
$sala       = $_GET['sala']       ?? "";
$encarregado= $_GET['encarregado']?? "";

/* ============================================================
   WHERE DINÂMICO
============================================================ */
$where = "WHERE estado = 1";

/* 🔍 Pesquisa */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND nome LIKE '%$p%'";
}

/* 🧒 Sala */
if (!empty($sala)) {
    $idSala = (int)$sala;
    $where .= " AND IDsala = $idSala";
}

/* 👤 Encarregado */
if (!empty($encarregado)) {
    $idEnc = (int)$encarregado;
    $where .= " AND IDutl = $idEnc";
}

/* 🔤 Ordenação */
$ordemSQL = "ORDER BY IDcri DESC";

if ($ordem === "az")  $ordemSQL = "ORDER BY nome ASC";
if ($ordem === "za")  $ordemSQL = "ORDER BY nome DESC";
if ($ordem === "old") $ordemSQL = "ORDER BY IDcri ASC";

/* ============================================================
   CONTAGEM TOTAL
============================================================ */
$sqlTotal = "SELECT COUNT(*) AS total FROM crianca $where";
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
    SELECT * FROM crianca
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

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Verifica se o utilizador é administrador e está autenticado
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

// PROCESSO DE ELIMINAÇÃO VIA AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = $_POST['eliminar_id'];
    
    // Desativar todas as relações com educadores
    mysqli_query($link, "
        UPDATE crianca_educador 
        SET estado = 0 
        WHERE IDcri = $id
    ");

    /* ============================================================
       2) DESATIVAR RELAÇÃO CRIANÇA ↔ ATIVIDADES
       ============================================================ */
    mysqli_query($link, "
        UPDATE crianca_atividade
        SET estado = 0
        WHERE IDcri = $id
    ");

    /* ============================================================
       3) DESATIVAR OCORRÊNCIAS DA CRIANÇA
       ============================================================ */
    mysqli_query($link, "
        UPDATE ocorrencia
        SET estado = 0
        WHERE IDcri = $id
    ");

    // Desativar criança (soft delete)
    $stmt = mysqli_prepare($link, "UPDATE crianca SET estado = 0 WHERE IDcri = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $success = mysqli_stmt_execute($stmt);

    // Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Eliminação da criança (ID $id)', '$fdatahora', '{$_SESSION['id']}')");
    }

    echo $success ? "ok" : "erro";
    exit;
}

$nome = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Crianças</title>
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

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar Crinaças da creche </h1>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">

                <a href="admin.php"
                class="mb-6 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700">
                    ← Voltar
                </a>

                <a href="adicionarcri.php"
                class="mb-6 px-4 py-2 bg-green-600 text-white rounded-md font-semibold hover:bg-green-700">
                    + Adicionar Criança
                </a>

            </div>

            <form method="GET" id="filtrosForm"
                class="bg-white p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                    md:grid-cols-[2fr_1fr_1fr_1fr_auto] gap-4">

                <!-- 🔍 PESQUISA -->
                <div class="relative">
                    <label class="font-semibold">Pesquisar:</label>
                    <input type="text" name="pesquisa" id="pesquisaInput"
                        placeholder="Nome da criança..."
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
                        <option value="old" <?= ($_GET['ordem'] ?? '')=='old'?'selected':'' ?>>Mais antigas</option>
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

                <!-- 👤 ENCARREGADO -->
                <div>
                    <label class="font-semibold">Encarregado:</label>
                    <select name="encarregado" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>

                        <?php
                        $resEnc = mysqli_query($link, "
                            SELECT IDutl, nome FROM utilizador 
                            WHERE tipo='encarregado' AND estado=1 ORDER BY nome ASC
                        ");
                        while ($e = mysqli_fetch_assoc($resEnc)):
                        ?>
                            <option value="<?= $e['IDutl'] ?>" <?= ($_GET['encarregado'] ?? '') == $e['IDutl'] ? 'selected' : '' ?>>
                                <?= $e['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- ✖️ RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='listacri.php'"
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
                    
                    <?php if ($totalResultados == 0): ?>
                        <p class="col-span-3 text-center text-gray-600 text-lg py-10">
                            <span class="text-4xl mb-2">🔍</span>
                            Nenhuma criança encontrada.
                        </p>
                    <?php else: ?>

                        <?php

                        while ($cri = mysqli_fetch_assoc($result)) {

                            // Sala
                            $salaNome = "—";
                            if (!empty($cri['IDsala'])) {
                                $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = {$cri['IDsala']}");
                                if ($resSala && mysqli_num_rows($resSala) > 0) {
                                    $salaNome = mysqli_fetch_assoc($resSala)['nome'];
                                }
                            }

                            // Encarregado
                            $encNome = "—";
                            if (!empty($cri['IDutl'])) {
                                $resEnc = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$cri['IDutl']}");
                                if ($resEnc && mysqli_num_rows($resEnc) > 0) {
                                    $encNome = mysqli_fetch_assoc($resEnc)['nome'];
                                }
                            }

                            // Sexo
                            $sexo = $cri['sexo'] === "M" ? "Masculino" :
                                    ($cri['sexo'] === "F" ? "Feminino" : "Indefinido");

                            // Observações
                            $obs = !empty($cri['observacoes']) ? $cri['observacoes'] : "—";
                        ?>

                            <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                                <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $cri['nome'] ?></h2>

                                <div class="text-gray-700 space-y-1 mb-4">
                                    <p><strong>ID:</strong> <?= $cri['IDcri'] ?></p>
                                    <p><strong>Data Nasc.:</strong> <?= $cri['datanascimento'] ?></p>
                                    <p><strong>Sexo:</strong> <?= $sexo ?></p>
                                    <p><strong>Sala:</strong> <?= $salaNome ?></p>
                                    <p><strong>Encarregado:</strong> <?= $encNome ?></p>
                                    <p><strong>Observações:</strong> <?= $obs ?></p>
                                </div>

                                <div class="flex gap-3">

                                    <!-- Ícone Editar -->
                                    <button onclick="window.location.href='editarcri.php?id=<?= $cri['IDcri'] ?>'"
                                        class="text-gray-500 hover:text-yellow-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                        </svg>
                                    </button>

                                    <!-- Ícone Eliminar -->
                                    <button onclick="eliminarCrianca(<?= $cri['IDcri'] ?>)"
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

<!-- Modal Eliminar Criança -->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Eliminação</h2>

        <p class="text-gray-700 mb-6">
            Tens a certeza que desejas eliminar esta criança?
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

<!-- SCRIPT para eliminar criança -->
<script>
    let idCriancaParaEliminar = null;

    function eliminarCrianca(id) {
        idCriancaParaEliminar = id;
        const modal = document.getElementById("modalEliminar");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function fecharModal() {
        const modal = document.getElementById("modalEliminar");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        idCriancaParaEliminar = null;
    }

    document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

        if (idCriancaParaEliminar === null) return;

        fetch("listacri.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "eliminar_id=" + idCriancaParaEliminar
        })
        .then(r => r.text())
        .then(res => {

            res = res.trim();

            if (res === "ok") {
                fecharModal();
                mostrarMensagem("Criança eliminada com sucesso.", "green");
                setTimeout(() => location.reload(), 1200);
                return;
            }

            mostrarMensagem("Erro ao eliminar criança.", "red");
        });
    });
</script>
</body>
</html>
