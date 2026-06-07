<?php
session_start();
include("DBConnection.php");

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas superadmins podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $datahora = $_POST['datahora'];
    $criadopor = $_SESSION['id']; // Superadmin é o responsável

    // 1) Inserir atividade (IDedu = NULL)
    $sql = "INSERT INTO atividade (titulo, datahora, IDedu, descricao, criadopor, estado)
            VALUES (?, ?, NULL, ?, ?, 1)";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $titulo, $datahora, $descricao, $criadopor);

    if (mysqli_stmt_execute($stmt)) {

        // ID da atividade criada
        $IDatv = mysqli_insert_id($link);

        // 2) Buscar todas as crianças ativas
        $resCri = mysqli_query($link, "SELECT IDcri FROM crianca WHERE estado = 1");

        // 3) Associar atividade a todas as crianças
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

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Superadmin adicionou atividade (ID $IDatv)', '$fdatahora', '$criadopor')");

        header("Location: listaratvsuper.php?sucesso=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar atividade: " . mysqli_error($link);
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Atividade (Superadmin)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 min-h-screen">

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php
            include("sidebar_superadmin.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Adicionar Atividade </h1>
    
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

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
                        <a href="superadmin.php"
                            class="w-[40%] px-4 py-2 bg-gray-500 text-white text-center rounded-lg hover:bg-gray-600">
                            Cancelar
                        </a>

                        <button type="submit"
                            class="w-[40%] px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Adicionar
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>
</body>
</html>
