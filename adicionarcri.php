<?php
session_start();
include("DBConnection.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

// PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'];
    $datanascimento = $_POST['datanascimento'];
    $sexo = $_POST['sexo'];
    $observacoes = $_POST['observacoes'];
    $IDsala = $_POST['IDsala'];
    $IDutl = $_POST['IDutl']; // encarregado
    $educadores = $_POST['educadores'] ?? [];
    $criadopor = $_SESSION['id'];

    // 🔥 VALIDAÇÃO DA IDADE AQUI
    $idade = date_diff(date_create($datanascimento), date_create('today'))->y;

    if ($idade > 6) {
        $erro = "A criança não pode ter mais de 6 anos.";
    }

    // Inserir criança
    $sql = "INSERT INTO crianca (nome, datanascimento, sexo, observacoes, IDutl, IDsala, estado)
            VALUES (?, ?, ?, ?, ?, ?, 1)";

    $stmt = mysqli_prepare($link, $sql);

    if (!$stmt) {
        die("Erro no prepare: " . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "ssssii",
        $nome, $datanascimento, $sexo, $observacoes, $IDutl, $IDsala
    );

    if (mysqli_stmt_execute($stmt)) {

        $IDcri = mysqli_insert_id($link);

        // Associar educadores
        foreach ($educadores as $IDedu) {
            $stmt2 = mysqli_prepare($link,
                "INSERT INTO crianca_educador (IDcri, IDedu, estado) VALUES (?, ?, 1)"
            );
            mysqli_stmt_bind_param($stmt2, "ii", $IDcri, $IDedu);
            mysqli_stmt_execute($stmt2);
        }

        // Criar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Adição de criança: $nome', '$fdatahora', '$criadopor')");

        header("Location: adicionarcri.php?sucesso=1");
        exit();
    } else {
        $erro = "Erro ao adicionar criança: " . mysqli_error($link);
    }
}

// Buscar salas
$salas = mysqli_query($link, "SELECT * FROM sala WHERE estado = 1");

// Buscar encarregados
$encarregados = mysqli_query($link,
    "SELECT IDutl, nome FROM utilizador WHERE tipo = 'encarregado' AND estado = 1"
);

// Buscar educadores
$educadores = mysqli_query($link,
    "SELECT educador.IDedu, utilizador.nome
     FROM educador
     INNER JOIN utilizador ON educador.IDutl = utilizador.IDutl
     WHERE educador.estado = 1"
);
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Criança</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Criança</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                Criança adicionada com sucesso!
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input name="nome" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Nome da criança"
                    required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                <input name="datanascimento" type="date" max="<?= date('Y-m-d', strtotime('-6 years')) ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Sexo</label>
                <select name="sexo"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                    <option value="">Selecionar...</option>
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Observações</label>
                <textarea name="observacoes" rows="3"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Notas importantes (opcional)"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Sala</label>
                <select name="IDsala"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                    <option value="">Selecionar sala...</option>
                    <?php while ($s = mysqli_fetch_assoc($salas)): ?>
                        <option value="<?= $s['IDsala'] ?>"><?= $s['nome'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Encarregado de Educação</label>
                <select name="IDutl"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                    <option value="">Selecionar encarregado...</option>
                    <?php while ($e = mysqli_fetch_assoc($encarregados)): ?>
                        <option value="<?= $e['IDutl'] ?>"><?= $e['nome'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Educadores Associados</label>
                <div class="mt-2 space-y-2">
                    <?php while ($ed = mysqli_fetch_assoc($educadores)): ?>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="educadores[]" value="<?= $ed['IDedu'] ?>">
                            <span><?= $ed['nome'] ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
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
