<?php
session_start();
include "DBConnection.php";

// Verifica se o utilizador é educador
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDutl = $_SESSION['id'];
$nome  = $_SESSION['user'];

/* ================================
   1) BUSCAR ID DO EDUCADOR
================================ */
$resEdu = mysqli_query($link, "
    SELECT IDedu, IDsala 
    FROM educador 
    WHERE IDutl = $IDutl AND estado = 1
");

if (!$resEdu || mysqli_num_rows($resEdu) === 0) {
    die("Erro: Educador não encontrado ou inativo.");
}

$edu    = mysqli_fetch_assoc($resEdu);
$IDedu  = $edu['IDedu'];
$IDsala = $edu['IDsala'];

/* ================================
   2) BUSCAR NOME DA SALA
================================ */
$nomeSala = "—";
$resSala  = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = $IDsala");

if ($resSala && mysqli_num_rows($resSala) > 0) {
    $nomeSala = mysqli_fetch_assoc($resSala)['nome'];
}

/* ================================
   3) BLOQUEAR QUALQUER TENTATIVA DE ELIMINAÇÃO
      (educador não pode eliminar crianças)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    echo "erro_permissao";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Crianças da Sala</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function eliminarCrianca(id) {
        // Segurança extra: mesmo que alguém tente forçar via JS, o PHP responde erro_permissao
        if (confirm("Não tem permissão para eliminar crianças. Fale com o administrador.")) {
            fetch("listarcriedu.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "eliminar_id=" + encodeURIComponent(id)
            })
            .then(r => r.text())
            .then(data => {
                if (data.trim() === "erro_permissao") {
                    alert("Não tem permissão para eliminar crianças. Contacte o administrador.");
                } else {
                    alert("Operação inválida.");
                }
            });
        }
    }
    </script>

</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
            Página do Educador
        </h1>

        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Crianças da Sala: <?= htmlspecialchars($nomeSala) ?>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse bg-white shadow rounded-lg">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Nome</th>
                        <th class="p-3 text-left">Data Nasc.</th>
                        <th class="p-3 text-left">Sexo</th>
                        <th class="p-3 text-left">Encarregado</th>
                        <th class="p-3 text-left">Observações</th>
                        <th class="p-3 text-left">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    /* ================================
                       4) BUSCAR CRIANÇAS DA SALA
                    ================================= */
                    $resCri = mysqli_query($link, "
                        SELECT * 
                        FROM crianca 
                        WHERE estado = 1 AND IDsala = $IDsala
                        ORDER BY IDcri ASC
                    ");

                    if (!$resCri) {
                        die("Erro na query: " . mysqli_error($link));
                    }

                    while ($cri = mysqli_fetch_assoc($resCri)) {

                        /* 5) BUSCAR ENCARREGADO */
                        $encNome = "—";

                        if (!empty($cri['IDutl'])) {
                            $resEnc = mysqli_query($link, "
                                SELECT nome 
                                FROM utilizador 
                                WHERE IDutl = {$cri['IDutl']}
                            ");

                            if ($resEnc && mysqli_num_rows($resEnc) > 0) {
                                $encNome = mysqli_fetch_assoc($resEnc)['nome'];
                            }
                        }

                        /* Sexo formatado */
                        $sexo = ($cri['sexo'] === "M") ? "Masculino" :
                                (($cri['sexo'] === "F") ? "Feminino" : "Indefinido");

                        /* Observações */
                        $obs = !empty($cri['observacoes']) ? $cri['observacoes'] : "—";

                        echo "
                        <tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$cri['IDcri']}</td>
                            <td class='p-3'>{$cri['nome']}</td>
                            <td class='p-3'>{$cri['datanascimento']}</td>
                            <td class='p-3'>{$sexo}</td>
                            <td class='p-3'>{$encNome}</td>
                            <td class='p-3'>{$obs}</td>

                            <td class='p-3 flex gap-2'>
                                <a href='editarcriedu.php?id={$cri['IDcri']}'
                                    class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition'>
                                    Editar
                                </a>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 text-center">
            <a href="educador.php"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-block">
                Página Inicial
            </a>
        </div>
    </div>
</body>
</html>
