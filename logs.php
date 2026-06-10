<?php
session_start();
include "DBConnection.php";

/* ============================================================
   PAGINAÇÃO
============================================================ */
$registosPorPagina = 20;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

/* ============================================================
   FILTROS
============================================================ */
$pesquisa   = $_GET['pesquisa']   ?? "";
$ordem      = $_GET['ordem']      ?? "";
$utilizador = $_GET['utilizador'] ?? "";
$tipo       = $_GET['tipo']       ?? "";
$dataFiltro = $_GET['dataFiltro'] ?? "";

/* ============================================================
   WHERE DINÂMICO
============================================================ */
$where = "WHERE 1";

/* 🔍 Pesquisa (descrição) */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND descricao LIKE '%$p%'";
}

/* 👤 Utilizador */
if (!empty($utilizador)) {
    $idU = (int)$utilizador;
    $where .= " AND IDutl = $idU";
}

/* 🧑‍💼 Tipo de utilizador */
if (!empty($tipo)) {
    $t = mysqli_real_escape_string($link, $tipo);
    $where .= " AND IDutl IN (SELECT IDutl FROM utilizador WHERE tipo = '$t')";
}

/* 📅 Data (últimos X dias) */
if (!empty($dataFiltro)) {
    $dias = (int)$dataFiltro;
    $where .= " AND datahora >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)";
}

/* 🔤 Ordenação */
$ordemSQL = "ORDER BY datahora DESC"; // padrão

if ($ordem === "old") $ordemSQL = "ORDER BY datahora ASC";
if ($ordem === "az")  $ordemSQL = "ORDER BY descricao ASC";
if ($ordem === "za")  $ordemSQL = "ORDER BY descricao DESC";

/* ============================================================
   CONTAGEM TOTAL
============================================================ */
$sqlTotal = "SELECT COUNT(*) AS total FROM logs $where";
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
    SELECT * FROM logs
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

