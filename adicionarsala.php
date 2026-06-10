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

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'];
    $capacidade = $_POST['capacidade'];
    $criadopor = $_SESSION['id'];

    $sql = "INSERT INTO sala (nome, capacidade) VALUES (?, ?)";

    $stmt = mysqli_prepare($link, $sql);

    if (!$stmt) {
        die("Erro no prepare: " . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "si", $nome, $capacidade);

    if (mysqli_stmt_execute($stmt)) {

        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Adição de sala', '$fdatahora', '$criadopor')");

        header("Location: listarsala.php?sucesso=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar sala: " . mysqli_error($link);
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Sala</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico"> <!-- ícone da tab do browser -->
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
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_admin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_admin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

		<h1 class="text-3xl font-bold text-gray-800 mb-8">Adicionar Sala </h1>
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

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
                        <label for="capacidade" class="block text-sm font-medium text-gray-700">Capacidade</label>
                        <input name="capacidade" id="capacidade" type="number"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                            placeholder="Escreve um capacidade para a sala!"
                            required>
                    </div>

                    <div class="flex justify-between">
                        <a href="admin.php"
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