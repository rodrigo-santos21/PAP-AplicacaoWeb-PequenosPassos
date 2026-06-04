<?php
session_start();
include("DBConnection.php");

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
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Aprovar Criança</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body class="bg-gray-100 min-h-screen p-8">

<div class="max-w-lg mx-auto bg-white shadow-lg rounded-lg p-6">

    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
        Aprovar Criança
    </h2>

    <?php if (isset($erro)): ?>
        <div class="bg-red-200 text-red-800 p-3 rounded mb-4">
            <?= $erro ?>
        </div>
    <?php endif; ?>

    <p><strong>Nome:</strong> <?= $c['nome'] ?></p>
    <p><strong>Data Nascimento:</strong> <?= $c['datanascimento'] ?></p>
    <p><strong>Sexo:</strong> <?= $c['sexo'] ?></p>
    <p><strong>Observações:</strong> <?= $c['observacoes'] ?></p>

    <form method="post" class="mt-6 space-y-4">

        <div>
            <label class="block text-sm font-medium text-gray-700">Educadores</label>
            <div id="educadoresLista" class="mt-2 space-y-2">
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

        <div>
            <label class="block text-sm font-medium text-gray-700">Sala</label>
            <input type="text" id="IDsala" name="IDsala"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-200"
                readonly required>
        </div>

        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Aprovar
        </button>

    </form>

    <div class="text-center mt-6">
        <a href="criancaspendentes.php"
           class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
            Voltar
        </a>
    </div>

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

</body>
</html>
