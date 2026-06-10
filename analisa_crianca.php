<?php
session_start();
include("DBConnection.php");

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

$IDfunc = $_SESSION['id'];

// Verificar se veio ID pela URL
if (!isset($_GET['id'])) {
    header("Location: criancaspendentes.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da criança pendente
$sql = "SELECT * FROM crianca WHERE IDcri = $id AND estado = 0";
$res = mysqli_query($link, $sql);
$c = mysqli_fetch_assoc($res);

if (!$c) {
    header("Location: criancaspendentes.php?erro=nao_existe");
    exit();
}

/* ============================================================
   1) TENTAR BLOQUEAR A CRIANÇA
   ============================================================ */

if ($c['analise_por'] === NULL) {

    // Tentar bloquear para este funcionário
    mysqli_query($link,
        "UPDATE crianca 
         SET analise_por = $IDfunc 
         WHERE IDcri = $id AND analise_por IS NULL"
    );

    // Buscar novamente para confirmar
    $res = mysqli_query($link, "SELECT * FROM crianca WHERE IDcri = $id");
    $c = mysqli_fetch_assoc($res);

    if ($c['analise_por'] != $IDfunc) {
        // Outro funcionário ganhou o bloqueio
        header("Location: criancaspendentes.php?erro=bloqueado");
        exit();
    }

} else if ($c['analise_por'] != $IDfunc) {
    // Já está bloqueado por outro funcionário
    header("Location: criancaspendentes.php?erro=bloqueado");
    exit();
}

/* ============================================================
   2) BUSCAR NOME DO ENCARREGADO (SEM JOIN)
   ============================================================ */

$nomeEncarregado = "Desconhecido";

if ($c['IDutl']) {
    $IDenc = intval($c['IDutl']);
    $resEnc = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDenc");
    if ($resEnc && mysqli_num_rows($resEnc) > 0) {
        $enc = mysqli_fetch_assoc($resEnc);
        $nomeEncarregado = $enc['nome'];
    }
}

/* ============================================================
   3) PROCESSAR APROVAÇÃO OU REJEIÇÃO
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'];

    if ($acao === "aprovar") {

        header("Location: aprovar_crianca.php?id=$id");
        exit();

    } elseif ($acao === "rejeitar") {

        header("Location: rejeitar_crianca.php?id=$id");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Analisar Criança</title>
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

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Analisar Criança </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>
            
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <p><strong>Nome:</strong> <?= $c['nome'] ?></p>
                <p><strong>Data Nascimento:</strong> <?= $c['datanascimento'] ?></p>
                <p><strong>Sexo:</strong> <?= $c['sexo'] ?></p>
                <p><strong>Observações:</strong> <?= $c['observacoes'] ?></p>
                <p><strong>Encarregado:</strong> <?= $nomeEncarregado ?></p>

                <form method="post" class="mt-6 flex justify-between">

                    <button name="acao" value="aprovar"
                        class="w-[40%] px-4 py-2 bg-blue-600 text-white text-center rounded hover:bg-blue-700">
                        Aprovar
                    </button>

                    <button name="acao" value="rejeitar"
                        class="w-[40%] px-4 py-2 bg-red-600 text-white text-center rounded hover:bg-red-700">
                        Rejeitar
                    </button>

                </form>

            </div>
        </main>
    </div>

</body>
</html>
