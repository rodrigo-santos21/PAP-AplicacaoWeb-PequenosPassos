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
    header("Location: listarsala.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da sala
$stmt = mysqli_prepare($link, "SELECT * FROM sala WHERE IDsala = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sala = mysqli_fetch_assoc($result);

// Se não existir
if (!$sala) {
    header("Location: listarsala.php?erro=nao_existe");
    exit();
}

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $capacidade = $_POST['capacidade'];
    $criadopor = $_SESSION['id']; // ID do admin que está a editar a sala

    $stmt = mysqli_prepare($link, "UPDATE sala SET nome=?, capacidade=? WHERE IDsala=?");

    mysqli_stmt_bind_param($stmt, "sii", $nome, $capacidade, $id);

    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        // Registar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Edição de sala', '$fdatahora', '$criadopor')");

        header("Location: listarsala.php?sucesso=editado");
        exit();
    } else {
        $erro = "Erro ao atualizar sala.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Sala</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico"> <!-- ícone da tab do browser -->
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Editar Sala
        </h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="nome" value="<?= $sala['nome'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Capacidade</label>
                <input type="text" name="capacidade" value="<?= $sala['capacidade'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div class="flex justify-between mt-6">
                <a href="listarsala.php"
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