<?php
    include 'DBConnection.php';

    session_start();

    if(!isset($_POST['user'], $_POST['pass'])){
        header("Location: index.php?erro=1");
        exit();
    }

    $user = $_POST['user'];
    $pass = $_POST['pass'];

    $sql = "SELECT id from utilizadores where user = ? and pass = ?";
    $stmt = mysqli_prepare($link, $sql);

    if ($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $user, $pass);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0){
            mysqli_stmt_bind_result($stmt, $id);
            mysqli_stmt_fetch($stmt);
            
            $_SESSION['iduser'] = $id;
            $_SESSION['user'] = $user;
            $_SESSION['erro'] = 0;

            if($id == 0){
                header("Location: admin.php");
            } else {
                header("Location: user.php");
            }
        } else {
            $_SESSION['erro'] = 1;
            header("Location: index.php?erro=1");
        }

        mysqli_stmt_close($stmt);
    } else {
        die("Erro ao executar a consulta SQL");
    }
    mysqli_close($link);
?>