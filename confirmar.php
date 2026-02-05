<?php
include "DBConnection.php";

$token = $_GET['token'] ?? null;
$mensagem = "";

if ($token) {

    // Verificar se o token existe e se a conta ainda não está confirmada
    $stmt = mysqli_prepare($link, "SELECT IDutl FROM utilizador WHERE token_confirmacao=? AND confirmado=0");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $id = $row['IDutl'];

        // Confirmar conta
        $stmt2 = mysqli_prepare($link, "
            UPDATE utilizador 
            SET confirmado=1, token_confirmacao=NULL 
            WHERE IDutl=?
        ");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);

        // Registar log da confirmação
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Conta confirmada', '$fdatahora', '$id')");

        $mensagem = "Conta confirmada com sucesso! Já pode iniciar sessão.";

    } else {
        $mensagem = "Token inválido ou conta já confirmada.";
    }

} else {
    $mensagem = "Token em falta.";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Confirmação de Conta</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md text-center">
        <p class="text-lg text-gray-800"><?= $mensagem ?></p>

        <a href="index.php" 
           class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Ir para Login
        </a>
    </div>
</body>
</html>