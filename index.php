<html>
<head>
    <meta http-equiv="Content-Language" content="pt" />
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <style>
        .left-panel {
            background-image: url('imagens/bg_default3.png');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<script>
function avaliar(frm) {
    if (frm.email.value == "" || frm.password.value == "") {
        alert("É necessário preencher os campos!");
        return false;
    }
    return true;
}
</script>

<script>
    function togglePassword(inputId, eyeId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(eyeId);

        if (input.type === "password") {
            input.type = "text";
            eye.textContent = "👁️"; // olho aberto
        } else {
            input.type = "password";
            eye.textContent = "👁️‍🗨️"; // olho fechado
        }
    }
</script>



<body class="bg-[#90b77d] min-h-screen">

<div class="flex min-h-screen flex-col lg:flex-row">

    <!-- LADO ESQUERDO -->
    <div class="lg:flex lg:flex-col w-[52%] left-panel justify-center items-center text-blue-700 p-10 hidden">
        <img src="imagens/logologin.png" class="w-60  drop-shadow-lg">
        <h1 class="text-4xl font-bold drop-shadow-lg">Bem-vindo</h1>
        <p class="text-2xl mt-4 drop-shadow-xl">Aceda à sua conta</p>
    </div>

    <!-- LADO DIREITO -->
    <div class="w-full lg:w-[48%] flex items-center justify-center p-6 lg:p-10">
        <div class="w-full max-w-md bg-white rounded-lg shadow-md p-6 lg:p-8">

            <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Login</h2>

            <div class="lg:hidden flex justify-center mb-6">
                <img src="imagens/logologin.png" class="w-40 drop-shadow-lg">
            </div>

            <form name="Login" method="post" action="autentica.php" onsubmit="return avaliar(Login)" class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input name="email" type="email"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm"
                        placeholder="Insira o seu email" required>
                </div>

                <div class="relative">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>

                    <input name="password" id="password" type="password"
                        class="mt-1 block w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg shadow-sm"
                        placeholder="Insira a sua password" required>

                    <button type="button" onclick="togglePassword('password', 'eyeLogin')"
                        class="absolute right-3 top-9 text-gray-500">
                        <span id="eyeLogin">👁️‍🗨️</span>
                    </button>
                </div>

                <div class="flex flex-col lg:flex-row gap-3 justify-between items-center">
                    <button type="submit"
                        class="w-full lg:w-[35%] px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700">
                        Entrar
                    </button>

                    <a href="criarconta.php"
                        class="w-full lg:w-[35%] px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700">
                        Criar Conta
                    </a>
                </div>

                <div class="flex items-center justify-center p-1 "> Esqueceu-se da password?
                    <a href="recuperar.php" class="text-red-500 ml-4 hover:underline">
                        Redifinir Password
                    </a>
                </div>
            </form>

        </div>
    </div>

</div>

</body>
</html>
