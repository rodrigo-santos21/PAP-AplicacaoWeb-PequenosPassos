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

// Verificar ID da criança
if (!isset($_GET['id'])) {
    header("Location: listarcriedu.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);
$IDutl = $_SESSION['id'];

/* ================================
   1) BUSCAR ID DO EDUCADOR
================================ */
$resEdu = mysqli_query($link, "SELECT IDedu, IDsala FROM educador WHERE IDutl = $IDutl AND estado = 1");

if (!$resEdu || mysqli_num_rows($resEdu) === 0) {
    die("Erro: Educador não encontrado.");
}

$edu = mysqli_fetch_assoc($resEdu);
$IDsalaEducador = $edu['IDsala'];

/* ================================
   2) BUSCAR CRIANÇA
================================ */
$stmt = mysqli_prepare($link, "SELECT * FROM crianca WHERE IDcri = ? AND estado = 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$crianca = mysqli_fetch_assoc($result);

if (!$crianca) {
    header("Location: listarcriedu.php?erro=nao_existe");
    exit();
}

/* ================================
   3) VERIFICAR SE A CRIANÇA É DA SALA DO EDUCADOR
================================ */
if ($crianca['IDsala'] != $IDsalaEducador) {
    die("Erro: Não tem permissão para editar esta criança.");
}

/* ================================
   4) PROCESSAR ATUALIZAÇÃO
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $datanascimento = $_POST['datanascimento'];
    $sexo = $_POST['sexo'];
    $observacoes = $_POST['observacoes'];

    // VALIDAÇÃO DA IDADE
    $idade = date_diff(date_create($datanascimento), date_create('today'))->y;

    if ($idade > 6) {
        $erro = "A criança não pode ter mais de 6 anos.";
    } else {

        // Atualizar criança (educador NÃO altera sala, encarregado ou educadores)
        $stmt = mysqli_prepare($link,
            "UPDATE crianca 
             SET nome=?, datanascimento=?, sexo=?, observacoes=?
             WHERE IDcri=?"
        );

        mysqli_stmt_bind_param($stmt, "ssssi",
            $nome, $datanascimento, $sexo, $observacoes, $id
        );

        $success = mysqli_stmt_execute($stmt);

        if ($success) {

            // Log
            date_default_timezone_set("Europe/Lisbon");
            $fdatahora = date("Y-m-d H:i:s");

            mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                 VALUES ('Educador editou criança (ID $id)', '$fdatahora', '$IDutl')");

            header("Location: listarcriedu.php?sucesso=editado");
            exit();
        } else {
            $erro = "Erro ao atualizar criança: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Criança</title>
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
            include("sidebar_educador.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Editar Criança </h1>
    
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="nome" value="<?= $crianca['nome'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                        <input type="date" name="datanascimento" value="<?= $crianca['datanascimento'] ?>"
                            max="<?= date('Y-m-d', strtotime('-6 years')) ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sexo</label>
                        <select name="sexo" class="mt-1 w-full px-4 py-2 border rounded-lg">
                            <option value="M" <?= $crianca['sexo'] === "M" ? "selected" : "" ?>>Masculino</option>
                            <option value="F" <?= $crianca['sexo'] === "F" ? "selected" : "" ?>>Feminino</option>
                            <option value="N" <?= $crianca['sexo'] === "N" ? "selected" : "" ?>>Prefere não divulgar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea name="observacoes" rows="4"
                            class="mt-1 w-full px-4 py-2 border rounded-lg"><?= $crianca['observacoes'] ?></textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="listarcriedu.php"
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
