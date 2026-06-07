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


// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

// Verificar se veio um ID pela URL
if (!isset($_GET['id'])) {
    header("Location: listaratv.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da atividade
$stmt = mysqli_prepare($link, "SELECT * FROM atividade WHERE IDatv = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$atividade = mysqli_fetch_assoc($result);

// Se não existir
if (!$atividade) {
    header("Location: listaratv.php?erro=nao_existe");
    exit();
}

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $datahora = $_POST['datahora'];
    $IDedu = $_POST['IDedu']; // Novo responsável
    $criadopor = $_SESSION['id']; // Admin que editou

    // Atualizar atividade
    $stmt = mysqli_prepare($link, "
        UPDATE atividade 
        SET titulo=?, datahora=?, descricao=?, IDedu=?
        WHERE IDatv=?
    ");

    mysqli_stmt_bind_param($stmt, "sssii", $titulo, $datahora, $descricao, $IDedu, $id);

    $success = mysqli_stmt_execute($stmt);

    if ($success) {

        // Registar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Admin editou atividade (ID $id)', '$fdatahora', '$criadopor')
        ");

        header("Location: listaratv.php?sucesso=editado");
        exit();
    } else {
        $erro = "Erro ao atualizar atividade.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Atividade</title>
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
            include("sidebar_admin.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Editar Atividade </h1>
            
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Título</label>
                        <input type="text" name="titulo" value="<?= $atividade['titulo'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data e Hora</label>
                        <input type="datetime-local" name="datahora"
                            value="<?= date('Y-m-d\TH:i', strtotime($atividade['datahora'])) ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Responsável</label>
                        <select name="IDedu"
                                class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                            <option value="">Selecione um educador</option>

                            <?php
                            // Buscar educadores ativos
                            $resEdu = mysqli_query($link, "SELECT IDedu, IDutl FROM educador WHERE estado = 1");

                            while ($e = mysqli_fetch_assoc($resEdu)) {

                                $IDutlEdu = $e['IDutl'];

                                // Buscar nome do utilizador
                                $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutlEdu");
                                $u = mysqli_fetch_assoc($resU);

                                $selected = ($atividade['IDedu'] == $e['IDedu']) ? "selected" : "";

                                echo "<option value='{$e['IDedu']}' $selected>{$u['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" rows="5"
                        class="mt-1 w-full px-4 py-2 border rounded-lg" required><?= $atividade['descricao'] ?></textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="listaratv.php"
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
