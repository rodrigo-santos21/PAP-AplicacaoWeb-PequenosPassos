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
    header("Location: listarreu.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da reuniao
$stmt = mysqli_prepare($link, "SELECT * FROM reuniao WHERE IDreu = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reuniao = mysqli_fetch_assoc($result);

// Se não existir
if (!$reuniao) {
    header("Location: listaratv.php?erro=nao_existe");
    exit();
}

// Converter datahora para formato datetime-local
$valueDataHora = date("Y-m-d\TH:i", strtotime($reuniao['datahora']));

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = $_POST['titulo'];
    $datahora = $_POST['datahora'];
    $localidade = $_POST['localidade'];
    $objetivo = $_POST['objetivo'];
    $criadopor = $_SESSION['id']; // ID do admin que está a editar a reuniao

    $stmt = mysqli_prepare($link, "UPDATE reuniao SET titulo=?, datahora=?, localidade=?, objetivo=? WHERE IDreu=?");

    mysqli_stmt_bind_param($stmt, "ssssi", $titulo, $datahora, $localidade, $objetivo, $id);

    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        // Registar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Edição de reunião', '$fdatahora', '$criadopor')");

        header("Location: listarreu.php?sucesso=editado");
        exit();
    } else {
        $erro = "Erro ao atualizar reunião.";
    }
}

?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Reunião</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Editar Reunião</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
                <input name="titulo" id="titulo" type="text" value="<?= $reuniao['titulo'] ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreve um título para a reunião!"
                    required>
            </div>

            <div>
                <label for="datahora" class="block text-sm font-medium text-gray-700">Data e Hora (reunião)</label>
                <input name="datahora" id="datahora" type="datetime-local"
                    value="<?= $valueDataHora ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div>
                <label for="localidade" class="block text-sm font-medium text-gray-700">Localidade</label>
                <input name="localidade" id="localidade" type="text" value="<?= $reuniao['localidade'] ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreve um local para a realização da atividade!"
                    required>
            </div>

            <div>
                <label for="objetivo" class="block text-sm font-medium text-gray-700">Objetivo</label>
                <textarea name="objetivo" id="objetivo" rows="5" value="<?= $reuniao['objetivo'] ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Escreva aqui o objetivo da reunião..."
                    required><?= $reuniao['objetivo'] ?></textarea>
            </div>

            <div class="flex justify-between mt-6">
                <a href="listarreu.php"
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