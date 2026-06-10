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

// Apenas encarregados podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit();
}

$IDEE = intval($_SESSION['id']);

// Verificar se veio um ID pela URL
if (!isset($_GET['id'])) {
    header("Location: listarcriee.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da criança (SEM JOIN)
$stmt = mysqli_prepare($link, "SELECT * FROM crianca WHERE IDcri = ? AND estado = 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$crianca = mysqli_fetch_assoc($result);

// Se não existir
if (!$crianca) {
    header("Location: listarcriee.php?erro=nao_existe");
    exit();
}

// Verificar se a criança pertence ao encarregado
if ($crianca['IDutl'] != $IDEE) {
    header("Location: listarcriee.php?erro=sem_permissao");
    exit();
}

// Buscar sala (SEM JOIN)
$salaNome = "—";
if (!empty($crianca['IDsala'])) {
    $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = {$crianca['IDsala']}");
    if ($resSala && mysqli_num_rows($resSala) > 0) {
        $sala = mysqli_fetch_assoc($resSala);
        $salaNome = $sala['nome'];
    }
}

// Buscar educador associado (SEM JOIN)
$eduNome = "—";

$resEduRel = mysqli_query($link, "
    SELECT IDedu FROM crianca_educador 
    WHERE IDcri = $id AND estado = 1
");

if ($resEduRel && mysqli_num_rows($resEduRel) > 0) {
    $rel = mysqli_fetch_assoc($resEduRel);
    $IDedu = intval($rel['IDedu']);

    $resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDedu = $IDedu");
    if ($resEdu && mysqli_num_rows($resEdu) > 0) {
        $edu = mysqli_fetch_assoc($resEdu);
        $IDutlEdu = intval($edu['IDutl']);

        $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutlEdu");
        if ($resU && mysqli_num_rows($resU) > 0) {
            $u = mysqli_fetch_assoc($resU);
            $eduNome = $u['nome'];
        }
    }
}

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $datanascimento = $_POST['datanascimento'];
    $sexo = $_POST['sexo'];
    $observacoes = $_POST['observacoes'];

    // VALIDAÇÃO DA IDADE
    $idade = date_diff(date_create($datanascimento), date_create('today'))->y;

    if ($idade > 6) {
        $erro = "A criança não pode ter mais de 6 anos.";
    }

    if (!isset($erro)) {

        // Atualizar criança (SEM alterar sala, educadores ou encarregado)
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
                                 VALUES ('Edição da criança pelo encarregado (ID $id)', '$fdatahora', '$IDEE')");

            header("Location: listarcriee.php?sucesso=editado");
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

<body class="bg-gray-100 min-h-screen">

    <!-- WRAPPER FLEX RESPONSIVO -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_encarregado.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_encarregado.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

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
                        <label class="block text-sm font-medium text-gray-700">Sala</label>
                        <input type="text" value="<?= $salaNome ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg bg-gray-200"
                            readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Educador</label>
                        <input type="text" value="<?= $eduNome ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg bg-gray-200"
                            readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea name="observacoes" rows="4"
                            class="mt-1 w-full px-4 py-2 border rounded-lg"><?= $crianca['observacoes'] ?></textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="listarcriee.php"
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
