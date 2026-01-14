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
    $datahora = $_POST['datahora'];
    $localidade = $_POST['localidade'];
    $objetivo = $_POST['objetivo'];
    $criadopor = $_SESSION['id']; // ID do admin que está a criar a reunião


    // Inserir reunião
    $sql = "INSERT INTO reuniao (titulo, datahora, localidade, objetivo, criadopor)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $titulo, $datahora, $localidade, $objetivo, $criadopor);

    if (mysqli_stmt_execute($stmt)) {

        // Registo de log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Adição de reunião', '$fdatahora', '$criadopor')");

        header("Location: listarreu.php?sucesso=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar reunião: " . mysqli_error($link);
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Reunião</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Reunião</h2>

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
                    placeholder="Escreve um título para a reunião!"
                    required>
            </div>

            <div>
                <label for="datahora" class="block text-sm font-medium text-gray-700">Data e Hora (reunião)</label>
                <input name="datahora" id="datahora" type="date"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreve um!"
                    required>
            </div>

            <div>
                <label for="localidade" class="block text-sm font-medium text-gray-700">Localidade</label>
                <input name="localidade" id="localidade" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreve um local para a realização da atividade!"
                    required>
            </div>

            <div>
                <label for="objetivo" class="block text-sm font-medium text-gray-700">Objetivo</label>
                <textarea name="objetivo" id="objetivo" rows="5"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreva aqui o objetivo da reunião..."
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