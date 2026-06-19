<?php
session_start();
include("DBConnection.php");

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

// Buscar tema do utilizador
$stmtTema = mysqli_prepare($link, "SELECT tema FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtTema, "i", $IDutl);
mysqli_stmt_execute($stmtTema);
$resTema = mysqli_stmt_get_result($stmtTema);
$tema = mysqli_fetch_assoc($resTema)['tema'] ?? 'light';

// Atualizar sessão
$_SESSION['tema'] = $tema;


$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

// Apenas funcionários podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit();
}

$IDfunc = $_SESSION['id'];

// Verificar se veio ID pela URL
if (!isset($_GET['id'])) {
    header("Location: inscricoespendentes.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da inscrição
$sql = "SELECT * FROM utilizador WHERE IDutl = $id AND aprovado = 0";
$res = mysqli_query($link, $sql);
$u = mysqli_fetch_assoc($res);

if (!$u) {
    header("Location: inscricoespendentes.php?erro=nao_existe");
    exit();
}

/* ============================================================
   1) TENTAR BLOQUEAR A INSCRIÇÃO
   ============================================================ */

if ($u['analise_por'] === NULL) {

    // Tentar bloquear para este funcionário
    mysqli_query($link,
        "UPDATE utilizador 
        SET analise_por = $IDfunc 
        WHERE IDutl = $id AND analise_por IS NULL
        "
    );
    
    // Buscar novamente para confirmar
    $res = mysqli_query($link, "SELECT * FROM utilizador WHERE IDutl = $id");
    $u = mysqli_fetch_assoc($res);

    if ($u['analise_por'] != $IDfunc) {
        // Outro funcionário ganhou o bloqueio
        header("Location: inscricoespendentes.php?erro=bloqueado");
        exit();
    }

} else if ($u['analise_por'] != $IDfunc) {
    // Já está bloqueado por outro funcionário
    header("Location: inscricoespendentes.php?erro=bloqueado");
    exit();
}

/* ============================================================
   2) PROCESSAR APROVAÇÃO OU REJEIÇÃO
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'];

    if ($acao === "aprovar") {

        mysqli_query($link,
            "UPDATE utilizador 
             SET aprovado = 1, analise_por = NULL 
             WHERE IDutl = $id"
        );

        // Criar log
        date_default_timezone_set("Europe/Lisbon");
        $datahora = date("Y-m-d H:i:s");
        mysqli_query($link,
            "INSERT INTO logs (descricao, datahora, IDutl)
             VALUES ('Funcionário $IDfunc aprovou a conta $id', '$datahora', $IDfunc)"
        );

        header("Location: inscricoespendentes.php?sucesso=aprovado");
        exit();

    } elseif ($acao === "rejeitar") {

        mysqli_query($link, "DELETE FROM utilizador WHERE IDutl = $id");

        // Criar log
        date_default_timezone_set("Europe/Lisbon");
        $datahora = date("Y-m-d H:i:s");
        mysqli_query($link,
            "INSERT INTO logs (descricao, datahora, IDutl)
             VALUES ('Funcionário $IDfunc rejeitou a conta $id', '$datahora', $IDfunc)"
        );

        header("Location: inscricoespendentes.php?sucesso=rejeitado");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <title>Analisar Inscrição</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<!-- SCRIPT global de toast-->
<script>
    function mostrarMensagem(tipo, texto) {
        const box = document.getElementById("msgGlobal");
        const icon = document.getElementById("msgIcon");
        const msg = document.getElementById("msgTexto");

        // Limpar classes antigas
        box.classList.remove("border-blue-600", "border-green-600", "border-yellow-500", "border-red-600");
        msg.classList.remove("text-blue-600", "text-green-600", "text-yellow-500", "text-red-600");

        const icons = {
            adicionar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-blue-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m4.5 12.75 6 6 9-13.5" />
            </svg>`,

            editar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 
                        2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 
                        1.13L6 18l.8-2.685a4.5 4.5 0 0 1 
                        1.13-1.897l8.932-8.931Zm0 0L19.5 
                        7.125M18 14v4.75A2.25 2.25 0 0 1 
                        15.75 21H5.25A2.25 2.25 0 0 1 
                        3 18.75V8.25A2.25 2.25 0 0 1 
                        5.25 6H10" />
            </svg>`,

            reset: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-yellow-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 
                        3.374 1.948 3.374h14.71c1.73 0 
                        2.813-1.874 1.948-3.374L13.949 
                        3.378c-.866-1.5-3.032-1.5-3.898 
                        0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>`,

            eliminar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-red-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21
                        c.342.052.682.107 1.022.166m-1.022-.165L19.5 19.5
                        a2.25 2.25 0 0 1-2.244 2.25H6.744A2.25 2.25 0 0 1
                        4.5 19.5L5.772 5.79m14.456 0a48.108 48.108 0 0 0
                        -3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0
                        a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164
                        -2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09
                        1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>`
        };

        // Aplicar ícone
        icon.innerHTML = icons[tipo];
        msg.textContent = texto;

        // Aplicar cor do texto
        if (tipo === "adicionar") msg.classList.add("text-blue-600");
        if (tipo === "editar") msg.classList.add("text-green-600");
        if (tipo === "reset") msg.classList.add("text-yellow-500");
        if (tipo === "eliminar") msg.classList.add("text-red-600");

        // Aplicar cor da borda
        if (tipo === "adicionar") box.classList.add("border-blue-600");
        if (tipo === "editar") box.classList.add("border-green-600");
        if (tipo === "reset") box.classList.add("border-yellow-500");
        if (tipo === "eliminar") box.classList.add("border-red-600");

        // Mostrar
        box.classList.remove("hidden", "opacity-0");
        box.classList.add("opacity-100");

        // Ocultar após 3 segundos
        setTimeout(() => {
            box.classList.add("opacity-0");
            setTimeout(() => box.classList.add("hidden"), 300);
        }, 3000);
    }
</script>

<!-- Esconde o scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    scrollbar-width: none;
}
</style>

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">

    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal"
        class="hidden fixed top-5 right-5 bg-white dark:bg-gray-800 dark:text-gray-100 
               shadow-lg border-l-4 border-blue-500 dark:border-blue-400 
               rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_funcionario.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_funcionario.php"); ?>

        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Analisar Inscrição
            </h1>

            <a href="inscricoespendentes.php"
               class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                      rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>

            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <p><strong>Nome:</strong> <?= $u['nome'] ?></p>
                <p><strong>Email:</strong> <?= $u['email'] ?></p>
                <p><strong>Data Nascimento:</strong> <?= $u['datanascimento'] ?></p>
                <p><strong>Telefone:</strong> <?= $u['telefone'] ?></p>
                <p><strong>Tipo:</strong> <?= $u['tipo'] ?></p>

                <form method="post" class="mt-6 flex justify-between">

                    <a href="aprovar.php?id=<?= $u['IDutl'] ?>"
                       class="w-[40%] px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                              text-center rounded hover:bg-blue-700 dark:hover:bg-blue-600">
                        Aprovar
                    </a>

                    <a href="rejeitar.php?id=<?= $u['IDutl'] ?>"
                       class="w-[40%] px-4 py-2 bg-red-600 dark:bg-red-700 text-white 
                              text-center rounded hover:bg-red-700 dark:hover:bg-red-600">
                        Rejeitar
                    </a>

                </form>

            </div>
        </main>
    </div>
</body>
</html>
