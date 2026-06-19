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

<!-- VER PASSWORD -->
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
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password</label>

                    <input name="password" id="password" type="password"
                        class="mt-1 block w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg shadow-sm
                            bg-white dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600"
                        placeholder="Insira a sua password" required>

                    <button type="button" onclick="togglePassword('password', 'eyeLogin')"
                        class="absolute right-3 top-9 text-gray-500">
                        <span id="eyeLogin">
                            <!-- Ícone inicial (password oculta) -->
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
