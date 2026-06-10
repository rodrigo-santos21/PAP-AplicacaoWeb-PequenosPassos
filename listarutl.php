<?php
session_start();
include "DBConnection.php";

$IDutl = $_SESSION['id'];

// PAGINAÇÃO
$registosPorPagina = 1;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

/* ============================================================
   FILTROS
============================================================ */
$pesquisa     = $_GET['pesquisa']     ?? "";
$ordem        = $_GET['ordem']        ?? "";
$tipo         = $_GET['tipo']         ?? "";
$confirmado   = $_GET['confirmado']   ?? "";
$aprovado     = $_GET['aprovado']     ?? "";

/* ============================================================
   WHERE DINÂMICO
============================================================ */
$where = "WHERE estado = 1 AND tipo != 'superadmin' AND IDutl != $IDutl";

/* 🔍 Pesquisa nome/email */
if (!empty($pesquisa)) {
    $p = mysqli_real_escape_string($link, $pesquisa);
    $where .= " AND (nome LIKE '%$p%' OR email LIKE '%$p%')";
}

/* 👤 Tipo */
if (!empty($tipo)) {
    $t = mysqli_real_escape_string($link, $tipo);
    $where .= " AND tipo = '$t'";
}

/* ✔ Confirmado */
if ($confirmado !== "") {
    $c = (int)$confirmado;
    $where .= " AND confirmado = $c";
}

/* 📝 Aprovado */
if ($aprovado !== "") {
    $a = (int)$aprovado;
    $where .= " AND aprovado = $a";
}

/* 🔤 Ordenação */
$ordemSQL = "ORDER BY IDutl DESC";

if ($ordem == "az")  $ordemSQL = "ORDER BY nome ASC";
if ($ordem == "za")  $ordemSQL = "ORDER BY nome DESC";
if ($ordem == "old") $ordemSQL = "ORDER BY IDutl ASC";


/* ============================================================
   CONTAGEM TOTAL COM FILTROS
============================================================ */
$sqlTotal = "SELECT COUNT(*) AS total FROM utilizador $where";
$resultTotal = mysqli_query($link, $sqlTotal);
$totalRegistos = mysqli_fetch_assoc($resultTotal)['total'];

$totalPaginas = ceil($totalRegistos / $registosPorPagina);

if ($totalPaginas < 1) {
    $totalPaginas = 1;
}

if ($paginaAtual < 1) $paginaAtual = 1;
if ($paginaAtual > $totalPaginas) $paginaAtual = $totalPaginas;


$offset = ($paginaAtual - 1) * $registosPorPagina;

/* ============================================================
   QUERY PRINCIPAL COM FILTROS
============================================================ */
$sql = "SELECT * FROM utilizador
        $where
        $ordemSQL
        LIMIT $offset, $registosPorPagina";

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

// Verifica se o utilizador é administrador
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