/* ============================================================
   FOTO DO UTILIZADOR
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
if (!isset($_SESSION['tipo']) ||
   ($_SESSION['tipo'] !== 'administrador' &&
    $_SESSION['tipo'] !== 'funcionario' &&
    $_SESSION['tipo'] !== 'superadmin')) {

    header("Location: index.php?erro=permissao");
    exit();
}

/* ============================================================
   RESET TOTAL DOS LOGS (APENAS SUPERADMIN)
============================================================ */
if (isset($_POST['reset_logs']) && $_SESSION['tipo'] === 'superadmin') {

    mysqli_query($link, "TRUNCATE TABLE logs");

    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Superadministrador fez reset total aos logs', '$fdatahora', $IDutl)
    ");

    header("Location: logs.php?reset=ok");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registo de Logs</title>
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
            <?php
                $tipo = $_SESSION['tipo'];

                if ($tipo === "administrador") {
                    include("sidebar_admin.php");
                } elseif ($tipo === "superadmin") {
                    include("sidebar_superadmin.php");
                } elseif ($tipo === "funcionario") {
                    include("sidebar_funcionario.php");
                }
            ?>
        </div>

        <!-- MENU MOBILE -->
        <?php
            $tipo = $_SESSION['tipo'];

            if ($tipo === "administrador") {
                include("menu_mobile_admin.php");
            } elseif ($tipo === "superadmin") {
                include("menu_mobile_superadmin.php");
            } elseif ($tipo === "funcionario") {
                include("menu_mobile_funcionario.php");
            }
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

		<h1 class="text-3xl font-bold text-gray-800 mb-8">Registo de Logs (Auditoria) </h1>

            <a href="<?=
                $_SESSION['tipo'] === 'superadmin' ? 'superadmin.php' :
                ($_SESSION['tipo'] === 'administrador' ? 'admin.php' : 'funcionario.php')
            ?>"
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
                        placeholder="Descrição do log..."
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

                <!-- 👤 UTILIZADOR -->
                <div>
                    <label class="font-semibold">Utilizador:</label>
                    <select name="utilizador" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>

                        <?php
                        $resU = mysqli_query($link, "SELECT IDutl, nome FROM utilizador WHERE estado = 1 ORDER BY nome ASC");
                        while ($u = mysqli_fetch_assoc($resU)):
                        ?>
                            <option value="<?= $u['IDutl'] ?>"
                                <?= ($_GET['utilizador'] ?? '') == $u['IDutl'] ? 'selected' : '' ?>>
                                <?= $u['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 🧑‍💼 TIPO DE UTILIZADOR -->
                <div>
                    <label class="font-semibold">Tipo:</label>
                    <select name="tipo" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>
                        <option value="superadmin" <?= ($_GET['tipo'] ?? '')=='superadmin'?'selected':'' ?>>Superadmin</option>
                        <option value="administrador" <?= ($_GET['tipo'] ?? '')=='administrador'?'selected':'' ?>>Administrador</option>
                        <option value="funcionario" <?= ($_GET['tipo'] ?? '')=='funcionario'?'selected':'' ?>>Funcionário</option>
                        <option value="encarregado" <?= ($_GET['tipo'] ?? '')=='encarregado'?'selected':'' ?>>Encarregado</option>
                        <option value="educador" <?= ($_GET['tipo'] ?? '')=='educador'?'selected':'' ?>>Educador</option>
                    </select>
                </div>

                <!-- ✖️ RESET -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='logs.php'"
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

                <!-- BOTÃO RESETAR LOGS (ABRE MODAL) -->
                <?php if ($_SESSION['tipo'] === 'superadmin'): ?>
                    <div class="text-center mb-6">
                        <button type="button"
                                onclick="abrirModalReset()"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Resetar Logs
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($totalResultados == 0): ?>
                    <p class="text-center text-gray-600 text-lg py-10">
                        <span class="text-4xl mb-2">🔍</span>
                        Nenhum resultado encontrado para a pesquisa.
                    </p>
                <?php else: ?>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse bg-white shadow rounded-lg">
                            <thead>
                                <tr class="bg-blue-600 text-white">
                                    <th class="p-3 text-left">Descrição</th>
                                    <th class="p-3 text-left">Utilizador</th>
                                    <th class="p-3 text-left">Email</th>
                                    <th class="p-3 text-left">Tipo</th>
                                    <th class="p-3 text-left">Data e Hora</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($log = mysqli_fetch_assoc($result)): ?>

                                    <?php
                                    $IDutl = $log['IDutl'];
                                    $sqlUser = "SELECT nome, email, tipo FROM utilizador WHERE IDutl = $IDutl";
                                    $resUser = mysqli_query($link, $sqlUser);
                                    $user = mysqli_fetch_assoc($resUser);
                                    ?>

                                    <tr class="border-b hover:bg-gray-100">
                                        <td class="p-3"><?= $log['descricao'] ?></td>

                                        <td class="p-3">
                                            <?= $user['nome'] ?? "<i>Utilizador removido</i>" ?>
                                        </td>

                                        <td class="p-3">
                                            <?= $user['email'] ?? "-" ?>
                                        </td>

                                        <td class="p-3">
                                            <?= $user['tipo'] ?? "-" ?>
                                        </td>

                                        <td class="p-3"><?= $log['datahora'] ?></td>
                                    </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
            </div>

            <!-- PAGINAÇÃO -->
            <?php if ($totalPaginas > 1): ?>
            <div class="flex justify-center mt-10 text-center">
                <div class="flex items-center space-x-2">

                    <!-- PRIMEIRA PÁGINA -->
                    <a href="?pagina=1<?= $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">««</a>

                    <!-- VOLTAR UMA -->
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

                    <!-- AVANÇAR UMA -->
                    <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»</a>

                    <!-- ÚLTIMA PÁGINA -->
                    <a href="?pagina=<?= $totalPaginas . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">»»</a>

                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

<!-- MODAL DE CONFIRMAÇÃO DE RESET -->
<div id="modalReset" class="flex fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-[999999]">
    <div class="bg-white p-6 rounded-lg shadow-lg w-[90%] max-w-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Reset</h2>

        <p class="text-gray-700 mb-6">
            Tem a certeza que deseja apagar <strong>TODOS</strong> os logs?  
            Esta ação é <span class="text-red-600 font-semibold">irreversível</span>.
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="fecharModalReset()"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                Cancelar
            </button>

            <form method="POST">
                <input type="hidden" name="reset_logs" value="1">
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Confirmar Reset
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalReset() {
    document.getElementById("modalReset").classList.remove("hidden");
}

function fecharModalReset() {
    document.getElementById("modalReset").classList.add("hidden");
}
</script>

<!-- TOAST -->
<?php if (isset($_GET['reset'])): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("reset", "Todos os logs foram limpos!");
});
</script>
<?php endif; ?>

</body>
</html>
