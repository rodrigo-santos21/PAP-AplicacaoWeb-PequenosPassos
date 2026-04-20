<?php
session_start();
include("DBConnection.php");
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

// Verificar ID
if (!isset($_GET['id'])) {
    header("Location: inscricoespendentes.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados do utilizador
$stmt = mysqli_prepare($link, "SELECT * FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$u = mysqli_fetch_assoc($result);

if (!$u) {
    header("Location: inscricoespendentes.php?erro=nao_existe");
    exit();
}

// Atualizar estado para aprovado
mysqli_query($link, "UPDATE utilizador SET aprovado = 1 WHERE IDutl = $id");

// Enviar email de confirmação
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
    $mail->addAddress($u['email']);

    $mail->Subject = "A sua conta foi aprovada!";
    $linkConfirmacao = "http://localhost/PAP/PAP-AplicacaoWeb-PequenosPassos/confirmar.php?token=" . $u['token_confirmacao'];

    $mail->isHTML(true);
    $mail->Body = "
        <p>Olá <strong>{$u['nome']}</strong>,</p>
        <p>A sua conta foi aprovada pelo administrador.</p>
        <p>Clique no botão abaixo para confirmar o seu email:</p>
        <p style='text-align:center; margin: 30px 0;'>
            <a href='$linkConfirmacao'
               style='background-color:#2563eb; color:white; padding:12px 20px; text-decoration:none; border-radius:8px; font-size:16px; display:inline-block;'>
                Confirmar Conta
            </a>
        </p>
        <p>Se não foi você, ignore este email.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    header("Location: inscricoespendentes.php?erro=email");
    exit();
}

// Registar log
date_default_timezone_set("Europe/Lisbon");
$fdatahora = date("Y-m-d H:i:s");

mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                     VALUES ('Conta aprovada pelo administrador', '$fdatahora', '$id')");

header("Location: inscricoespendentes.php?sucesso=aprovado");
exit();
?>
