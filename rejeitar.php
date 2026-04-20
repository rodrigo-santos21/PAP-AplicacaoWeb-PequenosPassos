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
$stmt = mysqli_prepare($link, "SELECT nome, email FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$u = mysqli_fetch_assoc($result);

if (!$u) {
    header("Location: inscricoespendentes.php?erro=nao_existe");
    exit();
}

// Enviar email de rejeição
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

    $mail->Subject = "A sua conta foi rejeitada";
    $mail->isHTML(true);

    $mail->Body = "
        <p>Olá <strong>{$u['nome']}</strong>,</p>

        <p>Informamos que o seu pedido de criação de conta foi <strong>rejeitado pelo administrador</strong>.</p>

        <p>Se acredita que isto foi um erro, por favor contacte a instituição.</p>

        <p>Obrigado pela sua compreensão.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    header("Location: inscricoespendentes.php?erro=email");
    exit();
}

// Eliminar utilizador
mysqli_query($link, "DELETE FROM utilizador WHERE IDutl = $id");

// Registar log
date_default_timezone_set("Europe/Lisbon");
$fdatahora = date("Y-m-d H:i:s");

mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                     VALUES ('Conta rejeitada e eliminada pelo administrador', '$fdatahora', '$id')");

header("Location: inscricoespendentes.php?sucesso=rejeitado");
exit();
?>
