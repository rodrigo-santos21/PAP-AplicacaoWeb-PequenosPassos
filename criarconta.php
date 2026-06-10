<?php
include("DBConnection.php");
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar passwords
    if ($_POST['pass'] !== $_POST['confirmarpass']) {
        echo "<p style='color:red'>Erro: As passwords não coincidem.</p>";
        exit;
    }

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    $datanascimento = $_POST['datanascimento'];
    $telefone = $_POST['telefone'];
    $tipo = $_POST['tipo'];

    // VALIDAR IDADE (mínimo 18 anos)
    $hoje = new DateTime();
    $nascimento = new DateTime($datanascimento);
    $idade = $hoje->diff($nascimento)->y;

    if ($idade < 18) {
        echo "<p style='color:red'>Erro: Precisa ter pelo menos 18 anos para criar uma conta.</p>";
        exit;
    }

    // Gerar token de confirmação
    $token = bin2hex(random_bytes(32));

    // Inserir utilizador como NÃO confirmado e NÃO aprovado
    $sql = "INSERT INTO utilizador (nome, email, password, tipo, datanascimento, telefone, confirmado, token_confirmacao, aprovado, analise_por)
        VALUES (?, ?, ?, ?, ?, ?, 0, ?, 0, NULL)";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $nome, $email, $pass, $tipo, $datanascimento, $telefone, $token);

    if (mysqli_stmt_execute($stmt)) {

        $IDutl = mysqli_insert_id($link);

        // Registar log
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Pedido de criação de conta (aguarda aprovação)', '$fdatahora', '$IDutl')");

        echo "<p style='color:green'>Pedido enviado! Aguarde aprovação da secretaria da escola.</p>";
        exit();

    } else {
        echo "<p style='color:red'>Erro: " . mysqli_error($link) . "</p>";
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
    function avaliar(frm) {
        frm.tipo.value = "encarregado";
        return true;
    }

    function togglePassword(inputId, eyeId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(eyeId);

        if (input.type === "password") {
            input.type = "text";
            eye.textContent = "👁️";
        } else {
            input.type = "password";
            eye.textContent = "👁️‍🗨️";
        }
    }
</script>

<body class="bg-[#90b77d] min-h-screen p-4 flex-col lg:flex lg:items-center lg:justify-center lg:h-full">

    <div class="w-full max-w-lg bg-white rounded-lg shadow-md p-6 lg:p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">Criar Conta</h2>

        <form name="criarconta" method="post" action="criarconta.php" 
              onsubmit="return avaliar(criarconta)" class="space-y-5">

            <div>
                <label for="nome">Nome</label>
                <input name="nome" id="nome" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="Insira o seu nome" required>
            </div>

            <div>
                <label for="email">Email</label>
                <input name="email" id="email" type="email"
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
                    <span id="eyePass">👁️‍🗨️</span>
                </button>
            </div>

            <div class="relative">
                <label for="confirmarpass">Confirmar Password</label>
                <input name="confirmarpass" id="confirmarpass" type="password"
                    class="mt-1 block w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="Insira novamente a password" required>

                <button type="button" onclick="togglePassword('confirmarpass', 'eyeConfirm')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyeConfirm">👁️‍🗨️</span>
                </button>
            </div>

            <input type="hidden" name="tipo" id="tipo">

            <div>
                <label for="datanascimento">Data de nascimento</label>
                <input name="datanascimento" id="datanascimento" type="date"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm" required>
            </div>

            <div>
                <label for="telefone">Telefone</label>
                <input name="telefone" id="telefone" type="tel" maxlength="9" pattern="\d{9}"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm"
                    placeholder="9 dígitos" required
                    oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>

            <!-- BOTÕES RESPONSIVOS -->
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
