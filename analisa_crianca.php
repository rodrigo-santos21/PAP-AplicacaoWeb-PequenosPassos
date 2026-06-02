<?php
session_start();
include("DBConnection.php");

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

<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-lg mx-auto bg-white shadow-lg rounded-lg p-6">

        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Analisar Criança
        </h2>

        <p><strong>Nome:</strong> <?= $c['nome'] ?></p>
        <p><strong>Data Nascimento:</strong> <?= $c['datanascimento'] ?></p>
        <p><strong>Sexo:</strong> <?= $c['sexo'] ?></p>
        <p><strong>Observações:</strong> <?= $c['observacoes'] ?></p>
        <p><strong>Encarregado:</strong> <?= $nomeEncarregado ?></p>

        <form method="post" class="mt-6 flex justify-between">

            <button name="acao" value="aprovar"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Aprovar
            </button>

            <button name="acao" value="rejeitar"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Rejeitar
            </button>

        </form>

        <div class="text-center mt-6">
            <a href="criancaspendentes.php"
               class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                Voltar
            </a>
        </div>

    </div>

</body>
</html>
