<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas funcionários podem aceder
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit;
}

$nome = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Educadores</title>
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
            include("sidebar_funcionario.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Listar Educadores </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>
            
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- GRID DE CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php
                // Buscar educadores ativos
                $query = "SELECT u.IDutl, u.nome, u.email, u.telefone, e.IDsala
                        FROM utilizador u
                        INNER JOIN educador e ON u.IDutl = e.IDutl
                        WHERE u.estado = 1 AND e.estado = 1
                        ORDER BY u.IDutl";

                $result = mysqli_query($link, $query);

                while ($row = mysqli_fetch_assoc($result)) {

                    // Buscar nome da sala
                    $salaNome = "—";
                    if (!empty($row['IDsala'])) {
                        $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = {$row['IDsala']}");
                        if ($resSala && mysqli_num_rows($resSala) > 0) {
                            $sala = mysqli_fetch_assoc($resSala);
                            $salaNome = $sala['nome'];
                        }
                    }
                ?>

                    <div class="bg-green-50 shadow-md rounded-lg p-6 hover:shadow-xl transition">

                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $row['nome'] ?></h2>

                        <div class="text-gray-700 space-y-1 mb-4">
                            <p><strong>ID:</strong> <?= $row['IDutl'] ?></p>
                            <p><strong>Email:</strong> <?= $row['email'] ?></p>
                            <p><strong>Telefone:</strong> <?= $row['telefone'] ?></p>
                            <p><strong>Sala:</strong> <?= $salaNome ?></p>
                        </div>

                        <div class="flex gap-3">

                            <!-- Ícone Editar -->
                            <button onclick="window.location.href='editaredufun.php?id=<?= $row['IDutl'] ?>'"
                                class="text-gray-500 hover:text-yellow-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                </svg>
                            </button>

                        </div>

                    </div>

                <?php } ?>

                </div>

            </div>

</body>
</html>
