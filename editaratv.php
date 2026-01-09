<?php
session_start();
include "DBConnection.php";

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
    $criadopor = $_SESSION['id']; // ID do admin que está a editar a atividade

    $stmt = mysqli_prepare($link, "UPDATE atividade SET titulo=?, descricao=? WHERE IDatv=?");

    mysqli_stmt_bind_param($stmt, "ssi", $titulo, $descricao, $id);

    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        // Registar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Edição de Atividade', '$fdatahora', '$criadopor')");

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
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Editar Atividade
        </h2>

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
                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" rows="5"
                class="mt-1 w-full px-4 py-2 border rounded-lg" required><?= $atividade['descricao'] ?></textarea>
            </div>

            <div class="flex justify-between mt-6">
                <a href="listaratv.php"
                   class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Guardar Alterações
                </button>
            </div>

        </form>
    </div>

</body>
</html>