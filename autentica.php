<?php
include 'DBConnection.php';
session_start();

// Verificar se os campos foram enviados
if (empty($_POST['email']) || empty($_POST['password'])) {
    header("Location: index.php?erro=1");
    exit();
}

$user = $_POST['email'];
$pass = $_POST['password'];

// Buscar utilizador pelo email
$sql = "SELECT IDutl, tipo, password FROM utilizador WHERE email = ?";
$stmt = mysqli_prepare($link, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $IDutl, $tipo, $hash);
        mysqli_stmt_fetch($stmt);

        // Verifica password com hash
        if (password_verify($pass, $hash)) {
            $_SESSION['id'] = $IDutl;
            $_SESSION['user'] = $user;
            $_SESSION['tipo'] = $tipo;
            $_SESSION['erro'] = 0;

            // Registo de log
            date_default_timezone_set("Europe/Lisbon");
            $fdatahora = date("Y-m-d H:i:s");
            mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl) 
                                 VALUES ('Início de sessão', '$fdatahora', '$IDutl')");

            // Redireciona conforme tipo
            if ($tipo === 'administrador') {
                header("Location: admin.php");
            } elseif ($tipo === 'encarregado') {
                header("Location: encarregado.php");
            } elseif ($tipo === 'educador') {
                header("Location: educador.php");
            } else {
                header("Location: index.php?erro=2"); // tipo desconhecido
            }
            exit();
        } else {
            $_SESSION['erro'] = 1;
            header("Location: index.php?erro=1");
            exit();
        }
    } else {
        $_SESSION['erro'] = 1;
        header("Location: index.php?erro=1");
        exit();
    }

    mysqli_stmt_close($stmt);
} else {
    die("Erro ao executar a consulta SQL");
}

mysqli_close($link);
?>