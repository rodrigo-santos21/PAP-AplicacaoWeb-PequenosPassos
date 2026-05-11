<?php
session_start();
include "DBConnection.php";

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
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Editar Criança
        </h2>

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
                    <option value="ND" <?= $crianca['sexo'] === "ND" ? "selected" : "" ?>>Prefere não divulgar</option>
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
                   class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Guardar Alterações
                </button>
            </div>

        </form>
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
