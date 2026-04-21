<?php
session_start();
include("DBConnection.php");
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

// Buscar salas da base de dados
$salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado = 1");

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar password e confirmação
    if ($_POST['pass'] !== $_POST['confirmarpass']) {
        $erro = "As passwords não coincidem.";
    } else {

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
            $erro = "O utilizador deve ter pelo menos 18 anos.";
        }

        // Impedir criação de administradores
        if (!isset($erro) && ($tipo === "administrador" || $tipo === "superadministrador")) {
            $erro = "Não é permitido criar administradores.";
        }

        // Se não houver erros, inserir utilizador
        if (!isset($erro)) {

            // Criar token de confirmação
            $token = bin2hex(random_bytes(32));

            $sql = "INSERT INTO utilizador (nome, email, password, tipo, datanascimento, telefone, confirmado, token_confirmacao, aprovado)
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, 1)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "sssssss", 
                $nome, $email, $pass, $tipo, $datanascimento, $telefone, $token
            );

            if (mysqli_stmt_execute($stmt)) {

                $IDutl = mysqli_insert_id($link);

                // Se for educador, inserir também na tabela educador
                if ($tipo === "educador") {

                    $especialidade = $_POST['especialidade'] ?? null;
                    $sala = $_POST['sala'] ?? null;

                    $sqlEdu = "INSERT INTO educador (IDutl, especialidade, IDsala) VALUES (?, ?, ?)";
                    $stmtEdu = mysqli_prepare($link, $sqlEdu);
                    mysqli_stmt_bind_param($stmtEdu, "isi", $IDutl, $especialidade, $sala);
                    mysqli_stmt_execute($stmtEdu);
                }

                // Enviar email de confirmação
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = "smtp.gmail.com";
                    $mail->SMTPAuth = true;
                    $mail->Username = "webaplicacao@gmail.com";
                    $mail->Password = "wbeabctqiecxzpda";
                    $mail->SMTPSecure = "tls";
                    $mail->Port = 587;

                    $mail->setFrom("webaplicacao@gmail.com", "Pequenos Passos");
                    $mail->addAddress($email);

                    $mail->Subject = "Confirmação de Conta";

                    $linkConfirmacao = "http://localhost/PAP/PAP-AplicacaoWeb-PequenosPassos/confirmar.php?token=$token";

                    $mail->isHTML(true);
                    $mail->Body = "
                        <p>Olá <strong>$nome</strong>,</p>

                        <p>A sua conta foi criada pelo administrador.</p>

                        <p>Clique no botão abaixo para confirmar o seu email:</p>

                        <p style='text-align:center; margin: 30px 0;'>
                            <a href='$linkConfirmacao'
                               style='background-color:#2563eb; color:white; padding:12px 20px; text-decoration:none; border-radius:8px; font-size:16px; display:inline-block;'>
                                Confirmar Conta
                            </a>
                        </p>

                        <p>Se não foi você, ignore este email.</p>
                    ";

                    $mail->send();

                } catch (Exception $e) {
                    $erro = "Erro ao enviar email de confirmação: " . $mail->ErrorInfo;
                }

                // Registo de log
                date_default_timezone_set("Europe/Lisbon");
                $fdatahora = date("Y-m-d H:i:s");

                mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                     VALUES ('Criação de Conta (Admin)', '$fdatahora', '$IDutl')");

                header("Location: listarutl.php?sucesso=adicionado&emailconfirmacao=1");
                exit();
            } else {
                $erro = "Erro ao adicionar utilizador: " . mysqli_error($link);
            }
        }
    }
}
?>

<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Utilizador</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<script>
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

    function mostrarCamposEducador() {
        const tipo = document.getElementById("tipo").value;
        const campos = document.getElementById("camposEducador");

        campos.style.display = (tipo === "educador") ? "block" : "none";
    }

    window.onload = function() {
        mostrarCamposEducador();
        document.getElementById("tipo").addEventListener("change", mostrarCamposEducador);
    };
</script>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Utilizador</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">

            <div>
                <label for="nome">Nome</label>
                <input name="nome" id="nome" type="text"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div>
                <label for="email">Email</label>
                <input name="email" id="email" type="email"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div class="relative">
                <label for="pass">Password</label>
                <input name="pass" id="pass" type="password"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                
                <button type="button" onclick="togglePassword('pass', 'eyePass')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyePass">👁️‍🗨️</span>
                </button>
            </div>

            <div class="relative">
                <label for="confirmarpass">Confirmar Password</label>
                <input name="confirmarpass" id="confirmarpass" type="password"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>

                <button type="button" onclick="togglePassword('confirmarpass', 'eyeConfirm')"
                    class="absolute right-3 top-9 text-gray-500">
                    <span id="eyeConfirm">👁️‍🗨️</span>
                </button>
            </div>

            <div>
                <label for="tipo">Tipo de Utilizador</label>
                <select name="tipo" id="tipo"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
                    <option value="encarregado">Encarregado</option>
                    <option value="educador">Educador</option>
                </select>
            </div>

            <!-- CAMPOS EXTRA PARA EDUCADORES -->
            <div id="camposEducador" style="display:none;">

                <div>
                    <label for="especialidade">Especialidade</label>
                    <input name="especialidade" id="especialidade" type="text"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label for="sala">Sala</label>
                    <select name="sala" id="sala"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecione uma sala</option>

                        <?php while ($s = mysqli_fetch_assoc($salas)): ?>
                            <option value="<?= $s['IDsala'] ?>">
                                <?= $s['nome'] ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>

            </div>

            <div>
                <label for="datanascimento">Data de nascimento</label>
                <input name="datanascimento" id="datanascimento" type="date"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div>
                <label for="telefone">Telefone</label>
                <input name="telefone" id="telefone" type="tel" maxlength="9" pattern="\d{9}" placeholder="9 dígitos"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required
                    oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>

            <div class="flex justify-between">
                <a href="admin.php"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </a>

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Adicionar
                </button>
            </div>

        </form>
    </div>
</body>
</html>
