<?php
session_start();
include "DBConnection.php";

// Verifica se o utilizador é administrador
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

/* ============================================================
   PROCESSO DE ELIMINAÇÃO VIA AJAX (ANTES DE QUALQUER HTML)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {

    $id = intval($_POST['eliminar_id']);
    $success = true;

    // Buscar tipo do utilizador
    $resTipo = mysqli_query($link, "SELECT tipo FROM utilizador WHERE IDutl = $id");
    $tipo = mysqli_fetch_assoc($resTipo)['tipo'];

    // 1) Desativar utilizador
    $success &= mysqli_query($link, "UPDATE utilizador SET estado = 0 WHERE IDutl = $id");

    // 2) Desativar participações em reuniões
    $success &= mysqli_query($link, "UPDATE reuniao_participante SET estado = 0 WHERE IDutl = $id");

    /* ============================================================
       CASO SEJA ENCARREGADO → DESASSOCIAR CRIANÇAS
       ============================================================ */
    if ($tipo === "encarregado") {
        $success &= mysqli_query($link, "UPDATE crianca SET IDutl = NULL WHERE IDutl = $id");
    }

    /* ============================================================
       CASO SEJA EDUCATOR → DESATIVAR TODAS AS RELAÇÕES DEPENDENTES
       ============================================================ */
    if ($tipo === "educador") {

        // Buscar IDedu
        $resEdu = mysqli_query($link, "SELECT IDedu FROM educador WHERE IDutl = $id AND estado = 1");
        if ($rowEdu = mysqli_fetch_assoc($resEdu)) {
            $IDedu = $rowEdu['IDedu'];

            // Desativar educador
            $success &= mysqli_query($link, "UPDATE educador SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar relações criança-educador
            $success &= mysqli_query($link, "UPDATE crianca_educador SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar atividades criadas pelo educador
            $success &= mysqli_query($link, "UPDATE atividade SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar ocorrências criadas pelo educador
            $success &= mysqli_query($link, "UPDATE ocorrencia SET estado = 0 WHERE IDedu = $IDedu");

            // Desativar relações criança-atividade associadas às atividades do educador
            $resAtv = mysqli_query($link, "SELECT IDatv FROM atividade WHERE IDedu = $IDedu");
            while ($atv = mysqli_fetch_assoc($resAtv)) {
                $IDatv = $atv['IDatv'];
                $success &= mysqli_query($link, "UPDATE crianca_atividade SET estado = 0 WHERE IDatv = $IDatv");
            }
        }
    }

    /* ============================================================
       CASO SEJA ADMINISTRADOR → DESATIVAR REUNIÕES CRIADAS POR ELE
       ============================================================ */
    if ($tipo === "administrador") {
        $resReu = mysqli_query($link, "SELECT IDreu FROM reuniao WHERE criadopor = $id");
        while ($reu = mysqli_fetch_assoc($resReu)) {
            $IDreu = $reu['IDreu'];
            $success &= mysqli_query($link, "UPDATE reuniao SET estado = 0 WHERE IDreu = $IDreu");
            $success &= mysqli_query($link, "UPDATE reuniao_participante SET estado = 0 WHERE IDreu = $IDreu");
        }
    }

    // 5) Registar log
    if ($success) {
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");
        $idadmin = $_SESSION['id'];

        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Eliminação de utilizador (ID $id)', '$fdatahora', '$idadmin')
        ");
    }

    echo $success ? "ok" : "erro";
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Utilizadores</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    function eliminarUtilizador(id) {
        if (!confirm("Tem a certeza que deseja eliminar este utilizador?")) return;

        fetch("listarutl.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "eliminar_id=" + id
        })
        .then(r => r.text())
        .then(res => {
            res = res.trim();
            if (res === "ok") {
                alert("Utilizador eliminado com sucesso.");
                location.reload();
            } else {
                alert("Erro ao eliminar utilizador.");
                console.log("Resposta inesperada:", res);
            }
        });
    }
    </script>
</head>

<body class="bg-gray-100 min-h-screen">
    
    <?php if (isset($_GET['emailconfirmacao'])): ?>
        <div class="bg-blue-200 text-blue-800 p-4 rounded mb-6 text-center font-semibold shadow">
            ✔ O utilizador foi criado com sucesso.  
            Um email de confirmação foi enviado para o endereço indicado.
        </div>
    <?php endif; ?>

<div class="max-w-full mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">

    <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
        Página do Administrador
    </h1>
    <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
        Listar Utilizadores
    </h3>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white shadow rounded-lg">
            <thead>
                <tr class="bg-blue-600 text-white">
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Utilizador</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Password</th>
                    <th class="p-3 text-left">Tipo</th>
                    <th class="p-3 text-left">Data de Nascimento</th>
                    <th class="p-3 text-left">Telefone</th>
                    <th class="p-3 text-left">Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $result = mysqli_query($link, "SELECT * FROM utilizador WHERE estado = 1 ORDER BY IDutl");

                while ($row = mysqli_fetch_assoc($result)) {

                    if ($row['tipo'] === 'superadmin') continue;

                    echo "<tr class='border-b hover:bg-gray-100'>
                            <td class='p-3'>{$row['IDutl']}</td>
                            <td class='p-3'>{$row['nome']}</td>
                            <td class='p-3'>{$row['email']}</td>
                            <td class='p-3 text-gray-500'>Definida</td>
                            <td class='p-3'>{$row['tipo']}</td>
                            <td class='p-3'>{$row['datanascimento']}</td>
                            <td class='p-3'>{$row['telefone']}</td>
                            <td class='p-3 flex gap-2'>";

                    if ($row['tipo'] === 'administrador') {
                        echo "<span class='text-gray-500 italic'>Sem permissões</span>";
                    } else {
                        echo "
                            <a href='editarutl.php?id={$row['IDutl']}'
                               class='px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500'>
                                Editar
                            </a>

                            <button onclick='eliminarUtilizador({$row['IDutl']})'
                                class='px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700'>
                                Eliminar
                            </button>";
                    }

                    echo "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6 text-center">
        <a href="admin.php"
           class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Página Inicial
        </a>
    </div>

</div>

</body>
</html>
