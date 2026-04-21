<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Password</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow">

        <h2 class="text-2xl font-bold text-center mb-6">Recuperar Password</h2>

        <?php if (isset($_GET['erro'])): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                O email introduzido não existe.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                Foi enviado um email com instruções para recuperar a password.
            </div>
        <?php endif; ?>

        <form action="enviarreset.php" method="POST" class="space-y-5">

            <div>
                <label for="email">Email</label>
                <input type="email" name="email" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                Enviar link de recuperação
            </button>

        </form>

        <div class="text-center mt-4">
            <a href="index.php" class="text-blue-600 hover:underline">Voltar ao login</a>
        </div>

    </div>

</body>
</html>
