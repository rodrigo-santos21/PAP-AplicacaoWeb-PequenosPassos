<?php
include("DBConnection.php");
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Dados recebidos
    $nome = $_POST['nome'] ?? "";
    $email = $_POST['email'] ?? "";
    $pass = $_POST['pass'] ?? "";
    $confirmarpass = $_POST['confirmarpass'] ?? "";
    $datanascimento = $_POST['datanascimento'] ?? "";
    $telefone = $_POST['telefone'] ?? "";

    // Verificar passwords
    if ($pass !== $confirmarpass) {
        $erro = "As passwords não coincidem.";
    }

    // Validar idade
    if (empty($erro)) {
        $hoje = new DateTime();
        $nascimento = new DateTime($datanascimento);
        $idade = $hoje->diff($nascimento)->y;

        if ($idade < 18) {
            $erro = "Precisa ter pelo menos 18 anos para criar uma conta.";
        }
    }

    // Se não houver erros, prosseguir
    if (empty($erro)) {

        $passHash = password_hash($pass, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        $sql = "INSERT INTO utilizador 
                (nome, email, password, tipo, datanascimento, telefone, confirmado, token_confirmacao, aprovado, analise_por)
                VALUES (?, ?, ?, 'encarregado', ?, ?, 0, ?, 0, NULL)";

        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $nome, $email, $passHash, $datanascimento, $telefone, $token);

        if (mysqli_stmt_execute($stmt)) {

            $IDutl = mysqli_insert_id($link);

            date_default_timezone_set("Europe/Lisbon");
            $fdatahora = date("Y-m-d H:i:s");

            mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                 VALUES ('Pedido de criação de conta (aguarda aprovação)', '$fdatahora', '$IDutl')");

            $sucesso = "Pedido enviado! Aguarde aprovação da secretaria da escola.";

        } else {
            $erro = "Erro ao criar conta: " . mysqli_error($link);
        }
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

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

<body class="bg-[#90b77d] min-h-screen p-4 flex-col lg:flex lg:items-center lg:justify-center lg:h-full">

    <div class="w-full max-w-lg bg-white rounded-lg shadow-md p-6 lg:p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">Criar Conta</h2>

        <!-- MENSAGENS -->
        <?php if (!empty($erro)): ?>
            <div class="p-3 mb-4 bg-red-100 text-red-700 border border-red-300 rounded">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="p-3 mb-4 bg-green-100 text-green-700 border border-green-300 rounded">
                <?= $sucesso ?>
            </div>
        <?php endif; ?>

        <form name="criarconta" method="post" action="criarconta.php" class="space-y-5">

            <div>
                <label for="nome">Nome</label>
                <input name="nome" id="nome" type="text"
                    value="<?= htmlspecialchars($nome ?? '') ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="Insira o seu nome" required>
            </div>

            <div>
                <label for="email">Email</label>
                <input name="email" id="email" type="email"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="Insira o seu email" required>
            </div>

            <div class="relative">
                <label for="pass">Password</label>
                <input name="pass" id="pass" type="password"
                    class="mt-1 block w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="Insira a sua password" required>

                <button type="button" onclick="togglePassword('pass', 'eyePass')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyePass">
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
                <input name="confirmarpass" id="confirmarpass" type="password"
                    class="mt-1 block w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="Insira novamente a password" required>

                <button type="button" onclick="togglePassword('confirmarpass', 'eyeConfirm')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyeConfirm">
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

            <div>
                <label for="datanascimento">Data de nascimento</label>
                <input name="datanascimento" id="datanascimento" type="date"
                    value="<?= htmlspecialchars($datanascimento ?? '') ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm" required>
            </div>

            <div>
                <label for="telefone">Telefone</label>
                <input name="telefone" id="telefone" type="tel" maxlength="9" pattern="\d{9}"
                    value="<?= htmlspecialchars($telefone ?? '') ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="9 dígitos" required
                    oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>

            <div class="flex flex-col md:flex-row gap-3 justify-between">
                <button type="button"
                    onclick="window.location.href='index.php';"
                    class="w-full md:w-[45%] px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Voltar
                </button>

                <button type="submit"
                    class="w-full md:w-[45%] px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Finalizar Conta
                </button>
            </div>

        </form>
    </div>

</body>
</html>
