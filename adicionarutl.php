<?php
session_start();
include("DBConnection.php");
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault.png";

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

<body class="bg-gray-100 min-h-screen">

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside class="w-1/5 bg-white shadow-lg p-6 flex flex-col justify-between fixed left-0 top-0 h-screen overflow-y-auto no-scrollbar">

            <!-- LOGO + TEXTO -->
            <div class="flex items-center space-x-3 mb-8">
                <a href="admin.php" class="flex items-center space-x-3">
                <img src="imagens/logo.png" class="w-18 h-12 object-cover rounded-lg" alt="Logo">
                <span class="text-2xl font-bold text-blue-400">Pequenos Passos</span>
                </a>
            </div>

            <div class="border-t-2 border-blue-400 pt-8">

            <!-- MENU -->
            <?php $pagina = basename($_SERVER['PHP_SELF']); ?> <!-- Devolve a página atual-->

            <nav class="space-y-3 flex-1">
                <a href="admin.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'admin.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Página Inicial
                </a>

                <a href="adicionarutl.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarutl.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Utilizador
                </a>

                <a href="listarutl.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarutl.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Lista Utilizadores
                </a>

                <a href="adicionaratv.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionaratv.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Atividade
                </a>

                <a href="listaratv.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listaratv.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Atividades
                </a>

                <a href="adicionarreu.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarreu.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Reunião
                </a>

                <a href="listarreu.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarreu.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Reuniões
                </a>

                <a href="adicionarsala.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarsala.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Sala
                </a>

                <a href="listarsala.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarsala.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Salas
                </a>

                <a href="adicionarcri.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarcri.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Criança
                </a>

                <a href="listacri.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listacri.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Crianças
                </a>

                <a href="listaroco.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listaroco.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Ocorrências
                </a>

                <a href="admin_presencas.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'admin_presencas.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Presenças
                </a>

                <a href="logs.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'logs.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Consultar Logs
                </a>
            </nav>

            <!-- PERFIL + LOGOUT -->
            <div class="mt-8 border-t-2 border-blue-400 pt-6">

                <!-- PERFIL (AGORA É UM LINK) -->
                <a href="perfil.php"
                class="flex items-center space-x-3 mb-4 px-2 py-2 rounded-md transition
                <?= $pagina === 'perfil.php' 
                        ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' 
                        : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">

                    <img src="<?= $fotoPerfil ?>" class="w-12 h-12 rounded-full object-cover border" alt="Foto de Perfil">

                    <div>
                        <p class="font-semibold text-gray-800 truncate max-w-[180px]"><?= $_SESSION['user']; ?></p>
                        <p class="text-sm text-gray-500">Administrador</p>
                    </div>
                </a>

                <!-- LOGOUT -->
                <a href="logout.php"
                class="flex items-center justify-center gap-2 w-full text-center px-4 py-2 
                        bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-log-out">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Terminar Sessão
                </a>
            </div>
        </aside>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 mb-8">Adicionar Utilizador </h1>
            
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

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
                        <option value="funcionario">Funcionário</option>
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
                        class="w-[40%] text-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancelar
                    </a>

                    <button type="submit"
                        class="w-[40%] px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Adicionar
                    </button>
                </div>

            </form>
        </main>
    </div>
</body>
</html>
