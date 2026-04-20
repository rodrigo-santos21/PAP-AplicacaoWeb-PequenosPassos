<?php
session_start();
include("DBConnection.php");

// Verificar login
if (!isset($_SESSION['id'])) {
    header("Location: index.php?erro=permissao");
    exit();
}

$IDutl = $_SESSION['id'];
$tipo = $_SESSION['tipo'];

// Definir página de cancelar consoante o tipo de utilizador
if ($tipo === "superadministrador") {
    $paginaCancelar = "superadmin.php";

} elseif ($tipo === "administrador") {
    $paginaCancelar = "admin.php";

} elseif ($tipo === "educador") {
    $paginaCancelar = "educador.php";

} elseif ($tipo === "encarregado") {
    $paginaCancelar = "encarregado.php";

} else {
    $paginaCancelar = "index.php"; // fallback de segurança
}

// Buscar dados do utilizador
$stmt = mysqli_prepare($link, "SELECT nome, email, telefone, datanascimento FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmt, "i", $IDutl);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$utilizador = mysqli_fetch_assoc($result);

// PROCESSAR FORMULÁRIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];

    // Atualizar nome e telefone
    $stmt = mysqli_prepare($link, "UPDATE utilizador SET nome=?, telefone=? WHERE IDutl=?");
    mysqli_stmt_bind_param($stmt, "ssi", $nome, $telefone, $IDutl);
    mysqli_stmt_execute($stmt);

    // Se o utilizador preencheu password nova
    if (!empty($_POST['pass1']) || !empty($_POST['pass2'])) {

        if ($_POST['pass1'] !== $_POST['pass2']) {
            $erro = "As passwords não coincidem.";
        } else {
            $hash = password_hash($_POST['pass1'], PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($link, "UPDATE utilizador SET password=? WHERE IDutl=?");
            mysqli_stmt_bind_param($stmt, "si", $hash, $IDutl);
            mysqli_stmt_execute($stmt);

            $sucesso = "Password alterada com sucesso!";
        }
    }

    if (!isset($erro)) {
        $sucesso = "Dados atualizados com sucesso!";
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Perfil</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">

        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Perfil de <?php echo $_SESSION['user']; ?>
        </h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4"><?= $erro ?></div>
        <?php endif; ?>

        <?php if (isset($sucesso)): ?>
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4"><?= $sucesso ?></div>
        <?php endif; ?>

        <!-- FORMULÁRIO ÚNICO -->
        <form method="post" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="nome" value="<?= $utilizador['nome'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email (não editável)</label>
                <input type="email" value="<?= $utilizador['email'] ?>" disabled
                       class="mt-1 w-full px-4 py-2 border rounded-lg bg-gray-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" value="<?= $utilizador['telefone'] ?>"
                       class="mt-1 w-full px-4 py-2 border rounded-lg" 
                       required
                       oninput="this.value = this.value.replace(/[^0-9]/g, '');"> <!-- Só deixa introduzir números, impedindo assim a introdução de letras-->
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                <input type="date" value="<?= $utilizador['datanascimento'] ?>" disabled
                       class="mt-1 w-full px-4 py-2 border rounded-lg bg-gray-200">
            </div>

            <hr class="my-6">

            <h3 class="text-lg font-bold text-gray-800">Alterar Password</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700">Nova Password</label>
                <input type="password" name="pass1"
                       class="mt-1 w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Confirmar Password</label>
                <input type="password" name="pass2"
                       class="mt-1 w-full px-4 py-2 border rounded-lg">
            </div>

            <!-- BOTÃO FINAL -->
            <button type="submit"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Guardar Alterações
            </button>
        </form>
        
        <a href="<?= $paginaCancelar ?>"
            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 block text-center mt-4">
            Cancelar
        </a>

    </div>

</body>
</html>
