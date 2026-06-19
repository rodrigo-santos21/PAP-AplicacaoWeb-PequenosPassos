<?php
session_start();
include("DBConnection.php");
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

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

// Buscar salas (sem JOIN)
$salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado = 1");

// Apenas SUPERADMIN pode aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($_POST['pass'] !== $_POST['confirmarpass']) {
        $erro = "As passwords não coincidem.";
    } else {

        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);
        $datanascimento = $_POST['datanascimento'];
        $telefone = $_POST['telefone'];
        $tipo = $_POST['tipo'];

        $hoje = new DateTime();
        $nascimento = new DateTime($datanascimento);
        $idade = $hoje->diff($nascimento)->y;

        if ($idade < 18) {
            $erro = "O utilizador deve ter pelo menos 18 anos.";
        }

        if (!isset($erro)) {

            $token = bin2hex(random_bytes(32));

            $sql = "INSERT INTO utilizador (nome, email, password, tipo, datanascimento, telefone, confirmado, token_confirmacao, aprovado)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, 1)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "sssssss", 
                $nome, $email, $pass, $tipo, $datanascimento, $telefone, $token
            );

            if (mysqli_stmt_execute($stmt)) {

                $IDutl = mysqli_insert_id($link);

                if ($tipo === "educador") {

                    $especialidade = $_POST['especialidade'] ?? null;
                    $sala = $_POST['sala'] ?? null;

                    $sqlEdu = "INSERT INTO educador (IDutl, especialidade, IDsala) VALUES (?, ?, ?)";
                    $stmtEdu = mysqli_prepare($link, $sqlEdu);
                    mysqli_stmt_bind_param($stmtEdu, "isi", $IDutl, $especialidade, $sala);
                    mysqli_stmt_execute($stmtEdu);
                }

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

                    $mail->Subject = "Conta Criada pelo Superadministrador";

                    $linkConfirmacao = "https://pequenospassos.infinityfree.io/confirmar.php?token=$token";

                    $mail->isHTML(true);
                    $mail->Body = "
                        <p>Olá <strong>$nome</strong>,</p>

                        <p>A sua conta foi criada pelo Superadministrador.</p>

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

                date_default_timezone_set("Europe/Lisbon");
                $fdatahora = date("Y-m-d H:i:s");

                mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                     VALUES ('Criação de Conta (Superadmin)', '$fdatahora', '$IDutl')");

                header("Location: listarutlsuper.php?sucesso=adicionado&emailconfirmacao=1");
                exit();
            } else {
                $erro = "Erro ao adicionar utilizador: " . mysqli_error($link);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Adicionar Utilizador (Superadmin)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<!-- SCRIPT global de toast-->
<script>
    function mostrarMensagem(tipo, texto) {
        const box = document.getElementById("msgGlobal");
        const icon = document.getElementById("msgIcon");
        const msg = document.getElementById("msgTexto");

        // Limpar classes antigas
        box.classList.remove("border-blue-600", "border-green-600", "border-yellow-500", "border-red-600");
        msg.classList.remove("text-blue-600", "text-green-600", "text-yellow-500", "text-red-600");

        const icons = {
            adicionar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-blue-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m4.5 12.75 6 6 9-13.5" />
            </svg>`,

            editar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 
                        2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 
                        1.13L6 18l.8-2.685a4.5 4.5 0 0 1 
                        1.13-1.897l8.932-8.931Zm0 0L19.5 
                        7.125M18 14v4.75A2.25 2.25 0 0 1 
                        15.75 21H5.25A2.25 2.25 0 0 1 
                        3 18.75V8.25A2.25 2.25 0 0 1 
                        5.25 6H10" />
            </svg>`,

            reset: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-yellow-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 
                        3.374 1.948 3.374h14.71c1.73 0 
                        2.813-1.874 1.948-3.374L13.949 
                        3.378c-.866-1.5-3.032-1.5-3.898 
                        0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>`,

            eliminar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-red-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21
                        c.342.052.682.107 1.022.166m-1.022-.165L19.5 19.5
                        a2.25 2.25 0 0 1-2.244 2.25H6.744A2.25 2.25 0 0 1
                        4.5 19.5L5.772 5.79m14.456 0a48.108 48.108 0 0 0
                        -3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0
                        a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164
                        -2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09
                        1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>`
        };

        // Aplicar ícone
        icon.innerHTML = icons[tipo];
        msg.textContent = texto;

        // Aplicar cor do texto
        if (tipo === "adicionar") msg.classList.add("text-blue-600");
        if (tipo === "editar") msg.classList.add("text-green-600");
        if (tipo === "reset") msg.classList.add("text-yellow-500");
        if (tipo === "eliminar") msg.classList.add("text-red-600");

        // Aplicar cor da borda
        if (tipo === "adicionar") box.classList.add("border-blue-600");
        if (tipo === "editar") box.classList.add("border-green-600");
        if (tipo === "reset") box.classList.add("border-yellow-500");
        if (tipo === "eliminar") box.classList.add("border-red-600");

        // Mostrar
        box.classList.remove("hidden", "opacity-0");
        box.classList.add("opacity-100");

        // Ocultar após 3 segundos
        setTimeout(() => {
            box.classList.add("opacity-0");
            setTimeout(() => box.classList.add("hidden"), 300);
        }, 3000);
    }
</script>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

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

<body class="bg-gray-100 dark:bg-gray-900 dark:text-gray-100 min-h-screen">

    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal" 
        class="hidden fixed top-5 right-5 bg-white dark:bg-gray-800 shadow-lg border-l-4 rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

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

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">Adicionar Utilizador</h1>
    
            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200 p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">

                    <div>
                        <label for="nome" class="text-gray-700 dark:text-gray-200">Nome</label>
                        <input name="nome" id="nome" type="text"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required>
                    </div>

                    <div>
                        <label for="email" class="text-gray-700 dark:text-gray-200">Email</label>
                        <input name="email" id="email" type="email"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required>
                    </div>

                    <!-- PASSWORD -->
                    <div class="relative">
                        <label for="pass" class="text-gray-700 dark:text-gray-200">Password</label>
                        <input name="pass" id="pass" type="password"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required>
                        
                        <button type="button" onclick="togglePassword('pass', 'eyePass')"
                            class="absolute right-3 top-9 text-gray-500 dark:text-gray-300">
                            <span id="eyePass">
                                <!-- Ícone inicial -->
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

                    <!-- CONFIRMAR PASSWORD -->
                    <div class="relative">
                        <label for="confirmarpass" class="text-gray-700 dark:text-gray-200">Confirmar Password</label>
                        <input name="confirmarpass" id="confirmarpass" type="password"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required>

                        <button type="button" onclick="togglePassword('confirmarpass', 'eyeConfirm')"
                            class="absolute right-3 top-9 text-gray-500 dark:text-gray-300">
                            <span id="eyeConfirm">
                                <!-- Ícone inicial -->
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

                    <!-- TIPO -->
                    <div>
                        <label for="tipo" class="text-gray-700 dark:text-gray-200">Tipo de Utilizador</label>
                        <select name="tipo" id="tipo"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required>
                            <option value="encarregado">Encarregado</option>
                            <option value="educador">Educador</option>
                            <option value="funcionario">Funcionário</option>
                            <option value="administrador">Administrador</option>
                            <option value="superadministrador">Superadministrador</option>
                        </select>
                    </div>

                    <!-- CAMPOS EDUCADORES -->
                    <div id="camposEducador" style="display:none;">

                        <div>
                            <label for="especialidade" class="text-gray-700 dark:text-gray-200">Especialidade</label>
                            <input name="especialidade" id="especialidade" type="text"
                                class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                       rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100">
                        </div>

                        <div>
                            <label for="sala" class="text-gray-700 dark:text-gray-200">Sala</label>
                            <select name="sala" id="sala"
                                class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                       rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100">
                                <option value="">Selecione uma sala</option>

                                <?php while ($s = mysqli_fetch_assoc($salas)): ?>
                                    <option value="<?= $s['IDsala'] ?>">
                                        <?= $s['nome'] ?>
                                    </option>
                                <?php endwhile; ?>

                            </select>
                        </div>

                    </div>

                    <!-- DATA -->
                    <div>
                        <label for="datanascimento" class="text-gray-700 dark:text-gray-200">Data de nascimento</label>
                        <input name="datanascimento" id="datanascimento" type="date"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>

                    <!-- TELEFONE -->
                    <div>
                        <label for="telefone" class="text-gray-700 dark:text-gray-200">Telefone</label>
                        <input name="telefone" id="telefone" type="tel" maxlength="9" pattern="\d{9}" placeholder="9 dígitos"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-700 dark:text-gray-100"
                            required
                            oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                    </div>

                    <!-- BOTÕES -->
                    <div class="flex justify-between">
                        <a href="listarutlsuper.php"
                            class="w-[40%] px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white text-center rounded-lg hover:bg-gray-600 dark:hover:bg-gray-500">
                            Cancelar
                        </a>

                        <button type="submit"
                            class="w-[40%] px-4 py-2 bg-green-600 dark:bg-green-700 text-white rounded-lg hover:bg-green-700 dark:hover:bg-green-600">
                            Adicionar
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>
</body>
</html>
