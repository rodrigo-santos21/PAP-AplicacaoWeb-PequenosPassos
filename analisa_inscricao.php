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
    header("Location: inscricoespendentes.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da inscrição
$sql = "SELECT * FROM utilizador WHERE IDutl = $id AND aprovado = 0";
$res = mysqli_query($link, $sql);
$u = mysqli_fetch_assoc($res);

if (!$u) {
    header("Location: inscricoespendentes.php?erro=nao_existe");
    exit();
}

/* ============================================================
   1) TENTAR BLOQUEAR A INSCRIÇÃO
   ============================================================ */

if ($u['analise_por'] === NULL) {

    // Tentar bloquear para este funcionário
    mysqli_query($link,
        "UPDATE utilizador 
        SET analise_por = $IDfunc 
        WHERE IDutl = $id AND analise_por IS NULL
        "
    );
    
    // Buscar novamente para confirmar
    $res = mysqli_query($link, "SELECT * FROM utilizador WHERE IDutl = $id");
    $u = mysqli_fetch_assoc($res);

    if ($u['analise_por'] != $IDfunc) {
        // Outro funcionário ganhou o bloqueio
        header("Location: inscricoespendentes.php?erro=bloqueado");
        exit();
    }

} else if ($u['analise_por'] != $IDfunc) {
    // Já está bloqueado por outro funcionário
    header("Location: inscricoespendentes.php?erro=bloqueado");
    exit();
}

/* ============================================================
   2) PROCESSAR APROVAÇÃO OU REJEIÇÃO
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'];

    if ($acao === "aprovar") {

        mysqli_query($link,
            "UPDATE utilizador 
             SET aprovado = 1, analise_por = NULL 
             WHERE IDutl = $id"
        );

        // Criar log
        date_default_timezone_set("Europe/Lisbon");
        $datahora = date("Y-m-d H:i:s");
        mysqli_query($link,
            "INSERT INTO logs (descricao, datahora, IDutl)
             VALUES ('Funcionário $IDfunc aprovou a conta $id', '$datahora', $IDfunc)"
        );

        header("Location: inscricoespendentes.php?sucesso=aprovado");
        exit();

    } elseif ($acao === "rejeitar") {

        mysqli_query($link, "DELETE FROM utilizador WHERE IDutl = $id");

        // Criar log
        date_default_timezone_set("Europe/Lisbon");
        $datahora = date("Y-m-d H:i:s");
        mysqli_query($link,
            "INSERT INTO logs (descricao, datahora, IDutl)
             VALUES ('Funcionário $IDfunc rejeitou a conta $id', '$datahora', $IDfunc)"
        );

        header("Location: inscricoespendentes.php?sucesso=rejeitado");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Analisar Inscrição</title>
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
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php
            include("sidebar_funcionario.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Analisar Inscrição </h1>
    
            <a href="inscricoespendentes.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <p><strong>Nome:</strong> <?= $u['nome'] ?></p>
                <p><strong>Email:</strong> <?= $u['email'] ?></p>
                <p><strong>Data Nascimento:</strong> <?= $u['datanascimento'] ?></p>
                <p><strong>Telefone:</strong> <?= $u['telefone'] ?></p>
                <p><strong>Tipo:</strong> <?= $u['tipo'] ?></p>

                <form method="post" class="mt-6 flex justify-between">

                    <a href="aprovar.php?id=<?= $u['IDutl'] ?>" class="w-[40%] px-4 py-2 bg-blue-600 text-white text-center rounded "> Aprovar</a>
                    <a href="rejeitar.php?id=<?= $u['IDutl'] ?>" class="w-[40%] px-4 py-2 bg-red-600 text-white text-center rounded "> Rejeitar</a>

                </form>

            </div>
        </main>
    </div>

</body>
</html>
