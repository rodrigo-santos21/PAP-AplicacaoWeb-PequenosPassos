<?php
session_start();
include("DBConnection.php");

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
        $tipo = $_POST['tipo']; // Admin escolhe o tipo

        // Impedir criação de administradores ou superadministradores
        if ($tipo === "administrador" || $tipo === "superadministrador") {
            $erro = "Não é permitido criar administradores.";
        } else {

            $sql = "INSERT INTO utilizador (nome, email, password, tipo, datanascimento, telefone)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "ssssss", 
                $nome, $email, $pass, $tipo, $datanascimento, $telefone
            );

            if (mysqli_stmt_execute($stmt)) {

                $IDutl = mysqli_insert_id($link);

                // Registo de log
                date_default_timezone_set("Europe/Lisbon");
                $fdatahora = date("Y-m-d H:i:s");

                mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                     VALUES ('Criação de Conta (Admin)', '$fdatahora', '$IDutl')");

                header("Location: listarutl.php?sucesso=adicionado");
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
    <script src="https://cdn.tailwindcss.com"></script>
</head>

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

            <div>
                <label for="pass">Password</label>
                <input name="pass" id="pass" type="password"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div>
                <label for="confirmarpass">Confirmar Password</label>
                <input name="confirmarpass" id="confirmarpass" type="password"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
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

            <div>
                <label for="datanascimento">Data de nascimento</label>
                <input name="datanascimento" id="datanascimento" type="date"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    required>
            </div>

            <div>
                <label for="telefone">Telefone</label>
                <input name="telefone" id="telefone" type="tel" pattern="[0-9]{9}"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="9 dígitos" required>
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