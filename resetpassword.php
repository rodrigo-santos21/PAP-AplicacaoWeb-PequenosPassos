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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definir Nova Password</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function togglePassword(inputId, eyeId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(eyeId);

        const svgHidden = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 
                    16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 
                    2.863-.395M6.228 6.228A10.451 10.451 0 0 1 
                    12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 
                    10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 
                    3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228
                    -3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 
                    4.242L9.88 9.88" />
            </svg>`;

        const svgVisible = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 
                    7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 
                    9.963 7.178.07.207.07.431 0 .639C20.577 
                    16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007
                    -9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>`;

        if (input.type === "password") {
            input.type = "text";
            eye.innerHTML = svgVisible;
        } else {
            input.type = "password";
            eye.innerHTML = svgHidden;
        }
    }
    </script>
</head>

<body class="bg-[#90b77d] flex items-center justify-center min-h-screen">

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
                    <span id="eyePass">
                        <!-- Ícone inicial (password oculta) -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 
                                16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 
                                2.863-.395M6.228 6.228A10.451 10.451 0 0 1 
                                12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 
                                10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 
                                3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228
                                -3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 
                                4.242L9.88 9.88" />
                        </svg>
                    </span>
                </button>
            </div>

            <div class="relative">
                <label for="confirmarpass">Confirmar Password</label>
                <input type="password" name="confirmarpass" id="confirmarpass" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg">

                <button type="button" onclick="togglePassword('confirmarpass', 'eyeConfirm')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyeConfirm">
                        <!-- Ícone inicial (password oculta) -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 
                                16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 
                                2.863-.395M6.228 6.228A10.451 10.451 0 0 1 
                                12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 
                                10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 
                                3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228
                                -3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 
                                4.242L9.88 9.88" />
                        </svg>
                    </span>
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
