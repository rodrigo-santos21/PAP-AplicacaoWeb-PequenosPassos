<?php
// Inclui a conexão
include("DBConnection.php");

// Processa o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $pass = ($_POST['pass']);
    $datanascimento = $_POST['datanascimento'];
    $telefone = $_POST['telefone'];
    $tipo = $_POST['tipo'];

    $sql = "INSERT INTO utilizador (nome, email, password, tipo, datanascimento, telefone)
            VALUES ('$nome', '$email', '$pass', '$tipo', '$datanascimento', '$telefone')";

    if (mysqli_query($link, $sql)) {
        // Redireciona para index.php após inserção
        header("Location: index.php");
        exit(); // importante para parar o script após redireção
    } else {
        echo "<p style='color:red'>Erro: " . mysqli_error($link) . "</p>";
    }
}
?>

<html>
    <head>
        <meta http-equiv="Content-Laguage" content="pt" />
        <script src=https://cdn.tailwindcss.com> </script>
        <meta charset="utf8"></meta>
        <title>Criar Conta</title>
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

        function avaliar(frm) { //verifica se os campos estão todos preenchidos
            if (frm.nome.value === "" || frm.email.value === "" || frm.pass.value === "" || frm.datanascimento.value === "" || frm.telefone.value === "") {
                alert("É necessário preencher todos os campos!");
                return false;
            }

            const idade = calcularIdade(frm.datanascimento.value);
            if (idade < 18) {
                alert("Precisa ter pelo menos 18 anos para criar uma conta.");
                return false;
            }

            frm.tipo.value = "Enc.educacao";
            return true;
        }
    </script>

    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div id="Body" class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
            <!-- Título -->
            <h2 class="text-xl font-bold text-gray-800 mb-6">Criar Conta</h2>
            <!-- Formulário -->
            <form name="criarconta" method="post" action="criarconta.php" onsubmit="return avaliar(criarconta)" class="space-y-5">
            <!-- Nome -->
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                <input name="nome" id="nome" type="text" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira o seu nome" required>
            </div>
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input name="email" id="email" type="email" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira o seu email" required>
            </div>
            <!-- Password -->
            <div>
                <label for="pass" class="block text-sm font-medium text-gray-700">Password</label>
                <input name="pass" id="pass" type="password" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira a sua password" required>
            </div>
            <!-- Tipo -->
            <input type="hidden" name="tipo" id="tipo">
            <!-- datanascimento -->
            <div>
                <label for="datanascimento" class="block text-sm font-medium text-gray-700">Data de nascimento</label>
                <input name="datanascimento" id="datanascimento" type="date" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira a sua data de nascimento" required>
            </div>
            <!-- Telefone -->
            <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input name="telefone" id="telefone" type="tel" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira o seu nº telefone" required>
            </div>
            <!-- Botões -->
            <div class="flex justify-center items-center">
                <button 
                    type="submit" 
                    class="px-4 w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Finalizar Conta
                </button>
            </div>

            </form>
        </div>
    </body>
</html>