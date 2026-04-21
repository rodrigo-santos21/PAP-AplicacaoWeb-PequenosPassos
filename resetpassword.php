<?php
session_start();
include("DBConnection.php");

// Verificar token
if (!isset($_GET['token'])) {
    die("Token inválido.");
}

$token = $_GET['token'];

$stmt = mysqli_prepare($link, "SELECT IDutl, reset_token_expira FROM utilizador WHERE reset_token = ?");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("Token inválido.");
}

// Verificar expiração
if (strtotime($user['reset_token_expira']) < time()) {
    die("O link expirou. Peça um novo reset de password.");
}

$IDutl = $user['IDutl'];

// Se submeteu nova password
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($_POST['pass'] !== $_POST['confirmarpass']) {
        $erro = "As passwords não coincidem.";
    } else {
        $novaPass = password_hash($_POST['pass'], PASSWORD_DEFAULT);

        // Atualizar password e limpar token
        $stmt2 = mysqli_prepare($link, "UPDATE utilizador SET password = ?, reset_token = NULL, reset_token_expira = NULL WHERE IDutl = ?");
        mysqli_stmt_bind_param($stmt2, "si", $novaPass, $IDutl);
        mysqli_stmt_execute($stmt2);

        header("Location: index.php?reset=sucesso");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Definir Nova Password</title>
    <link rel="stylesheet" href="style.css">

    <script>
    function togglePassword(inputId, eyeId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(eyeId);

        if (input.type === "password") {
            input.type = "text";
            eye.textContent = "👁️"; // olho aberto
        } else {
            input.type = "password";
            eye.textContent = "👁️‍🗨️"; // olho fechado
        }
    }
    </script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow">

        <h2 class="text-2xl font-bold text-center mb-6">Definir Nova Password</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">

            <div class="relative">
                <label for="pass">Nova Password</label>
                <input type="password" name="pass" id="pass" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg">

                <button type="button" onclick="togglePassword('pass', 'eyePass')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyePass">👁️‍🗨️</span>
                </button>
            </div>

            <div class="relative">
                <label for="confirmarpass">Confirmar Password</label>
                <input type="password" name="confirmarpass" id="confirmarpass" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg">

                <button type="button" onclick="togglePassword('confirmarpass', 'eyeConfirm')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyeConfirm">👁️‍🗨️</span>
                </button>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                Alterar Password
            </button>

        </form>

    </div>

</body>
</html>
