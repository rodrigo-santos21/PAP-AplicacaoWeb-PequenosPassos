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

// Verificar ID
if (!isset($_GET['id'])) {
    header("Location: criancaspendentes.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar criança SEM filtrar por estado
$res = mysqli_query($link, "SELECT * FROM crianca WHERE IDcri = $id");
$c = mysqli_fetch_assoc($res);

if (!$c) {
    header("Location: criancaspendentes.php?erro=nao_existe");
    exit();
}

// Impedir aprovação se já estiver aprovada
if ($c['estado'] != 0) {
    header("Location: criancaspendentes.php?erro=ja_aprovada");
    exit();
}

// Buscar salas
$salas = mysqli_query($link, "SELECT * FROM sala WHERE estado = 1");

// Buscar educadores (SEM JOIN)
$educadores = mysqli_query($link, "SELECT * FROM educador WHERE estado = 1");

// Para cada educador, buscar o nome do utilizador
$listaEducadores = [];
while ($e = mysqli_fetch_assoc($educadores)) {

    $IDutl = $e['IDutl'];
    $resNome = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutl");
    $nome = mysqli_fetch_assoc($resNome)['nome'] ?? "Desconhecido";

    $e['nome'] = $nome;
    $listaEducadores[] = $e;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $IDsala = intval($_POST['IDsala']);
    $educs = $_POST['educadores'] ?? [];

    // ❌ NOVO: impedir aprovação sem educadores
    if (empty($educs)) {
        $erro = "Tem de selecionar pelo menos um educador para aprovar a criança.";
    }

    if (!isset($erro)) {

        // Atualizar criança
        mysqli_query($link,
            "UPDATE crianca 
             SET estado = 1, aprovado = 1, IDsala = $IDsala, analise_por = NULL
             WHERE IDcri = $id"
        );

        // Apagar associações antigas
        mysqli_query($link, "DELETE FROM crianca_educador WHERE IDcri = $id");

        // Inserir novas associações
        foreach ($educs as $e) {
            $e = intval($e);
            mysqli_query($link,
                "INSERT INTO crianca_educador (IDcri, IDedu, estado)
                 VALUES ($id, $e, 1)"
            );
        }

        // Log
        date_default_timezone_set("Europe/Lisbon");
        $datahora = date("Y-m-d H:i:s");

        mysqli_query($link,
            "INSERT INTO logs (descricao, datahora, IDutl)
             VALUES ('Funcionário $IDfunc aprovou a criança $id', '$datahora', $IDfunc)"
        );

        header("Location: criancaspendentes.php?sucesso=aprovado");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <title>Aprovar Criança</title>
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

    <!-- WRAPPER -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_funcionario.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_funcionario.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Aprovar Criança
            </h1>

            <a href="criancaspendentes.php"
               class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                      rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>

            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 dark:bg-red-700 text-red-800 dark:text-red-100 
                                p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <p><strong>Nome:</strong> <?= $c['nome'] ?></p>
                <p><strong>Data Nascimento:</strong> <?= $c['datanascimento'] ?></p>
                <p><strong>Sexo:</strong> <?= $c['sexo'] ?></p>
                <p><strong>Observações:</strong> <?= $c['observacoes'] ?></p>

                <form method="post" class="mt-6 space-y-4">

                    <!-- EDUCADORES -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Educadores
                        </label>

                        <div id="educadoresLista" class="mt-2 space-y-2 dark:text-gray-200">
                            <?php foreach ($listaEducadores as $e): ?>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="educadorCheck"
                                        data-idsala="<?= $e['IDsala'] ?>"
                                        value="<?= $e['IDedu'] ?>"
                                        name="educadores[]">
                                    <span><?= $e['nome'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- SALA -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Sala
                        </label>
                        <input type="text" id="IDsala" name="IDsala"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-gray-200 dark:bg-gray-700 dark:text-gray-100"
                            readonly required>
                    </div>

                    <!-- BOTÃO APROVAR -->
                    <button type="submit"
                        class="w-[40%] px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                               rounded hover:bg-blue-700 dark:hover:bg-blue-600 transition">
                        Aprovar
                    </button>

                </form>

            </div>
        </main>
    </div>

<script>
let salaSelecionada = null;

document.querySelectorAll(".educadorCheck").forEach(chk => {
    chk.addEventListener("change", function () {

        const salaEducador = this.dataset.idsala;

        // Primeiro educador define a sala
        if (salaSelecionada === null && this.checked) {
            salaSelecionada = salaEducador;
            document.getElementById("IDsala").value = salaEducador;
            return;
        }

        // Se tentar selecionar educador de outra sala
        if (this.checked && salaEducador !== salaSelecionada) {
            alert("Este educador pertence a outra sala. Só pode selecionar educadores da mesma sala.");
            this.checked = false;
            return;
        }

        // Se desmarcar todos → limpar sala
        const algumMarcado = [...document.querySelectorAll(".educadorCheck")]
            .some(c => c.checked);

        if (!algumMarcado) {
            salaSelecionada = null;
            document.getElementById("IDsala").value = "";
        }
    });
});

// ❌ NOVO: impedir submit sem educadores
document.querySelector("form").addEventListener("submit", function(e) {
    const algumMarcado = [...document.querySelectorAll(".educadorCheck")]
        .some(c => c.checked);

    if (!algumMarcado) {
        alert("Selecione pelo menos um educador.");
        e.preventDefault();
    }
});
</script>

<?php if (isset($erro)): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("reset", "<?= $erro ?>");
});
</script>
<?php endif; ?>

</body>
</html>
