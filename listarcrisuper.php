<?php
session_start();
include "DBConnection.php";

// Verifica se o utilizador é superadmin e está autenticado
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit;
}

// PROCESSO DE ELIMINAÇÃO VIA AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = $_POST['eliminar_id'];
    
    // Desativar todas as relações com educadores
    mysqli_query($link, "
        UPDATE crianca_educador 
        SET estado = 0 
        WHERE IDcri = $id
    ");

    // Desativar relação criança ↔ atividades
    mysqli_query($link, "
        UPDATE crianca_atividade
        SET estado = 0
        WHERE IDcri = $id
    ");

    // Desativar ocorrências
    mysqli_query($link, "
        UPDATE ocorrencia
        SET estado = 0
        WHERE IDcri = $id
    ");

    // Desativar criança (soft delete)
    $stmt = mysqli_prepare($link, "UPDATE crianca SET estado = 0 WHERE IDcri = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $success = mysqli_stmt_execute($stmt);

    // Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Superadmin eliminou criança (ID $id)', '$fdatahora', '{$_SESSION['id']}')");
    }

    echo $success ? "ok" : "erro";
    exit;
}

$nome = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Crianças (Superadmin)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function eliminarCrianca(id) {
        if (confirm("Tem a certeza que deseja eliminar esta criança?")) {

            fetch("listarcrisuper.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "eliminar_id=" + encodeURIComponent(id)
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "ok") {
                    alert("Criança eliminada com sucesso.");
                    location.reload();
                } else {
                    alert("Erro ao eliminar criança.");
                }
            });
        }
    }
    </script>

</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
            Página do Superadministrador
        </h1>
        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Listar Crianças
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Nome</th>
                        <th class="p-3 text-left">Data Nasc.</th>
                        <th class="p-3 text-left">Sexo</th>
                        <th class="p-3 text-left">Sala</th>
                        <th class="p-3 text-left">Encarregado</th>
                        <th class="p-3 text-left">Observações</th>
                        <th class="p-3 text-left">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    // Buscar todas as crianças ativas
                    $query = "SELECT * FROM crianca WHERE estado = 1 ORDER BY IDcri";
                    $result = mysqli_query($link, $query);

                    if (!$result) {
                        die('Erro na query: ' . mysqli_error($link));
                    }

                    while ($cri = mysqli_fetch_assoc($result)) {

                        // Buscar sala
                        $salaNome = "—";
                        if (!empty($cri['IDsala'])) {
                            $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = {$cri['IDsala']}");
                            if ($resSala && mysqli_num_rows($resSala) > 0) {
                                $sala = mysqli_fetch_assoc($resSala);
                                $salaNome = $sala['nome'];
                            }
                        }

                        // Buscar encarregado
                        $encNome = "—";
                        if (!empty($cri['IDutl'])) {
                            $resEnc = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$cri['IDutl']}");
                            if ($resEnc && mysqli_num_rows($resEnc) > 0) {
                                $enc = mysqli_fetch_assoc($resEnc);
                                $encNome = $enc['nome'];
                            }
                        }

                        // Sexo formatado
                        if ($cri['sexo'] === "M") {
                            $sexo = "Masculino";
                        } elseif ($cri['sexo'] === "F") {
                            $sexo = "Feminino";
                        } else {
                            $sexo = "Indefinido";
                        }

                        // Observações
                        $obs = !empty($cri['observacoes']) ? $cri['observacoes'] : "—";

                        echo "
                        <tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$cri['IDcri']}</td>
                            <td class='p-3'>{$cri['nome']}</td>
                            <td class='p-3'>{$cri['datanascimento']}</td>
                            <td class='p-3'>{$sexo}</td>
                            <td class='p-3'>{$salaNome}</td>
                            <td class='p-3'>{$encNome}</td>
                            <td class='p-3'>{$obs}</td>

                            <td class='p-3 flex gap-2'>
                                <a href='editarcrisuper.php?id={$cri['IDcri']}'
                                    class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition'>
                                    Editar
                                </a>

                                <button onclick='eliminarCrianca({$cri['IDcri']})'
                                    class=\"px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition\">
                                    Eliminar
                                </button>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 text-center">
            <a href="superadmin.php"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-block">
                Página Inicial
            </a>
        </div>
    </div>
</body>
</html>
