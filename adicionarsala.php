<?php
session_start();
include("DBConnection.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'];
    $capacidade = $_POST['capacidade'];
    $criadopor = $_SESSION['id'];

    $sql = "INSERT INTO sala (nome, capacidade) VALUES (?, ?)";

    $stmt = mysqli_prepare($link, $sql);

    if (!$stmt) {
        die("Erro no prepare: " . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "si", $nome, $capacidade);

    if (mysqli_stmt_execute($stmt)) {

        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Adição de sala', '$fdatahora', '$criadopor')");

        header("Location: listarsala.php?sucesso=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar sala: " . mysqli_error($link);
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Sala</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Sala</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                <input name="nome" id="nome" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreve um nome para a sala!"
                    required>
            </div>

            <div>
                <label for="capacidade" class="block text-sm font-medium text-gray-700">Capacidade</label>
                <input name="capacidade" id="capacidade" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreve um capacidade para a sala!"
                    required>
            </div>

            <div class="flex justify-between">
                <a href="admin.php"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </a>

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Adicionar
                </button>
            </div>

        </form>
    </div>
</body>
</html>