/* ============================================================
   PROCESSO DE ELIMINAÇÃO VIA AJAX (ANTES DE QUALQUER HTML)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = intval($_POST['eliminar_id']);
    $success = true;

    // Buscar tipo do utilizador
    $resTipo = mysqli_query($link, "SELECT tipo FROM utilizador WHERE IDutl = $id");
    $tipo = mysqli_fetch_assoc($resTipo)['tipo'];

    // 1) Desativar utilizador
    $success &= mysqli_query($link, "UPDATE utilizador SET estado = 0 WHERE IDutl = $id");

    // 2) Desativar participações em reuniões
    $success &= mysqli_query($link, "UPDATE reuniao_participante SET estado = 0 WHERE IDutl = $id");

    /* ============================================================
       CASO SEJA ENCARREGADO → DESASSOCIAR CRIANÇAS
       ============================================================ */
    if ($tipo === "encarregado") {
        $success &= mysqli_query($link, "UPDATE crianca SET IDutl = NULL WHERE IDutl = $id");
    }

    /* ============================================================
       CASO SEJA EDUCATOR → DESATIVAR TODAS AS RELAÇÕES DEPENDENTES
       ============================================================ */
    if ($tipo === "educador") {

        // Buscar IDedu
        $resEdu = mysqli_query($link, "SELECT IDedu FROM educador WHERE IDutl = $id AND estado = 1");
        if ($rowEdu = mysqli_fetch_assoc($resEdu)) {
            $IDedu = $rowEdu['IDedu'];

            // Desativar educador
            $success &= mysqli_query($link, "UPDATE educador SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar relações criança-educador
            $success &= mysqli_query($link, "UPDATE crianca_educador SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar atividades criadas pelo educador
            $success &= mysqli_query($link, "UPDATE atividade SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar ocorrências criadas pelo educador
            $success &= mysqli_query($link, "UPDATE ocorrencia SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar relações criança-atividade associadas às atividades do educador
            $resAtv = mysqli_query($link, "SELECT IDatv FROM atividade WHERE IDedu = $IDedu");
            while ($atv = mysqli_fetch_assoc($resAtv)) {
                $IDatv = $atv['IDatv'];
                $success &= mysqli_query($link, "UPDATE crianca_atividade SET estado = 0 WHERE IDatv = $IDatv");
            }
        }
    }

    /* ============================================================
       CASO SEJA ADMINISTRADOR → DESATIVAR REUNIÕES CRIADAS POR ELE
       ============================================================ */
    if ($tipo === "administrador") {
        $resReu = mysqli_query($link, "SELECT IDreu FROM reuniao WHERE criadopor = $id");
        while ($reu = mysqli_fetch_assoc($resReu)) {
            $IDreu = $reu['IDreu'];
            $success &= mysqli_query($link, "UPDATE reuniao SET estado = 0 WHERE IDreu = $IDreu");
            $success &= mysqli_query($link, "UPDATE reuniao_participante SET estado = 0 WHERE IDreu = $IDreu");
        }
    }

    // 5) Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        $idadmin = $_SESSION['id'];

        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Eliminação de utilizador (ID $id)', '$fdatahora', '$idadmin')
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
    <title>Listar Utilizadores</title>
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

            <h1 class="text-3xl font-bold text-gray-800 mb-8">Utilizadores da creche</h1>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">

                <a href="admin.php"
                class="mb-6 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700">
                    ← Voltar
                </a>

                <a href="adicionarutl.php"
                class="mb-6 px-4 py-2 bg-green-600 text-white rounded-md font-semibold hover:bg-green-700">
                    + Adicionar Utilizador
                </a>

            </div>

            <form method="GET" id="filtrosForm"
                class="bg-white p-4 rounded-lg shadow-lg mb-6 grid grid-cols-1 
                    md:grid-cols-[2fr_1fr_1fr_1fr_1fr_1fr_auto] gap-4">

                <!-- 🔍 PESQUISA -->
                <div class="md:col-span-2 relative">
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
                    <label class="font-semibold">Tipo de utilizador:</label>
                    <select name="tipo" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>
                        <option value="administrador" <?= ($_GET['tipo'] ?? '')=='administrador'?'selected':'' ?>>Administrador</option>
                        <option value="educador" <?= ($_GET['tipo'] ?? '')=='educador'?'selected':'' ?>>Educador</option>
                        <option value="encarregado" <?= ($_GET['tipo'] ?? '')=='encarregado'?'selected':'' ?>>Encarregado</option>
                        <option value="funcionario" <?= ($_GET['tipo'] ?? '')=='funcionario'?'selected':'' ?>>Funcionário</option>
                    </select>
                </div>

                <!-- ✔ CONFIRMADO -->
                <div>
                    <label class="font-semibold">Confirmado:</label>
                    <select name="confirmado" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>
                        <option value="1" <?= ($_GET['confirmado'] ?? '')=='1'?'selected':'' ?>>Confirmado</option>
                        <option value="0" <?= ($_GET['confirmado'] ?? '')=='0'?'selected':'' ?>>Não confirmado</option>
                    </select>
                </div>

                <!-- 📝 APROVADO -->
                <div>
                    <label class="font-semibold">Aprovado:</label>
                    <select name="aprovado" class="border p-2 rounded w-full"
                            onchange="document.getElementById('filtrosForm').submit()">
                        <option value="">-- Todos --</option>
                        <option value="1" <?= ($_GET['aprovado'] ?? '')=='1'?'selected':'' ?>>Aprovado</option>
                        <option value="0" <?= ($_GET['aprovado'] ?? '')=='0'?'selected':'' ?>>Pendente</option>
                    </select>
                </div>

                <!-- ✖️ LIMPAR FILTROS -->
                <div class="flex mt-6 items-center justify-end">
                    <button type="button"
                        onclick="window.location.href='listarutl.php'"
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

                // 1) Submeter quando carregar ENTER
                input.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") {
                        e.preventDefault(); // evita refresh duplo
                        form.submit();
                    }
                });

                // 2) Submeter quando sair do input (blur)
                input.addEventListener("blur", function() {
                    form.submit();
                });
            </script>

            <?php if (isset($_GET['emailconfirmacao'])): ?>
                <div class="bg-blue-200 text-blue-800 p-4 rounded mb-6 text-center font-semibold shadow">
                    ✔ O utilizador foi criado com sucesso.  
                    Um email de confirmação foi enviado para o endereço indicado.
                </div>
            <?php endif; ?>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-6">

                    <?php if ($totalResultados == 0): ?>
                        <p class="col-span-3 text-center text-gray-600 text-lg py-10">
                            <span class="text-4xl mb-2">🔍</span>
                            Nenhum resultado encontrado para a pesquisa.
                        </p>    
                    <?php else: ?>
                            <?php while ($row = mysqli_fetch_assoc($result)) { 

                                if ($row['tipo'] === 'admin') continue;

                                $foto = $row['foto'] ?? null;
                                $fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";
                            ?>

                            <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                                <div class="flex items-center space-x-4 mb-4">
                                    <img src="<?= $fotoPerfil ?>" class="w-12 h-12s rounded-full object-cover border">
                                    <div>
                                        <p class="text-lg font-semibold text-gray-800"><?= $row['nome'] ?></p>
                                        <p class="text-sm text-gray-500"><?= $row['email'] ?></p>
                                    </div>
                                </div>

                                <div class="text-gray-700 space-y-1 mb-4">
                                    <p><strong>Tipo:</strong> <?= ucfirst($row['tipo']) ?></p>
                                    <p><strong>Telefone:</strong> <?= $row['telefone'] ?></p>
                                    <p><strong>Nascimento:</strong> <?= $row['datanascimento'] ?></p>
                                </div>

                                <div class="flex gap-2">

                                    <?php if ($row['tipo'] === 'administrador'): ?>

                                        <span class="text-gray-500 italic">Sem permissões</span>

                                    <?php else: ?>

                                        <div class="flex gap-3">

                                            <!-- Ícone Editar -->
                                            <button onclick="window.location.href='editarutl.php?id=<?= $row['IDutl'] ?>'"
                                                class="text-gray-500 hover:text-yellow-500 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                </svg>
                                            </button>

                                            <!-- Ícone Eliminar -->
                                            <button onclick="eliminarUtilizador(<?= $row['IDutl'] ?>)"
                                                class="text-gray-500 hover:text-red-600 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 011-1h4a1 1 0 011 1m-6 0h6" />
                                                </svg>
                                            </button>
                                        </div>
                                    <?php endif; ?>
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

                    <!-- PRIMEIRA PÁGINA -->
                    <a href="?pagina=1<?= $queryStringFiltros ?>" 
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">
                        ««
                    </a>

                    <!-- VOLTAR UMA -->
                    <a href="?pagina=<?= max(1, $paginaAtual - 1) . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">
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
                    <a href="?pagina=<?= $i . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center rounded 
                    <?= $i == $paginaAtual ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                        <?= $i ?>
                    </a>

                    <?php endfor; ?>

                    <!-- AVANÇAR UMA -->
                    <a href="?pagina=<?= min($totalPaginas, $paginaAtual + 1) . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">
                        »
                    </a>

                    <!-- ÚLTIMA PÁGINA -->
                    <a href="?pagina=<?= $totalPaginas . $queryStringFiltros ?>"
                    class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded hover:bg-gray-300">
                        »»
                    </a>

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
            Tens a certeza que desejas eliminar este utilizador?
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

<!-- SCRIPT para eliminar utilizador -->
<script>
    let idParaEliminar = null;

    function eliminarUtilizador(id) {
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

        fetch("listarutl.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "eliminar_id=" + idParaEliminar
        })
        .then(r => r.text())
        .then(res => {
            res = res.trim();

            if (res === "ok") {
                fecharModal();
                mostrarMensagem("Utilizador eliminado com sucesso.", "green");
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarMensagem("Erro ao eliminar utilizador.", "red");
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
</script>

</body>
</html>
