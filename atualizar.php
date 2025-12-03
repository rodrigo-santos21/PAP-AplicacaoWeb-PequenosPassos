<?php
include("DBConnection.php");

// Buscar todos os utilizadores
$sql = "SELECT IDutl, password FROM utilizador";
$result = mysqli_query($link, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['IDutl'];
    $pass = $row['password'];

    // Se não for hash (bcrypt começa com $2y$)
    if (substr($pass, 0, 4) !== '$2y$') {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $update = "UPDATE utilizador SET password = ? WHERE IDutl = ?";
        $stmt = mysqli_prepare($link, $update);
        mysqli_stmt_bind_param($stmt, "si", $hash, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo "Password do utilizador ID $id atualizada.<br>";
    }
}

mysqli_close($link);
echo "Atualização concluída!";
?>