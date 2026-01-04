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
    header("Location: listarutl.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados do utilizador
$stmt = mysqli_prepare($link, "SELECT * FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$utilizador = mysqli_fetch_assoc($result);

// Se não existir
if (!$utilizador) {
    header("Location: listarutl.php?erro=nao_existe");
    exit();
}

// Impedir edição de administradores e superadministradores
if ($utilizador['tipo'] === 'administrador' || $utilizador['tipo'] === 'superadministrador') {
    header("Location: listarutl.php?erro=sem_permissao");
    exit();
}

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];
    $datanascimento = $_POST['datanascimento'];
    $telefone = $_POST['telefone'];

    $stmt = mysqli_prepare($link, "UPDATE utilizador SET nome=?, email=?, tipo=?, datanascimento=?, telefone=? WHERE IDutl=?");

    mysqli_stmt_bind_param($stmt, "sssssi", $nome, $email, $tipo, $datanascimento, $telefone, $id);

    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        // Registar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Edição de Conta', '$fdatahora', '$id')");

        header("Location: listarutl.php?sucesso=editado");
        exit();
    } else {
        $erro = "Erro ao atualizar utilizador.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Utilizador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Editar Utilizador
        </h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="nome" value="<?= $utilizador['nome'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?= $utilizador['email'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                <select name="tipo" class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    <option value="encarregado" <?= $utilizador['tipo'] === 'encarregado' ? 'selected' : '' ?>>Encarregado</option>
                    <option value="educador" <?= $utilizador['tipo'] === 'educador' ? 'selected' : '' ?>>Educador</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                <input type="date" name="datanascimento" value="<?= $utilizador['datanascimento'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" value="<?= $utilizador['telefone'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div class="flex justify-between mt-6">
                <a href="listarutl.php"
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