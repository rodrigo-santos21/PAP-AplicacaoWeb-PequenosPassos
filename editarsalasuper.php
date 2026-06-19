<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

// Buscar tema do utilizador
$stmtTema = mysqli_prepare($link, "SELECT tema FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtTema, "i", $IDutl);
mysqli_stmt_execute($stmtTema);
$resTema = mysqli_stmt_get_result($stmtTema);
$tema = mysqli_fetch_assoc($resTema)['tema'] ?? 'light';

// Atualizar sessão
$_SESSION['tema'] = $tema;


$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: listarsalasuper.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar sala
$res = mysqli_query($link, "SELECT * FROM sala WHERE IDsala = $id AND estado = 1");
$sala = mysqli_fetch_assoc($res);

if (!$sala) {
    header("Location: listarsalasuper.php?erro=nao_existe");
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
        VALUES ('Superadmin editou sala (ID $id)', '$fdatahora', '$IDutl')
    ");

    header("Location: listarsalasuper.php?sucesso=editado");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Editar Sala (Superadmin)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<body class="bg-gray-100 dark:bg-gray-900 dark:text-gray-100 min-h-screen">

    <!-- WRAPPER FLEX -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_superadmin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_superadmin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">Editar Sala</h1>
    
            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <form method="post" class="space-y-5">

                    <!-- NOME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nome</label>
                        <input type="text" name="nome" value="<?= $sala['nome'] ?>"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100" required>
                    </div>

                    <!-- CAPACIDADE -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacidade</label>
                        <input type="number" name="capacidade" value="<?= $sala['capacidade'] ?>"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100" required>
                    </div>

                    <!-- INFO ASSOCIADA -->
                    <div class="bg-gray-100 dark:bg-gray-700 dark:text-gray-200 p-3 rounded">
                        <p><strong>Crianças associadas:</strong> <?= $cri ?></p>
                        <p><strong>Educadores associados:</strong> <?= $edu ?></p>
                    </div>

                    <!-- BOTÕES -->
                    <div class="flex justify-between mt-6">
                        <a href="listarsalasuper.php"
                           class="w-[40%] px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white text-center 
                                  rounded-lg hover:bg-gray-600 dark:hover:bg-gray-500">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="w-[40%] px-4 py-2 bg-green-600 dark:bg-green-700 text-white 
                                       rounded-lg hover:bg-green-700 dark:hover:bg-green-600">
                            Guardar Alterações
                        </button>
                    </div>

                </form>

            </div>
        </main>
    </div>
</body>

<!-- TOAST -->
<?php if (isset($erro)): ?>
<script>
window.addEventListener("load", () => {
mostrarMensagem("reset", "<?= $erro ?>");
});
</script>
<?php endif; ?>

</body>
</html>
