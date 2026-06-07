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

/* ============================================================
   PERMISSÕES
   ============================================================ */

if (!isset($_SESSION['tipo']) || 
   ($_SESSION['tipo'] !== 'administrador' && 
    $_SESSION['tipo'] !== 'funcionario' &&
    $_SESSION['tipo'] !== 'superadmin')) {

    header("Location: index.php?erro=permissao");
    exit();
}

/* ============================================================
   RESET TOTAL DOS LOGS (APENAS SUPERADMIN)
   ============================================================ */

if (isset($_POST['reset_logs']) && $_SESSION['tipo'] === 'superadmin') {

    // Apagar todos os logs
    mysqli_query($link, "TRUNCATE TABLE logs");

    // Registar que o superadmin fez reset (opcional)
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");
    $IDutl = $_SESSION['id'];

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Superadministrador fez reset total aos logs', '$fdatahora', $IDutl)
    ");

    header("Location: logs.php?reset=ok");
    exit();
}

/* ============================================================
   BUSCAR LOGS
   ============================================================ */

$sql = "SELECT * FROM logs ORDER BY datahora DESC";
$result = mysqli_query($link, $sql);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registo de Logs</title>
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
            $tipo = $_SESSION['tipo'];

            if ($tipo === "administrador") {
                include("sidebar_admin.php");
            } elseif ($tipo === "superadmin") {
                include("sidebar_superadmin.php");
            } elseif ($tipo === "funcionario") {
                include("sidebar_funcionario.php");
            }
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		<h1 class="text-3xl font-bold text-gray-800 mb-8">Registo de Logs (Auditoria) </h1>

            <a href="<?=
                $_SESSION['tipo'] === 'superadmin' ? 'superadmin.php' :
                ($_SESSION['tipo'] === 'administrador' ? 'admin.php' : 'funcionario.php')
            ?>"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">               
                ← Voltar
            </a>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok'): ?>
                    <p class="text-center text-green-600 font-semibold mb-4">
                        Logs resetados com sucesso.
                    </p>
                <?php endif; ?>

                <!-- BOTÃO RESETAR LOGS (APENAS SUPERADMIN) -->
                <?php if ($_SESSION['tipo'] === 'superadmin'): ?>
                    <form method="POST" class="text-center mb-6"
                        onsubmit="return confirm('Tem a certeza que deseja apagar TODOS os logs? Esta ação é irreversível.')">
                        <button type="submit" name="reset_logs"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Resetar Logs
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (mysqli_num_rows($result) === 0): ?>
                    <p class="text-center text-gray-600">Não existem logs registados.</p>
                <?php else: ?>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse bg-white shadow rounded-lg">
                            <thead>
                                <tr class="bg-blue-600 text-white">
                                    <th class="p-3 text-left">Descrição</th>
                                    <th class="p-3 text-left">Utilizador</th>
                                    <th class="p-3 text-left">Email</th>
                                    <th class="p-3 text-left">Tipo</th>
                                    <th class="p-3 text-left">Data e Hora</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($log = mysqli_fetch_assoc($result)): ?>

                                    <?php
                                    $IDutl = $log['IDutl'];
                                    $sqlUser = "SELECT nome, email, tipo FROM utilizador WHERE IDutl = $IDutl";
                                    $resUser = mysqli_query($link, $sqlUser);
                                    $user = mysqli_fetch_assoc($resUser);
                                    ?>

                                    <tr class="border-b hover:bg-gray-100">
                                        <td class="p-3"><?= $log['descricao'] ?></td>

                                        <td class="p-3">
                                            <?= $user['nome'] ?? "<i>Utilizador removido</i>" ?>
                                        </td>

                                        <td class="p-3">
                                            <?= $user['email'] ?? "-" ?>
                                        </td>

                                        <td class="p-3">
                                            <?= $user['tipo'] ?? "-" ?>
                                        </td>

                                        <td class="p-3"><?= $log['datahora'] ?></td>
                                    </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
