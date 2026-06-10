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
$capacidade = $_GET['capacidade'] ?? ""; // filtro adicional específico das salas

/* ============================================================
   WHERE DINÂMICO
============================================================ */
$where = "WHERE estado = 1";

/* 🔍 Pesquisa (nome da sala) */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND nome LIKE '%$p%'";
}

/* 🧱 Filtro por capacidade */
if (!empty($capacidade)) {
    $cap = (int)$capacidade;
    $where .= " AND capacidade >= $cap";
}

/* 🔤 Ordenação */
$ordemSQL = "ORDER BY IDsala DESC"; // padrão

if ($ordem === "old") $ordemSQL = "ORDER BY IDsala ASC";
if ($ordem === "az")  $ordemSQL = "ORDER BY nome ASC";
if ($ordem === "za")  $ordemSQL = "ORDER BY nome DESC";

/* ============================================================
   CONTAGEM TOTAL
============================================================ */
$sqlTotal = "SELECT COUNT(*) AS total FROM sala $where";
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
    SELECT * FROM sala
    $where
    $ordemSQL
    LIMIT $offset, $registosPorPagina
";

$res = mysqli_query($link, $sql);
$totalResultados = mysqli_num_rows($res);

/* ============================================================
   PRESERVAR FILTROS NA PAGINAÇÃO
============================================================ */
$queryStringFiltros = "";

foreach ($_GET as $key => $value) {
    if ($key !== "pagina") {
        $queryStringFiltros .= "&$key=" . urlencode($value);
    }
}

/* ============================================================
   FOTO DO SUPERADMIN
============================================================ */
$IDutl = $_SESSION['id'];
$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;
$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

/* ============================================================
   PERMISSÕES
============================================================ */
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit();
}

