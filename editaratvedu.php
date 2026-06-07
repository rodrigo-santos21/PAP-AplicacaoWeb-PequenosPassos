<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas educadores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit();
}

$IDutl = $_SESSION['id'];

/* ================================
   1) BUSCAR ID DO EDUCADOR + SALA
================================ */
$resEdu = mysqli_query($link, "
    SELECT IDedu, IDsala 
    FROM educador 
    WHERE IDutl = $IDutl AND estado = 1
");

if (!$resEdu || mysqli_num_rows($resEdu) === 0) {
    die("Erro: Educador não encontrado.");
}

$edu = mysqli_fetch_assoc($resEdu);
$IDedu = $edu['IDedu'];
$IDsala = $edu['IDsala'];

/* ================================
   2) VALIDAR ID DA ATIVIDADE
================================ */
if (!isset($_GET['id'])) {
    header("Location: listaratvedu.php?erro=sem_id");
    exit();
}

$IDatv = intval($_GET['id']);

/* ================================
   3) BUSCAR ATIVIDADE
================================ */
$resAtv = mysqli_query($link, "
    SELECT * FROM atividade 
    WHERE IDatv = $IDatv AND estado = 1
");

$atividade = mysqli_fetch_assoc($resAtv);

if (!$atividade) {
    header("Location: listaratvedu.php?erro=nao_existe");
    exit();
}

/* ================================
   4) PROCESSAR FORMULÁRIO
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $datahora = $_POST['datahora'];

    // Atualizar atividade
    $stmt = mysqli_prepare($link, "
        UPDATE atividade 
        SET titulo=?, datahora=?, descricao=?
        WHERE IDatv=?
    ");
    mysqli_stmt_bind_param($stmt, "sssi", $titulo, $datahora, $descricao, $IDatv);
    mysqli_stmt_execute($stmt);

    /* ================================
       5) MARCAR REALIZAÇÕES INDIVIDUAIS
    ================================= */
    $realizadas = $_POST['realizadas'] ?? [];

    // Buscar todas as relações da atividade
    $resRel = mysqli_query($link, "
        SELECT IDcri 
        FROM crianca_atividade 
        WHERE IDatv = $IDatv AND estado = 1
    ");

    while ($rel = mysqli_fetch_assoc($resRel)) {
        $IDcri = $rel['IDcri'];

        if (in_array($IDcri, $realizadas)) {
            mysqli_query($link, "
                UPDATE crianca_atividade
                SET realizada = 1, data_realizada = NOW()
                WHERE IDcri = $IDcri AND IDatv = $IDatv
            ");
        } else {
            mysqli_query($link, "
                UPDATE crianca_atividade
                SET realizada = 0, data_realizada = NULL
                WHERE IDcri = $IDcri AND IDatv = $IDatv
            ");
        }
    }

    /* ================================
       6) MARCAR TODAS COMO REALIZADAS
    ================================= */
    if (isset($_POST['todas'])) {
        mysqli_query($link, "
            UPDATE crianca_atividade
            SET realizada = 1, data_realizada = NOW()
            WHERE IDatv = $IDatv AND estado = 1
        ");
    }

    // Log
    date_default_timezone_set("Europe/Lisbon");
    $fdatahora = date("Y-m-d H:i:s");

    mysqli_query($link, "
        INSERT INTO logs (descricao, datahora, IDutl)
        VALUES ('Educador editou atividade (ID $IDatv)', '$fdatahora', '$IDutl')
    ");

    header("Location: listaratvedu.php?sucesso=editado");
    exit();
}

/* ================================
   7) BUSCAR CRIANÇAS DA SALA
================================ */
$resCri = mysqli_query($link, "
    SELECT IDcri, nome 
    FROM crianca 
    WHERE estado = 1 AND IDsala = $IDsala
");

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Atividade</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 min-h-screen">

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php
            include("sidebar_educador.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Editar atividades das crianças da sua sala </h1>
    
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Título</label>
                        <input type="text" name="titulo" value="<?= $atividade['titulo'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data e Hora</label>
                        <input type="datetime-local" name="datahora"
                            value="<?= date('Y-m-d\TH:i', strtotime($atividade['datahora'])) ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" rows="5"
                                class="mt-1 w-full px-4 py-2 border rounded-lg" required><?= $atividade['descricao'] ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Marcar como realizada</label>

                        <div class="mt-2 space-y-2">

                            <?php
                            while ($cri = mysqli_fetch_assoc($resCri)) {

                                $IDcri = $cri['IDcri'];

                                // Buscar estado da realização
                                $resRel = mysqli_query($link, "
                                    SELECT realizada 
                                    FROM crianca_atividade 
                                    WHERE IDcri = $IDcri AND IDatv = $IDatv AND estado = 1
                                ");

                                $rel = mysqli_fetch_assoc($resRel);
                                $checked = ($rel && $rel['realizada'] == 1) ? "checked" : "";

                                echo "
                                <label class='flex items-center space-x-2'>
                                    <input type='checkbox' name='realizadas[]' value='$IDcri' $checked>
                                    <span>{$cri['nome']}</span>
                                </label>";
                            }
                            ?>

                        </div>

                        <div class="mt-4">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="todas" value="1">
                                <span>Marcar todas como realizadas</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="listaratvedu.php"
                        class="w-[40%] px-4 py-2 bg-gray-500 text-white text-center rounded-lg hover:bg-gray-600">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="w-[40%] px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Guardar Alterações
                        </button>
                    </div>

                </form>

            </div>
        </main>
    </div>

</body>
</html>
