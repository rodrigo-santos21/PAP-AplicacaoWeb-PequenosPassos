<?php
session_start();
include("DBConnection.php");

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $criadopor = $_SESSION['id']; // ID do admin que está a criar a atividade


    // Inserir atividade
    $sql = "INSERT INTO atividade (titulo, descricao, criadopor)
            VALUES (?, ?, ?)";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $titulo, $descricao, $criadopor);

    if (mysqli_stmt_execute($stmt)) {

        // Registo de log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Adição de atividade', '$fdatahora', '$criadopor')");

        header("Location: listaratv.php?sucesso=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar atividade: " . mysqli_error($link);
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Atividade</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Atividade</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
                <input name="titulo" id="titulo" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreve um título para atividade!"
                    required>
            </div>

            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" id="descricao" rows="5"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreva aqui a descrição da atividade..."
                    required></textarea>
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