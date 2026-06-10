<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas educadores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit();
}

// Verificar ID da ocorrência
if (!isset($_GET['id'])) {
    header("Location: listarocoedu.php?erro=sem_id");
    exit();
}

$IDoc = intval($_GET['id']);
$IDutl = intval($_SESSION['id']);

// Buscar IDedu correto
$res = mysqli_query($link, "SELECT IDedu FROM educador WHERE IDutl = $IDutl AND estado = 1");
if (!$res || mysqli_num_rows($res) == 0) {
    die("Erro: Educador não encontrado.");
}
$row = mysqli_fetch_assoc($res);
$IDedu = intval($row['IDedu']);

// Buscar ocorrência (SEM JOIN)
$resOc = mysqli_query($link, "SELECT * FROM ocorrencia WHERE IDoc = $IDoc AND estado = 1");
$oc = mysqli_fetch_assoc($resOc);

if (!$oc) {
    header("Location: listarocoedu.php?erro=nao_existe");
    exit();
}

// Verificar se a criança pertence ao educador (SEM JOIN)
$IDcri = intval($oc['IDcri']);

$resRel = mysqli_query($link, "
    SELECT estado FROM crianca_educador
    WHERE IDcri = $IDcri AND IDedu = $IDedu
");

$permitido = false;
while ($r = mysqli_fetch_assoc($resRel)) {
    if ($r['estado'] == 1) {
        $permitido = true;
    }
}

if (!$permitido) {
    header("Location: listarocoedu.php?erro=sem_permissao");
    exit();
}

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = mysqli_real_escape_string($link, $_POST['tipo']);
    $gravidade = mysqli_real_escape_string($link, $_POST['gravidade']);
    $descricao = mysqli_real_escape_string($link, $_POST['descricao']);

    // Se for "Outro", guardar o texto personalizado
    if ($tipo === "Outro") {
        $tipo_outro = mysqli_real_escape_string($link, $_POST['tipo_outro']);
    } else {
        $tipo_outro = null;
    }

    // Atualizar ocorrência
    $stmt = mysqli_prepare($link, "
        UPDATE ocorrencia
        SET tipo = ?, tipo_outro = ?, descricao = ?, gravidade = ?
        WHERE IDoc = ?
    ");

    mysqli_stmt_bind_param($stmt, "ssssi",
        $tipo, $tipo_outro, $descricao, $gravidade, $IDoc
    );

    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        // Log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Ocorrência editada (ID $IDoc)', '$fdatahora', '$IDutl')");

        header("Location: listarocoedu.php?sucesso=editado");
        exit();
    } else {
        $erro = "Erro ao atualizar ocorrência.";
    }
}

// Buscar nome da criança (SEM JOIN)
$criNome = "—";
$resCri = mysqli_query($link, "SELECT nome FROM crianca WHERE IDcri = $IDcri");
if ($resCri && mysqli_num_rows($resCri) > 0) {
    $cri = mysqli_fetch_assoc($resCri);
    $criNome = $cri['nome'];
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Ocorrência</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function toggleOutro() {
        let tipo = document.getElementById("tipoSelect").value;
        document.getElementById("outroCampo").style.display = (tipo === "Outro") ? "block" : "none";
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

<body class="bg-gray-100 min-h-screen">

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

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Editar ocorrência / Criança: <b><?= $criNome ?></b> </h1>
    
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select name="tipo" id="tipoSelect" onchange="toggleOutro()"
                                class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                            <option value="Doença" <?= $oc['tipo'] === 'Doença' ? 'selected' : '' ?>>Doença</option>
                            <option value="Queda" <?= $oc['tipo'] === 'Queda' ? 'selected' : '' ?>>Queda</option>
                            <option value="Comportamento" <?= $oc['tipo'] === 'Comportamento' ? 'selected' : '' ?>>Comportamento</option>
                            <option value="Agressão" <?= $oc['tipo'] === 'Agressão' ? 'selected' : '' ?>>Agressão</option>
                            <option value="Outro" <?= $oc['tipo'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>

                    <div id="outroCampo" style="display: <?= $oc['tipo'] === 'Outro' ? 'block' : 'none' ?>;">
                        <label class="block text-sm font-medium text-gray-700">Especificar outro tipo</label>
                        <input type="text" name="tipo_outro"
                            value="<?= $oc['tipo_outro'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gravidade</label>
                        <select name="gravidade" class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                            <option value="Leve" <?= $oc['gravidade'] === 'Leve' ? 'selected' : '' ?>>Leve</option>
                            <option value="Moderada" <?= $oc['gravidade'] === 'Moderada' ? 'selected' : '' ?>>Moderada</option>
                            <option value="Grave" <?= $oc['gravidade'] === 'Grave' ? 'selected' : '' ?>>Grave</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" rows="4"
                                class="mt-1 w-full px-4 py-2 border rounded-lg"
                                required><?= $oc['descricao'] ?></textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="listarocoedu.php"
                        class="w-[40%] px-4 py-2 bg-gray-500 text-white text-center rounded-lg hover:bg-gray-600">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="w-[40%] px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Guardar Alterações
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>

</body>
</html>
