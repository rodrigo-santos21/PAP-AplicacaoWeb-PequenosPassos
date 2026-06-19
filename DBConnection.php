<?php
    $db_host = "sql210.infinityfree.com";
    $db_user = "if0_42180240";
    $db_pass = "PequenosPassos"; 
    $db_name = "if0_42180240_pequenos_passos";

    $link = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (!$link) {
        die("Erro na ligação: " . mysqli_connect_error());
    }
?>
