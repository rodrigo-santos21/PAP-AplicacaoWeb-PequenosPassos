<html>
    <head>
        <meta http-equiv="Content-Laguage" content="pt" />
        <script src=https://cdn.tailwindcss.com> </script>
        <meta charset="utf8"></meta>
        <title>Criar Conta</title>
    </head>

    <script> //verifica se os campos estão todos preenchidos
        function avaliar(frm)
        {
            if (frm.nome.value == "" || frm.email.value == "" || frm.pass.value == "" || frm.datanascimento.value == "" || frm.telefone.value == ""){ 
                alert ("É necessário preencher os campos!");
                return (false);
            } else
                return (true);
        }
    </script>

    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div id="Body" class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
            <!-- Título -->
            <h2 class="text-xl font-bold text-gray-800 mb-6">Criar Conta</h2>
            <!-- Formulário -->
            <form name="criarconta" method="post" action="index.php" onsubmit="return avaliar(criarconta)" class="space-y-5">
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input name="email" id="email" type="text" 
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
            <!-- Botões -->
            <div class="flex justify-between items-center">
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Entrar
                </button>
                <button 
                    type="button" 
                    onclick="window.location.href='criarconta.php';"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Criar Conta
                </button>
            </div>
            
            </form>
        </div>
    </body>
</html>