/* ============================================================
   PROCESSAR ELIMINAÇÃO
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = intval($_POST['eliminar_id']);

    // Dependências
    $cri = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS total FROM crianca WHERE IDsala = $id AND estado = 1"
    ))['total'];

    $edu = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS total FROM educador WHERE IDsala = $id AND estado = 1"
    ))['total'];

    if ($cri > 0 || $edu > 0) {
        echo "erro_dependencias";
        exit;
    }

    // Soft delete
    mysqli_query($link, "UPDATE sala SET estado = 0 WHERE IDsala = $id");

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Superadmin eliminou sala (ID $id)', '$fdatahora', '{$_SESSION['id']}')
    ");

    echo "ok";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Salas — Superadmin</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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

<body class="bg-gray-100 min-h-screen">
    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal" 
        class="hidden fixed top-5 right-5 bg-white shadow-lg border-l-4 rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_superadmin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_superadmin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

        <h1 class="text-3xl font-bold text-gray-800 mb-8">Salas da Creche</h1>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">

            <a href="superadmin.php"
            class="mb-6 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700">
                ← Voltar
            </a>

            <a href="adicionarsalasuper.php"
            class="mb-6 px-4 py-2 bg-green-600 text-white rounded-md font-semibold hover:bg-green-700">
                + Adicionar Sala
            </a>

        </div>

        <form method="GET" id="filtrosForm"
            class="bg-white p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 md:grid-cols-[2fr_1fr_1fr_1fr_auto] gap-4">

            <!-- 🔍 PESQUISA -->
            <div class="md:col-span-2 relative">
                <label class="font-semibold">Pesquisar:</label>
                <input type="text" name="pesquisa" id="pesquisaInput"
                    placeholder="Nome da sala..."
                    value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                    class="border p-2 rounded w-full">
            </div>

            <!-- 🔤 ORDEM -->
            <div>
                <label class="font-semibold">Ordenar por:</label>
                <select name="ordem" class="border p-2 rounded w-full"
                        onchange="document.getElementById('filtrosForm').submit()">
                    <option value="">Mais recentes</option>
                    <option value="old" <?= ($_GET['ordem'] ?? '')=='old'?'selected':'' ?>>Mais antigas</option>
                    <option value="az"  <?= ($_GET['ordem'] ?? '')=='az'?'selected':'' ?>>A → Z</option>
                    <option value="za"  <?= ($_GET['ordem'] ?? '')=='za'?'selected':'' ?>>Z → A</option>
                </select>
            </div>

            <!-- 🧱 CAPACIDADE (filtro adicional específico das salas) -->
            <div>
                <label class="font-semibold">Capacidade:</label>
                <select name="capacidade" class="border p-2 rounded w-full"
                        onchange="document.getElementById('filtrosForm').submit()">
                    <option value="">-- Todas --</option>
                    <option value="10" <?= ($_GET['capacidade'] ?? '')=='10'?'selected':'' ?>>10+</option>
                    <option value="15" <?= ($_GET['capacidade'] ?? '')=='15'?'selected':'' ?>>15+</option>
                    <option value="20" <?= ($_GET['capacidade'] ?? '')=='20'?'selected':'' ?>>20+</option>
                    <option value="25" <?= ($_GET['capacidade'] ?? '')=='25'?'selected':'' ?>>25+</option>
                </select>
            </div>

            <!-- ✖️ RESET -->
            <div class="flex mt-6 items-center justify-end">
                <button type="button"
                    onclick="window.location.href='listarsalasuper.php'"
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
                        Nenhum resultado encontrado para a pesquisa.
                    </p>
                <?php else: ?>

                    <?php while ($s = mysqli_fetch_assoc($res)) {

                    $IDsala = $s['IDsala'];

                    // Contar crianças
                    $cri = mysqli_fetch_assoc(mysqli_query($link,
                        "SELECT COUNT(*) AS total FROM crianca WHERE IDsala = $IDsala AND estado = 1"
                    ))['total'];

                    // Contar educadores
                    $edu = mysqli_fetch_assoc(mysqli_query($link,
                        "SELECT COUNT(*) AS total FROM educador WHERE IDsala = $IDsala AND estado = 1"
                    ))['total'];
                ?>

                    <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $s['nome'] ?></h2>

                        <p class="text-gray-700"><strong>ID:</strong> <?= $IDsala ?></p>
                        <p class="text-gray-700"><strong>Capacidade:</strong> <?= $s['capacidade'] ?></p>
                        <p class="text-gray-700"><strong>Crianças:</strong> <?= $cri ?></p>
                        <p class="text-gray-700"><strong>Educadores:</strong> <?= $edu ?></p>

                        <div class="flex gap-3 justify-end mt-4">

                            <!-- EDITAR -->
                            <button onclick="window.location.href='editarsalasuper.php?id=<?= $IDsala ?>'"
                                class="text-gray-500 hover:text-yellow-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                </svg>
                            </button>

                            <!-- ELIMINAR -->
                            <button onclick="eliminarSala(<?= $IDsala ?>)"
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
            // Cálculo das páginas visíveis
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

<!-- MODAL DE CONFIRMAÇÃO -->
<div id="modalEliminar" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Eliminação</h2>

        <p class="text-gray-700 mb-6">
            Tens a certeza que desejas eliminar esta sala?
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

<script>
    let idParaEliminar = null;

    function eliminarSala(id) {
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

    document.getElementById("btnConfirmarEliminar").addEventListener("click", function () {

        if (idParaEliminar === null) return;

        fetch("listarsalasuper.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "eliminar_id=" + idParaEliminar
        })
        .then(r => r.text())
        .then(res => {
            res = res.trim();

            if (res === "ok") {
                fecharModal();
                mostrarMensagem("eliminar", "Sala eliminada com sucesso!");
                setTimeout(() => location.reload(), 1200);
                return;
            }

            if (res === "erro_dependencias") {
                fecharModal();
                mostrarMensagem("reset", "Não é possível eliminar: existem crianças ou educadores associados.");
                return;
            }

            mostrarMensagem("reset", "Erro ao eliminar sala.");
        });
    });
</script>

<!-- TOAST: sala adicionada -->
<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'adicionado'): ?>
<script>
window.addEventListener("load", () => {
    // tipo = "adicionar" (para o ícone/cor), texto = mensagem
    mostrarMensagem("adicionar", "Sala adicionada com sucesso!");
});
</script>
<?php endif; ?>

<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'editado'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("editar", "Sala atualizada com sucesso!");
});
</script>
<?php endif; ?>

</body>
</html>
