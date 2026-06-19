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

// Verifica se o utilizador é funcionarios e está autenticado
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
    <title>Listar Crianças</title>
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
            <?php include("sidebar_funcionario.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_funcionario.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Listar Crianças
            </h1>

            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                   rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>

            <!-- FILTROS -->
            <form method="GET" id="filtrosForm"
                class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                       md:grid-cols-[2fr_1fr_1fr_1fr_auto] gap-4">

                <!-- PESQUISA -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Pesquisar:</label>
                    <input type="text" name="pesquisa" id="pesquisaInput"
                        placeholder="Nome da criança..."
                        value="<?= htmlspecialchars($_GET['pesquisa'] ?? '') ?>"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-900 dark:text-gray-100">
                </div>

                <!-- ORDEM -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Ordenar por:</label>
                    <select name="ordem"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-900 dark:text-gray-100"
                        onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">Mais recentes</option>
                        <option value="az"  <?= ($_GET['ordem'] ?? '')=='az'?'selected':'' ?>>A → Z</option>
                        <option value="za"  <?= ($_GET['ordem'] ?? '')=='za'?'selected':'' ?>>Z → A</option>
                        <option value="old" <?= ($_GET['ordem'] ?? '')=='old'?'selected':'' ?>>Mais antigas</option>
                    </select>
                </div>

                <!-- SALA -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Sala:</label>
                    <select name="sala"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-900 dark:text-gray-100"
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

                <!-- ENCARREGADO -->
                <div>
                    <label class="font-semibold dark:text-gray-200">Encarregado:</label>
                    <select name="encarregado"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full bg-white dark:bg-gray-900 dark:text-gray-100"
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

                <!-- RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='listarcrifun.php'"
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

            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                <?php

                while ($cri = mysqli_fetch_assoc($result)) {

                    // Sala
                    $salaNome = "—";
                    if (!empty($cri['IDsala'])) {
                        $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = {$cri['IDsala']}");
                        if ($resSala && mysqli_num_rows($resSala) > 0) {
                            $sala = mysqli_fetch_assoc($resSala);
                            $salaNome = $sala['nome'];
                        }
                    }

                    // Encarregado
                    $encNome = "—";
                    if (!empty($cri['IDutl'])) {
                        $resEnc = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$cri['IDutl']}");
                        if ($resEnc && mysqli_num_rows($resEnc) > 0) {
                            $enc = mysqli_fetch_assoc($resEnc);
                            $encNome = $enc['nome'];
                        }
                    }

                    // Sexo
                    $sexo = ($cri['sexo'] === "M") ? "Masculino" :
                            (($cri['sexo'] === "F") ? "Feminino" : "Indefinido");

                    // Observações
                    $obs = !empty($cri['observacoes']) ? $cri['observacoes'] : "—";
                ?>

                    <div class="bg-green-50 dark:bg-gray-700 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                            <?= $cri['nome'] ?>
                        </h2>

                        <div class="text-gray-700 dark:text-gray-200 space-y-1 mb-4">
                            <p><strong>ID:</strong> <?= $cri['IDcri'] ?></p>
                            <p><strong>Data Nasc.:</strong> <?= $cri['datanascimento'] ?></p>
                            <p><strong>Sexo:</strong> <?= $sexo ?></p>
                            <p><strong>Sala:</strong> <?= $salaNome ?></p>
                            <p><strong>Encarregado:</strong> <?= $encNome ?></p>
                            <p><strong>Observações:</strong> <?= $obs ?></p>
                        </div>

                        <div class="flex gap-3">

                            <!-- Ícone Editar -->
                            <button onclick="window.location.href='editarcrifun.php?id=<?= $cri['IDcri'] ?>'"
                                class="text-gray-500 dark:text-gray-300 hover:text-yellow-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                </svg>
                            </button>

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

<!-- TOAST: criança editada -->
<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'editado'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("editar", "Criança atualizada com sucesso!");
});
</script>
<?php endif; ?>

<!-- TOAST: erro ao atualizar -->
<?php if (isset($_GET['erro']) && $_GET['erro'] === 'atualizar'): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("reset", "Erro ao atualizar a criança.");
});
</script>
<?php endif; ?>

</body>
</html>
