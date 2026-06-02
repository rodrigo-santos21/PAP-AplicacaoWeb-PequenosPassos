<?php
session_start();
include("DBConnection.php");

// Apenas educadores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDutl = $_SESSION['id']; // ID do utilizador (educador logado)

/* ================================
   1) BUSCAR ID DO EDUCADOR + SALA
================================ */
$resEdu = mysqli_query($link, "
    SELECT IDedu, IDsala 
    FROM educador 
    WHERE IDutl = $IDutl AND estado = 1
");

if (!$resEdu || mysqli_num_rows($resEdu) === 0) {
    die("Erro: Educador não encontrado ou inativo.");
}

$edu = mysqli_fetch_assoc($resEdu);
$IDedu = $edu['IDedu'];
$IDsala = $edu['IDsala'];

/* ================================
   2) PROCESSAR FORMULÁRIO
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $datahora = $_POST['datahora'];
    $criadopor = $IDutl; // Educador cria

    // 1) Inserir atividade (educador é o responsável)
    $sql = "INSERT INTO atividade (titulo, datahora, IDedu, descricao, criadopor, estado)
            VALUES (?, ?, ?, ?, ?, 1)";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssisi", $titulo, $datahora, $IDedu, $descricao, $criadopor);

    if (mysqli_stmt_execute($stmt)) {

        // ID da atividade criada
        $IDatv = mysqli_insert_id($link);

        // 2) Buscar todas as crianças da sala do educador
        $resCri = mysqli_query($link, "
            SELECT IDcri 
            FROM crianca 
            WHERE estado = 1 AND IDsala = $IDsala
        ");

        // 3) Associar atividade às crianças da sala
        while ($cri = mysqli_fetch_assoc($resCri)) {
            $IDcri = $cri['IDcri'];

            mysqli_query($link, "
                INSERT INTO crianca_atividade (IDcri, IDatv, estado, realizada)
                VALUES ($IDcri, $IDatv, 1, 0)
            ");
        }

        // 4) Registo de log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Educador adicionou atividade (ID $IDatv)', '$fdatahora', '$criadopor')
        ");

        header("Location: listaratvedu.php?sucesso=adicionado");
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
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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
                <label class="block text-sm font-medium text-gray-700">Título</label>
                <input name="titulo" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Data e Hora</label>
                <input name="datahora" type="datetime-local"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" rows="5"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required></textarea>
            </div>

            <div class="flex justify-between">
                <a href="educador.php"
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
