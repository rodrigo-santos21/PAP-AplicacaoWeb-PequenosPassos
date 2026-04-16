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
    $datanascimento = $_POST['datanascimento'];
    $sexo = $_POST['sexo'];
    $observacoes = $_POST['observacoes'];
    $idedu = $_POST['IDedu'];
    $criadopor = $_SESSION['id'];
    $idsala = $_POST['IDsala'];

    $sql = "INSERT INTO crianca (nome, datanascimento, sexo, observacoes, IDedu, IDutl IDsala) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($link, $sql);

    if (!$stmt) {
        die("Erro no prepare: " . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "ssssiii", $nome, $datanascimento, $sexo, $observacoes, $idedu, $criadopor, $idsala);

    if (mysqli_stmt_execute($stmt)) {

        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Adição de criança', '$fdatahora', '$criadopor')");

        header("Location: listarcri.php?sucesso=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar criança: " . mysqli_error($link);
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Criança</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico"> <!-- ícone da tab do browser -->
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Criança</h2>

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
                <label for="datanascimento">Data de nascimento</label>
                <input name="datanascimento" id="datanascimento" type="date"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Introduza a data de nascimento da criança!"
                    required>
            </div>
            
            <div>
                <label for="sexo">Sexo da criança</label>
                <select name="sexo" id="sexo"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                    <option value="masculino">Masculino</option>
                    <option value="feminino">Feminino</option>
                    <option value="Nulo">Nulo</option>
                </select>
            </div>

            <div>
                <label for="observacoes" class="block text-sm font-medium text-gray-700">Observacoes</label>
                <textarea name="observacoes" id="observacoes" rows="5"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreva aqui as observações da criança..."
                    required></textarea>
            </div>

            <div>
                <label for="idedu">Educador responsavél</label>
                <select name="idedu" id="idedu"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                    <option value=""></option> <!-- Busca de todos os educadores da base de dados -->
                </select>
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