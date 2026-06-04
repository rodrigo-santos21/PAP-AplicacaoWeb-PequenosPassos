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

$fotoPerfil = $foto ? $foto : "imagens/perfildefault.png";

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
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Editar Criança</title>
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

<script>
document.querySelector("form").addEventListener("submit", function(e) {
    const checks = document.querySelectorAll(".educadorCheck:checked");
    if (checks.length === 0) {
        alert("Tem de selecionar pelo menos um educador.");
        e.preventDefault();
    }
});
</script>

<body class="bg-gray-100 min-h-screen">

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside class="w-1/5 bg-white shadow-lg p-6 flex flex-col justify-between fixed left-0 top-0 h-screen overflow-y-auto no-scrollbar">

            <!-- LOGO + TEXTO -->
            <div class="flex items-center space-x-3 mb-8">
                <a href="admin.php" class="flex items-center space-x-3">
                <img src="imagens/logo.png" class="w-18 h-12 object-cover rounded-lg" alt="Logo">
                <span class="text-2xl font-bold text-blue-400">Pequenos Passos</span>
                </a>
            </div>

            <div class="border-t-2 border-blue-400 pt-8">

            <!-- MENU -->
            <?php $pagina = basename($_SERVER['PHP_SELF']); ?> <!-- Devolve a página atual-->

            <nav class="space-y-3 flex-1">
                <a href="admin.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'admin.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Página Inicial
                </a>

                <a href="adicionarutl.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarutl.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Utilizador
                </a>

                <a href="listarutl.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarutl.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Lista Utilizadores
                </a>

                <a href="adicionaratv.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionaratv.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Atividade
                </a>

                <a href="listaratv.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listaratv.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Atividades
                </a>

                <a href="adicionarreu.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarreu.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Reunião
                </a>

                <a href="listarreu.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarreu.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Reuniões
                </a>

                <a href="adicionarsala.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarsala.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Sala
                </a>

                <a href="listarsala.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listarsala.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Salas
                </a>

                <a href="adicionarcri.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'adicionarcri.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Adicionar Criança
                </a>

                <a href="listacri.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listacri.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Crianças
                </a>

                <a href="listaroco.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'listaroco.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Listar Ocorrências
                </a>

                <a href="admin_presencas.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'admin_presencas.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Presenças
                </a>

                <a href="logs.php"
                class="flex items-center px-2 py-2 font-bold 
                <?= $pagina === 'logs.php' ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?> 
                rounded-md transition">
                Consultar Logs
                </a>
            </nav>

            <!-- PERFIL + LOGOUT -->
            <div class="mt-8 border-t-2 border-blue-400 pt-6">

                <!-- PERFIL (AGORA É UM LINK) -->
                <a href="perfil.php"
                class="flex items-center space-x-3 mb-4 px-2 py-2 rounded-md transition
                <?= $pagina === 'perfil.php' 
                        ? 'text-blue-600 bg-gray-100 border-l-4 border-blue-600' 
                        : 'text-gray-700 hover:text-blue-600 hover:bg-gray-100' ?>">

                    <img src="<?= $fotoPerfil ?>" class="w-12 h-12 rounded-full object-cover border" alt="Foto de Perfil">

                    <div>
                        <p class="font-semibold text-gray-800 truncate max-w-[180px]"><?= $_SESSION['user']; ?></p>
                        <p class="text-sm text-gray-500">Administrador</p>
                    </div>
                </a>

                <!-- LOGOUT -->
                <a href="logout.php"
                class="flex items-center justify-center gap-2 w-full text-center px-4 py-2 
                        bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-log-out">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Terminar Sessão
                </a>
            </div>
        </aside>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Editar Criança </h1>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="nome" value="<?= $crianca['nome'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                        <input type="date" name="datanascimento" value="<?= $crianca['datanascimento'] ?>" max="<?= date('Y-m-d', strtotime('-6 years')) ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sexo</label>
                        <select name="sexo" class="mt-1 w-full px-4 py-2 border rounded-lg">
                            <option value="M" <?= $crianca['sexo'] === "M" ? "selected" : "" ?>>Masculino</option>
                            <option value="F" <?= $crianca['sexo'] === "F" ? "selected" : "" ?>>Feminino</option>
                            <option value="N" <?= $crianca['sexo'] === "N" ? "selected" : "" ?>>Prefere não divulgar</option>
                        </select>
                    </div>

                    <!-- EDUCADORES (CHECKBOXES) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Educadores</label>
                        <div class="mt-2 space-y-2">

                            <?php mysqli_data_seek($educadores, 0); ?>
                            <?php while ($ed = mysqli_fetch_assoc($educadores)): ?>

                                <?php
                                // Buscar nome do educador
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

                    <!-- SALA AUTOMÁTICA -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sala</label>
                        <input type="text" id="IDsala" name="IDsala"
                            value="<?= $crianca['IDsala'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg bg-gray-200"
                            readonly required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea name="observacoes" rows="4"
                            class="mt-1 w-full px-4 py-2 border rounded-lg"><?= $crianca['observacoes'] ?></textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="listacri.php"
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

</body>
</html>
