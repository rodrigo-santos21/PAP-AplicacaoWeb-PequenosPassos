<?php
session_start();
include("DBConnection.php");

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault.png";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

/* ============================================================
   PROCESSAMENTO DO FORMULÁRIO
   ============================================================ */
$nome = $_POST['nome'] ?? "";
$datanascimento = $_POST['datanascimento'] ?? "";
$sexo = $_POST['sexo'] ?? "";
$observacoes = $_POST['observacoes'] ?? "";
$IDsala = $_POST['IDsala'] ?? "";
$IDutl = $_POST['IDutl'] ?? "";
$educadores = $_POST['educadores'] ?? [];
$criadopor = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // VALIDAÇÃO DA IDADE
    if (!empty($datanascimento)) {
        $idade = date_diff(date_create($datanascimento), date_create('today'))->y;
        if ($idade > 6) {
            $erro = "A criança não pode ter mais de 6 anos.";
        }
    }

    // VALIDAR SE PELO MENOS UM EDUCADOR FOI SELECIONADO
    if (empty($educadores)) {
        $erro = "Tem de selecionar pelo menos um educador.";
    }

    // Só continua se não houver erros
    if (!isset($erro)) {

        // Inserir criança
        $sql = "INSERT INTO crianca (nome, datanascimento, sexo, observacoes, IDutl, IDsala, estado, aprovado)
                VALUES (?, ?, ?, ?, ?, ?, 1, 1)";

        $stmt = mysqli_prepare($link, $sql);

        if (!$stmt) {
            die("Erro no prepare: " . mysqli_error($link));
        }

        mysqli_stmt_bind_param($stmt, "ssssii",
            $nome, $datanascimento, $sexo, $observacoes, $IDutl, $IDsala
        );

        if (mysqli_stmt_execute($stmt)) {

            $IDcri = mysqli_insert_id($link);

            // Associar educadores
            foreach ($educadores as $IDedu) {
                $stmt2 = mysqli_prepare($link,
                    "INSERT INTO crianca_educador (IDcri, IDedu, estado) VALUES (?, ?, 1)"
                );
                mysqli_stmt_bind_param($stmt2, "ii", $IDcri, $IDedu);
                mysqli_stmt_execute($stmt2);
            }

            // Criar log
            date_default_timezone_set("Europe/Lisbon");
            $fdatahora = date("Y-m-d H:i:s");

            mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                                 VALUES ('Adição de criança: $nome', '$fdatahora', '$criadopor')");

            header("Location: adicionarcri.php?sucesso=1");
            exit();
        } else {
            $erro = "Erro ao adicionar criança: " . mysqli_error($link);
        }
    }
}

/* ============================================================
   BUSCAR DADOS NECESSÁRIOS (SEM JOIN)
   ============================================================ */

// Buscar encarregados
$encarregados = mysqli_query($link,
    "SELECT IDutl, nome FROM utilizador WHERE tipo = 'encarregado' AND estado = 1"
);

// Buscar educadores (SEM JOIN)
$educadoresLista = mysqli_query($link,
    "SELECT IDedu, IDutl, IDsala 
     FROM educador 
     WHERE estado = 1"
);

// Criar array final com nome do educador SEM JOIN
$educadoresComNome = [];

while ($e = mysqli_fetch_assoc($educadoresLista)) {

    $IDutlEdu = intval($e['IDutl']);

    // Buscar nome do utilizador (SEM JOIN)
    $resNome = mysqli_query($link,
        "SELECT nome FROM utilizador WHERE IDutl = $IDutlEdu"
    );

    $nomeEdu = "—";
    if ($resNome && mysqli_num_rows($resNome) > 0) {
        $nomeEdu = mysqli_fetch_assoc($resNome)['nome'];
    }

    // Guardar tudo num array final
    $educadoresComNome[] = [
        "IDedu" => $e['IDedu'],
        "IDsala" => $e['IDsala'],
        "nome" => $nomeEdu
    ];
}

?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Criança</title>
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

            <h1 class="text-3xl font-bold text-gray-800 mb-8">Adicionar Criança</h1>    
                
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['sucesso'])): ?>
                    <div class="bg-green-200 text-green-800 p-3 rounded mb-4">
                        Criança adicionada com sucesso!
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <input name="nome" type="text"
                            value="<?= htmlspecialchars($nome) ?>"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                        <input name="datanascimento" type="date"
                            value="<?= $datanascimento ?>"
                            max="<?= date('Y-m-d', strtotime('-6 years')) ?>"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sexo</label>
                        <select name="sexo"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                            required>
                            <option value="">Selecionar...</option>
                            <option value="M" <?= $sexo === "M" ? "selected" : "" ?>>Masculino</option>
                            <option value="F" <?= $sexo === "F" ? "selected" : "" ?>>Feminino</option>
                            <option value="ND" <?= $sexo === "ND" ? "selected" : "" ?>>Prefere não divulgar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea name="observacoes" rows="3"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"><?= htmlspecialchars($observacoes) ?></textarea>
                    </div>

                    <!-- EDUCADORES (CHECKBOXES) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Educadores</label>
                        <div id="educadoresLista" class="mt-2 space-y-2">
                            <?php foreach ($educadoresComNome as $ed): ?>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" class="educadorCheck"
                                        data-idsala="<?= $ed['IDsala'] ?>"
                                        value="<?= $ed['IDedu'] ?>"
                                        name="educadores[]"
                                        <?= in_array($ed['IDedu'], $educadores) ? "checked" : "" ?>>
                                    <span><?= $ed['nome'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- SALA AUTOMÁTICA -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sala</label>
                        <input type="text" id="IDsala" name="IDsala"
                            value="<?= $IDsala ?>"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-200"
                            readonly required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Encarregado de Educação</label>
                        <select name="IDutl"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg"
                            required>
                            <option value="">Selecionar encarregado...</option>
                            <?php while ($e = mysqli_fetch_assoc($encarregados)): ?>
                                <option value="<?= $e['IDutl'] ?>" <?= $IDutl == $e['IDutl'] ? "selected" : "" ?>>
                                    <?= $e['nome'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="flex justify-between">
                        <a href="admin.php"
                            class="w-[40%] px-4 py-2 bg-gray-500 text-white text-center rounded-lg hover:bg-gray-600">
                            Cancelar
                        </a>

                        <button type="submit"
                            class="w-[40%] px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Adicionar
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

// Validação no cliente (opcional)
document.querySelector("form").addEventListener("submit", function(e) {
    const checks = document.querySelectorAll(".educadorCheck:checked");
    if (checks.length === 0) {
        alert("Tem de selecionar pelo menos um educador.");
        e.preventDefault();
    }
});
</script>

</body>
</html>
