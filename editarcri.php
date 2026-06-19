<?php
session_start();
include "DBConnection.php";

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

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit();
}

// Verificar se veio um ID pela URL
if (!isset($_GET['id'])) {
    header("Location: listacri.php?erro=sem_id");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados da criança
$stmt = mysqli_prepare($link, "SELECT * FROM crianca WHERE IDcri = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$crianca = mysqli_fetch_assoc($result);

// Se não existir
if (!$crianca) {
    header("Location: listacri.php?erro=nao_existe");
    exit();
}

// Buscar educadores (AGORA COM IDsala)
$educadores = mysqli_query($link,
    "SELECT educador.IDedu, educador.IDsala, educador.IDutl
     FROM educador
     WHERE educador.estado = 1"
);

// Buscar educadores associados
$educadoresAssociados = [];
$resAssoc = mysqli_query($link, "SELECT IDedu FROM crianca_educador WHERE IDcri = $id AND estado = 1");
while ($row = mysqli_fetch_assoc($resAssoc)) {
    $educadoresAssociados[] = $row['IDedu'];
}

// PROCESSAR ATUALIZAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $datanascimento = $_POST['datanascimento'];
    $sexo = $_POST['sexo'];
    $observacoes = $_POST['observacoes'];
    $IDsala = $_POST['IDsala'];
    $criadopor = $_SESSION['id'];

    // VALIDAÇÃO DA IDADE
    $idade = date_diff(date_create($datanascimento), date_create('today'))->y;

    if ($idade > 6) {
        $erro = "A criança não pode ter mais de 6 anos.";
    }

    // Atualizar criança
    $stmt = mysqli_prepare($link,
        "UPDATE crianca 
         SET nome=?, datanascimento=?, sexo=?, observacoes=?, IDsala=? 
         WHERE IDcri=?"
    );

    mysqli_stmt_bind_param($stmt, "ssssii",
        $nome, $datanascimento, $sexo, $observacoes, $IDsala, $id
    );

    $success = mysqli_stmt_execute($stmt);

    if ($success) {

        // Lista de educadores selecionados no formulário
        $educadoresSelecionados = $_POST['educadores'] ?? [];

        // VALIDAR SE PELO MENOS UM EDUCADOR FOI SELECIONADO
        if (empty($educadoresSelecionados)) {
            $erro = "Tem de selecionar pelo menos um educador.";
        } else {

            // Buscar educadores já associados (ativos ou inativos)
            $educadoresExistentes = [];
            $res = mysqli_query($link, "SELECT IDedu, estado FROM crianca_educador WHERE IDcri = $id");
            while ($row = mysqli_fetch_assoc($res)) {
                $educadoresExistentes[$row['IDedu']] = $row['estado']; // 1 ou 0
            }

            // 1) DESATIVAR educadores que foram desmarcados
            foreach ($educadoresExistentes as $IDedu => $estadoAtual) {
                if (!in_array($IDedu, $educadoresSelecionados)) {
                    mysqli_query($link, "
                        UPDATE crianca_educador 
                        SET estado = 0 
                        WHERE IDcri = $id AND IDedu = $IDedu
                    ");
                }
            }

            // 2) ATIVAR educadores que já existiam mas estavam desativados
            foreach ($educadoresSelecionados as $IDedu) {
                if (isset($educadoresExistentes[$IDedu])) {
                    mysqli_query($link, "
                        UPDATE crianca_educador 
                        SET estado = 1 
                        WHERE IDcri = $id AND IDedu = $IDedu
                    ");
                } else {
                    mysqli_query($link, "
                        INSERT INTO crianca_educador (IDcri, IDedu, estado)
                        VALUES ($id, $IDedu, 1)
                    ");
                }
            }

            // Registar log
            date_default_timezone_set("Europe/Lisbon");
            $fdatahora = date("Y-m-d H:i:s");

            mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                VALUES ('Edição da criança (ID $id)', '$fdatahora', '$criadopor')");

            header("Location: listacri.php?sucesso=editado");
            exit();
        }
    } else {
        $erro = "Erro ao atualizar criança: " . mysqli_error($link);
    }
}
?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Editar Criança</title>
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

<script>
document.querySelector("form").addEventListener("submit", function(e) {
    const checks = document.querySelectorAll(".educadorCheck:checked");
    if (checks.length === 0) {
        alert("Tem de selecionar pelo menos um educador.");
        e.preventDefault();
    }
});
</script>

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
            <?php include("sidebar_admin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_admin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Editar Criança
            </h1>

            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <form method="post" class="space-y-5">

                    <!-- NOME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nome
                        </label>
                        <input type="text" name="nome" value="<?= $crianca['nome'] ?>"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-900 dark:text-gray-100"
                            required>
                    </div>

                    <!-- DATA NASCIMENTO -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Data de Nascimento
                        </label>
                        <input type="date" name="datanascimento"
                            value="<?= $crianca['datanascimento'] ?>"
                            max="<?= date('Y-m-d', strtotime('-6 years')) ?>"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-900 dark:text-gray-100"
                            required>
                    </div>

                    <!-- SEXO -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Sexo
                        </label>
                        <select name="sexo"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-900 dark:text-gray-100">
                            <option value="M" <?= $crianca['sexo'] === "M" ? "selected" : "" ?>>Masculino</option>
                            <option value="F" <?= $crianca['sexo'] === "F" ? "selected" : "" ?>>Feminino</option>
                            <option value="N" <?= $crianca['sexo'] === "N" ? "selected" : "" ?>>Prefere não divulgar</option>
                        </select>
                    </div>

                    <!-- EDUCADORES -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Educadores
                        </label>

                        <div class="mt-2 space-y-2 dark:text-gray-200">

                            <?php mysqli_data_seek($educadores, 0); ?>
                            <?php while ($ed = mysqli_fetch_assoc($educadores)): ?>

                                <?php
                                $resNome = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = {$ed['IDutl']}");
                                $nomeEdu = mysqli_fetch_assoc($resNome)['nome'] ?? "Educador removido";
                                ?>

                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="educadorCheck"
                                        data-idsala="<?= $ed['IDsala'] ?>"
                                        value="<?= $ed['IDedu'] ?>"
                                        name="educadores[]"
                                        <?= in_array($ed['IDedu'], $educadoresAssociados) ? "checked" : "" ?>>
                                    <span><?= $nomeEdu ?></span>
                                </label>

                            <?php endwhile; ?>

                        </div>
                    </div>

                    <!-- SALA -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Sala
                        </label>
                        <input type="text" id="IDsala" name="IDsala"
                            value="<?= $crianca['IDsala'] ?>"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-gray-200 dark:bg-gray-700 dark:text-gray-100"
                            readonly required>
                    </div>

                    <!-- OBSERVAÇÕES -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Observações
                        </label>
                        <textarea name="observacoes" rows="4"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
                                   rounded-lg bg-white dark:bg-gray-900 dark:text-gray-100"><?= $crianca['observacoes'] ?></textarea>
                    </div>

                    <!-- BOTÕES -->
                    <div class="flex justify-between mt-6">

                        <a href="listacri.php"
                            class="w-[40%] px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white 
                                   text-center rounded-lg hover:bg-gray-600 dark:hover:bg-gray-500 transition">
                            Cancelar
                        </a>

                        <button type="submit"
                            class="w-[40%] px-4 py-2 bg-green-600 dark:bg-green-700 text-white 
                                   rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition">
                            Guardar Alterações
                        </button>

                    </div>

                </form>

            </div>
        </main>
    </div>

<!-- SCRIPT PARA VALIDAR EDUCADORES E DEFINIR SALA -->
<script>
let salaSelecionada = document.getElementById("IDsala").value || null;

document.querySelectorAll(".educadorCheck").forEach(chk => {
    chk.addEventListener("change", function () {

        const salaEducador = this.dataset.idsala;

        // Primeiro educador define a sala
        if (salaSelecionada === "" || salaSelecionada === null) {
            if (this.checked) {
                salaSelecionada = salaEducador;
                document.getElementById("IDsala").value = salaEducador;
            }
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
</script>

<!-- TOAST -->
<?php if (isset($erro)): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("reset", "<?= $erro ?>");
});
</script>
<?php endif; ?>

</body>
</html>
