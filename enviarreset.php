<?php
session_start();
include("DBConnection.php");
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

if (!isset($_POST['email'])) {
    header("Location: recuperar.php?erro=1");
    exit();
}

$email = $_POST['email'];

// Verificar se o email existe
$stmt = mysqli_prepare($link, "SELECT IDutl FROM utilizador WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: recuperar.php?erro=1");
    exit();
}

$IDutl = $user['IDutl'];

// Criar token
$token = bin2hex(random_bytes(32));
$expira = date("Y-m-d H:i:s", time() + 3600); // 1 hora para expirar o token

// Guardar token na BD
$stmt2 = mysqli_prepare($link, "UPDATE utilizador SET reset_token = ?, reset_token_expira = ? WHERE IDutl = ?");
mysqli_stmt_bind_param($stmt2, "ssi", $token, $expira, $IDutl);
mysqli_stmt_execute($stmt2);

// Enviar email
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

    $mail->Subject = "Recuperação de Password";

    // IMPORTANTE: quando estiveres num servidor real, substitui localhost pelo domínio
    $linkReset = "http://localhost/PAP/PAP-AplicacaoWeb-PequenosPassos/resetpassword.php?token=$token";

    $mail->isHTML(true);
    $mail->Body = "
        <p>Foi pedido um reset de password para a sua conta.</p>
        <p>Clique no botão abaixo para definir uma nova password:</p>

        <p style='text-align:center; margin: 30px 0;'>
            <a href='$linkReset'
               style='background-color:#2563eb; color:white; padding:12px 20px; text-decoration:none; border-radius:8px; font-size:16px; display:inline-block;'>
                Recuperar Password
            </a>
        </p>

        <p>Se não foi você, ignore este email.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    header("Location: recuperar.php?erro=1");
    exit();
}

header("Location: recuperar.php?sucesso=1");
exit();
?>
