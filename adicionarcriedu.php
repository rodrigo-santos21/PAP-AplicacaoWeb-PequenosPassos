<?php
session_start();
include("DBConnection.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Apenas educadores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDedu = $_SESSION['id']; // Educador autenticado

// PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $IDcri = $_POST['IDcri'];
    $criadopor = $_SESSION['id'];

    // Verificar se a criança já tem educador
    $check = mysqli_query($link, "
        SELECT * FROM crianca_educador 
        WHERE IDcri = $IDcri AND estado = 1
    ");

    if (mysqli_num_rows($check) > 0) {
        $erro = "Esta criança já está associada a um educador.";
    } else {

        // Associar educador à criança
        $stmt = mysqli_prepare($link,
            "INSERT INTO crianca_educador (IDcri, IDedu, estado) VALUES (?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt, "ii", $IDcri, $IDedu);
        mysqli_stmt_execute($stmt);

        // Criar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Educador associou-se à criança ID $IDcri', '$fdatahora', '$criadopor')");

        header("Location: associar_crianca_educador.php?sucesso=1");
        exit();
    }
}

// Buscar crianças sem educador (SEM JOIN)
$criancasLivres = [];

$resCri = mysqli_query($link, "SELECT IDcri, nome FROM crianca WHERE estado = 1");

while ($cri = mysqli_fetch_assoc($resCri)) {

    $IDcri = $cri['IDcri'];

    // Verificar se esta criança já tem educador
    $resCheck = mysqli_query($link, "
        SELECT * FROM crianca_educador 
        WHERE IDcri = $IDcri AND estado = 1
    ");

    if (mysqli_num_rows($resCheck) == 0) {
        // Criança livre → adicionar à lista
        $criancasLivres[] = $cri;
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Associar Criança</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Associar Criança ao Educador</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4"><?= $erro ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                Criança associada com sucesso!
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700">Criança disponível</label>
                <select name="IDcri"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                    <option value="">Selecionar criança...</option>

                    <?php foreach ($criancasLivres as $c): ?>
                        <option value="<?= $c['IDcri'] ?>"><?= $c['nome'] ?></option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="flex justify-between">
                <a href="educador.php"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </a>

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Associar
                </button>
            </div>

        </form>
    </div>
</body>
</html>
