<?php
session_start();
include "DBConnection.php";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: listarsala.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar sala
$res = mysqli_query($link, "SELECT * FROM sala WHERE IDsala = $id AND estado = 1");
$sala = mysqli_fetch_assoc($res);

if (!$sala) {
    header("Location: listarsala.php?erro=nao_existe");
    exit();
}

// Contar dependências
$cri = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT COUNT(*) AS total FROM crianca WHERE IDsala = $id AND estado = 1"
))['total'];

$edu = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT COUNT(*) AS total FROM educador WHERE IDsala = $id AND estado = 1"
))['total'];

// PROCESSAR UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $capacidade = $_POST['capacidade'];
    $IDutl = $_SESSION['id'];

    $stmt = mysqli_prepare($link,
        "UPDATE sala SET nome=?, capacidade=? WHERE IDsala=?"
    );

    mysqli_stmt_bind_param($stmt, "sii", $nome, $capacidade, $id);
    mysqli_stmt_execute($stmt);

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Sala editada (ID $id)', '$fdatahora', '$IDutl')
    ");

    header("Location: listarsala.php?sucesso=editado");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Sala</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-md bg-white shadow-lg rounded-lg p-8">

    <h2 class="text-xl font-bold text-gray-800 mb-6">Editar Sala</h2>

    <form method="post" class="space-y-5">

        <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input type="text" name="nome" value="<?= $sala['nome'] ?>"
                   class="mt-1 w-full px-4 py-2 border rounded-lg" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Capacidade</label>
            <input type="number" name="capacidade" value="<?= $sala['capacidade'] ?>"
                   class="mt-1 w-full px-4 py-2 border rounded-lg" required>
        </div>

        <div class="bg-gray-100 p-3 rounded">
            <p><strong>Crianças associadas:</strong> <?= $cri ?></p>
            <p><strong>Educadores associados:</strong> <?= $edu ?></p>
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
