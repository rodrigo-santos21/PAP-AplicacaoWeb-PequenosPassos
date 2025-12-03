<html>
    <head>
        <meta http-equiv="Content-Language" content="pt" />
        <script src=https://cdn.tailwindcss.com> </script>
        <meta charset="utf-8">
        <title>Login</title>
    </head>

    <script> //verifica se os campos do email e da password estão preenchidos
        function avaliar(frm)
        {
            if (frm.email.value == "" || frm.password.value == ""){ 
                alert ("É necessário preencher os campos!");
                return (false);
            } else
                return (true);
        }
    </script>

    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div id="Body" class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
            <!-- Título -->
            <h2 class="text-xl font-bold text-gray-800 mb-6">Login</h2>
            <!-- Formulário -->
            <form name="Login" method="post" action="autentica.php" onsubmit="return avaliar(Login)" class="space-y-5">
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input name="email" id="email" type="email" 
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Insira o seu email" required>
            </div>
            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input name="password" id="password" type="password" 
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