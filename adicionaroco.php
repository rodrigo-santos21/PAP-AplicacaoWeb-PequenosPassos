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

$IDutl = intval($_SESSION['id']); //educador autenticado

$res = mysqli_query($link, "SELECT IDedu FROM educador WHERE IDutl = $IDutl AND estado = 1");

if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    $IDedu = intval($row['IDedu']);
} else {
    die("Erro: Educador não encontrado ou inativo.");
}


// PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $IDcri = intval($_POST['IDcri']);
    $tipo = mysqli_real_escape_string($link, $_POST['tipo']);
    $tipo_outro = null;

    if ($tipo === "Outro") {
        $tipo_outro = mysqli_real_escape_string($link, $_POST['tipo_outro']);
    }

    $gravidade = mysqli_real_escape_string($link, $_POST['gravidade']);
    $descricao = mysqli_real_escape_string($link, $_POST['descricao']);
    $criadopor = $IDedu;

    // Validar se a criança pertence ao educador
    $check = mysqli_query($link, "
        SELECT estado FROM crianca_educador 
        WHERE IDcri = $IDcri AND IDedu = $IDedu
    ");

    $permitido = false;
    while ($row = mysqli_fetch_assoc($check)) {
        if ($row['estado'] == 1) {
            $permitido = true;
        }
    }

    if (!$permitido) {
        $erro = "Não tem permissão para registar ocorrência nesta criança.";
    } else {

        // Inserir ocorrência
        $stmt = mysqli_prepare($link, "
            INSERT INTO ocorrencia (tipo, tipo_outro, datahora, descricao, gravidade, IDcri, IDedu, estado)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, 1)
        ");

        mysqli_stmt_bind_param($stmt, "ssssii",
            $tipo, $tipo_outro, $descricao, $gravidade, $IDcri, $IDedu
        );

        if (mysqli_stmt_execute($stmt)) {

            // Criar log
            date_default_timezone_set("Europe/Lisbon");
            $fdatahora = date("Y-m-d H:i:s");

            mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                 VALUES ('Ocorrência registada para criança ID $IDcri', '$fdatahora', '$criadopor')");

            header("Location: adicionaroco.php?sucesso=1");
            exit();
        } else {
            $erro = "Erro ao registar ocorrência: " . mysqli_error($link);
        }
    }
}

// Buscar IDs das crianças associadas ao educador (SEM JOIN)
$criancasIDs = mysqli_query($link, "
    SELECT IDcri FROM crianca_educador 
    WHERE IDedu = $IDedu AND estado = 1
");

// Criar array com dados completos das crianças
$criancas = [];

while ($row = mysqli_fetch_assoc($criancasIDs)) {

    $IDcri = $row['IDcri'];

    // Buscar dados da criança (SEM JOIN)
    $resCri = mysqli_query($link, "SELECT nome, estado FROM crianca WHERE IDcri = $IDcri");

    if ($resCri && mysqli_num_rows($resCri) > 0) {
        $cri = mysqli_fetch_assoc($resCri);

        if ($cri['estado'] == 1) {
            $criancas[] = [
                "IDcri" => $IDcri,
                "nome" => $cri['nome']
            ];
        }
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Registar Ocorrência</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Registar Ocorrência</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4"><?= $erro ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                Ocorrência registada com sucesso!
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700">Criança</label>
                <select name="IDcri" class="mt-1 block w-full px-4 py-2 border rounded-lg" required>
                    <option value="">Selecionar criança...</option>
                    <?php foreach ($criancas as $c): ?>
                        <option value="<?= $c['IDcri'] ?>"><?= $c['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo de Ocorrência</label>
                <select name="tipo" id="tipoSelect"
                        class="mt-1 block w-full px-4 py-2 border rounded-lg" required>
                    <option value="">Selecionar...</option>
                    <option value="Doença">Doença</option>
                    <option value="Queda">Queda</option>
                    <option value="Comportamento">Comportamento</option>
                    <option value="Agressão">Agressão</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>

            <div id="outroCampo" style="display:none;">
                <label class="block text-sm font-medium text-gray-700">Especificar outro tipo</label>
                <input type="text" name="tipo_outro"
                    class="mt-1 block w-full px-4 py-2 border rounded-lg"
                    placeholder="Descreva o tipo de ocorrência...">
            </div>

            <script>
            document.getElementById('tipoSelect').addEventListener('change', function() {
                if (this.value === "Outro") {
                    document.getElementById('outroCampo').style.display = "block";
                } else {
                    document.getElementById('outroCampo').style.display = "none";
                }
            });
            </script>

            <div>
                <label class="block text-sm font-medium text-gray-700">Gravidade</label>
                <select name="gravidade" class="mt-1 block w-full px-4 py-2 border rounded-lg" required>
                    <option value="">Selecionar...</option>
                    <option value="Leve">Leve</option>
                    <option value="Moderada">Moderada</option>
                    <option value="Grave">Grave</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" rows="3"
                    class="mt-1 block w-full px-4 py-2 border rounded-lg"
                    placeholder="Descreva o que aconteceu..." required></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Data</label>
                <input type="date" value="<?= date('Y-m-d') ?>" disabled
                       class="mt-1 block w-full px-4 py-2 border rounded-lg bg-gray-200">
            </div>

            <div class="flex justify-between">
                <a href="educador.php"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </a>

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Registar
                </button>
            </div>

        </form>
    </div>
</body>
</html>
