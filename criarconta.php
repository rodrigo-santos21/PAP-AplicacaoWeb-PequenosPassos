<?php
include("DBConnection.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT); // HASH seguro
    $datanascimento = $_POST['datanascimento'];
    $telefone = $_POST['telefone'];
    $tipo = $_POST['tipo'];

    $sql = "INSERT INTO utilizador (nome, email, password, tipo, datanascimento, telefone)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $nome, $email, $pass, $tipo, $datanascimento, $telefone);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php");
        exit();
    } else {
        echo "<p style='color:red'>Erro: " . mysqli_error($link) . "</p>";
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Criar Conta</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<script>
function calcularIdade(dataNascimento) {
    const hoje = new Date();
    const nascimento = new Date(dataNascimento);
    let idade = hoje.getFullYear() - nascimento.getFullYear();
    const mes = hoje.getMonth() - nascimento.getMonth();
    if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
        idade--;
    }
    return idade;
}

function avaliar(frm) {
    if (frm.nome.value === "" || frm.email.value === "" || frm.pass.value === "" || frm.datanascimento.value === "" || frm.telefone.value === "") {
        alert("É necessário preencher todos os campos!");
        return false;
    }

    const idade = calcularIdade(frm.datanascimento.value);
    if (idade < 18) {
        alert("Precisa ter pelo menos 18 anos para criar uma conta.");
        return false;
    }

    frm.tipo.value = "encarregado"; // ajusta conforme o perfil desejado
    return true;
}
</script>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Criar Conta</h2>

        <form name="criarconta" method="post" action="criarconta.php" onsubmit="return avaliar(criarconta)" class="space-y-5">
            <div>
                <label for="nome">Nome</label>
                <input name="nome" id="nome" type="text" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira o seu nome" required>
            </div>
            <div>
                <label for="email">Email</label>
                <input name="email" id="email" type="email" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira o seu email" required>
            </div>
            <div>
                <label for="pass">Password</label>
                <input name="pass" id="pass" type="password"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira a sua password" required>
            </div>
            <input type="hidden" name="tipo" id="tipo">
            <div>
                <label for="datanascimento">Data de nascimento</label>
                <input name="datanascimento" id="datanascimento" type="date"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="telefone">Telefone</label>
                <input name="telefone" id="telefone" type="tel" pattern="[0-9]{9}" placeholder="9 dígitos"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="flex justify-center">
                <button type="submit" class="px-4 w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Finalizar Conta
                </button>
            </div>
        </form>
    </div>
</body>
</html>