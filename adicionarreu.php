<?php
session_start();
include("DBConnection.php");

// Apenas administradores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

// Variáveis auxiliares para recarregar selects
$selectedSalaEdu = $_POST['sala_educador'] ?? "";
$selectedSalaEnc = $_POST['sala_encarregado'] ?? "";
$tipoFuncionario = $_POST['tipo_funcionario'] ?? "";

// Guardar estado das checkboxes após refresh
$checkedFuncionarios = $_POST['funcionarios_especificos'] ?? [];
$checkedEducadores   = $_POST['educadores_sala'] ?? [];
$checkedEncarregados = $_POST['encarregados_sala'] ?? [];

// Controlar quais secções devem estar abertas após refresh
$showFunc = !empty($tipoFuncionario);
$showEdu  = !empty($selectedSalaEdu);
$showEnc  = !empty($selectedSalaEnc);

// PROCESSAR SUBMISSÃO FINAL
if (isset($_POST['criar_reuniao'])) {

    $titulo = $_POST['titulo'];
    $datahora = $_POST['datahora'];
    $localidade = $_POST['localidade'];
    $objetivo = $_POST['objetivo'];
    $criadopor = $_SESSION['id'];

    // Inserir reunião
    $sql = "INSERT INTO reuniao (titulo, datahora, localidade, objetivo, criadopor)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $titulo, $datahora, $localidade, $objetivo, $criadopor);

    if (mysqli_stmt_execute($stmt)) {

        $IDreu = mysqli_insert_id($link);

        // ============================
        // FUNCIONÁRIOS
        // ============================
        if (!empty($tipoFuncionario)) {

            // Todos os funcionários
            if ($tipoFuncionario === "todos") {
                $res = mysqli_query($link, "SELECT IDutl FROM utilizador WHERE tipo='funcionario' AND estado=1");
                while ($u = mysqli_fetch_assoc($res)) {
                    mysqli_query($link, "INSERT INTO reuniao_participante (IDreu, IDutl) VALUES ($IDreu, {$u['IDutl']})");
                }
            }

            // Funcionários específicos
            if ($tipoFuncionario === "especificos" && !empty($checkedFuncionarios)) {
                foreach ($checkedFuncionarios as $IDutl) {
                    mysqli_query($link, "INSERT INTO reuniao_participante (IDreu, IDutl) VALUES ($IDreu, $IDutl)");
                }
            }
        }

        // ============================
        // EDUCADORES
        // ============================
        if (!empty($selectedSalaEdu) && !empty($checkedEducadores)) {
            foreach ($checkedEducadores as $IDutl) {
                mysqli_query($link, "INSERT INTO reuniao_participante (IDreu, IDutl) VALUES ($IDreu, $IDutl)");
            }
        }

        // ============================
        // ENCARREGADOS
        // ============================
        if (!empty($selectedSalaEnc) && !empty($checkedEncarregados)) {
            foreach ($checkedEncarregados as $IDutl) {
                mysqli_query($link, "INSERT INTO reuniao_participante (IDreu, IDutl) VALUES ($IDreu, $IDutl)");
            }
        }

        // LOG
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Admin criou reunião (ID $IDreu)', '$fdatahora', '$criadopor')");

        header("Location: listarreu.php?sucesso=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar reunião: " . mysqli_error($link);
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Reunião</title>
    <link rel="stylesheet" href="style.css">

    <script>
        function toggleSection(id) {
            const sec = document.getElementById(id);
            sec.style.display = sec.style.display === "none" ? "block" : "none";
        }
    </script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-2xl bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Reunião</h2>

        <?php if (isset($erro)): ?>
            <div class="bg-red-200 text-red-800 p-3 rounded mb-4"><?= $erro ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-6">

            <!-- CAMPOS BASE -->
            <div>
                <label class="block text-sm font-medium">Título</label>
                <input name="titulo" type="text" class="w-full border p-2 rounded"
                        value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Data e Hora</label>
                <input name="datahora" type="datetime-local" class="w-full border p-2 rounded"
                        value="<?= $_POST['datahora'] ?? '' ?>" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Localidade</label>
                <input name="localidade" type="text" class="w-full border p-2 rounded"
                        value="<?= htmlspecialchars($_POST['localidade'] ?? '') ?>" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Objetivo</label>
                <textarea name="objetivo" rows="4" class="w-full border p-2 rounded" required><?= 
                    htmlspecialchars($_POST['objetivo'] ?? '') 
                ?></textarea>
            </div>

            <hr>

            <!-- PARTICIPANTES -->
            <h3 class="text-lg font-semibold">Participantes</h3>

            <!-- FUNCIONÁRIOS -->
            <button type="button" onclick="toggleSection('sec_func')" class="w-full bg-gray-200 p-2 rounded">
                Funcionários
            </button>
            <div id="sec_func" style="display:<?= $showFunc ? 'block' : 'none' ?>" class="p-3 border rounded">

                <label>Selecionar:</label>
                <select name="tipo_funcionario" class="border p-2 rounded w-full mb-3" onchange="this.form.submit()">
                    <option value="">-- Escolher --</option>
                    <option value="todos" <?= ($tipoFuncionario=='todos') ? 'selected' : '' ?>>Todos os funcionários</option>
                    <option value="especificos" <?= ($tipoFuncionario=='especificos') ? 'selected' : '' ?>>Selecionar específicos</option>
                </select>

                <?php
                if ($tipoFuncionario === "especificos") {
                    $res = mysqli_query($link, "SELECT IDutl, nome FROM utilizador WHERE tipo='funcionario' AND estado=1");
                    while ($u = mysqli_fetch_assoc($res)) {

                        $checked = in_array($u['IDutl'], $checkedFuncionarios) ? "checked" : "";

                        echo "<label class='block ml-2'>
                                <input type='checkbox' name='funcionarios_especificos[]' value='{$u['IDutl']}' $checked>
                                {$u['nome']}
                              </label>";
                    }
                }
                ?>
            </div>

            <!-- EDUCADORES -->
            <button type="button" onclick="toggleSection('sec_edu')" class="w-full bg-gray-200 p-2 rounded">
                Educadores
            </button>
            <div id="sec_edu" style="display:<?= $showEdu ? 'block' : 'none' ?>" class="p-3 border rounded">

                <label>Sala:</label>
                <select name="sala_educador" class="border p-2 rounded w-full mb-3" onchange="this.form.submit()">
                    <option value="">-- Escolher sala --</option>
                    <?php
                    $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                    while ($s = mysqli_fetch_assoc($salas)) {
                        $sel = ($selectedSalaEdu == $s['IDsala']) ? "selected" : "";
                        echo "<option value='{$s['IDsala']}' $sel>{$s['nome']}</option>";
                    }
                    ?>
                </select>

                <?php
                if ($selectedSalaEdu) {

                    $resEdu = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDsala = $selectedSalaEdu AND estado = 1");

                    while ($e = mysqli_fetch_assoc($resEdu)) {

                        $IDutl = $e['IDutl'];

                        $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutl AND estado = 1");
                        $u = mysqli_fetch_assoc($resU);

                        $checked = in_array($IDutl, $checkedEducadores) ? "checked" : "";

                        echo "<label class='block ml-2'>
                                <input type='checkbox' name='educadores_sala[]' value='$IDutl' $checked>
                                {$u['nome']}
                              </label>";
                    }
                }
                ?>
            </div>

            <!-- ENCARREGADOS -->
            <button type="button" onclick="toggleSection('sec_enc')" class="w-full bg-gray-200 p-2 rounded">
                Encarregados
            </button>
            <div id="sec_enc" style="display:<?= $showEnc ? 'block' : 'none' ?>" class="p-3 border rounded">

                <label>Sala:</label>
                <select name="sala_encarregado" class="border p-2 rounded w-full mb-3" onchange="this.form.submit()">
                    <option value="">-- Escolher sala --</option>
                    <?php
                    $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                    while ($s = mysqli_fetch_assoc($salas)) {
                        $sel = ($selectedSalaEnc == $s['IDsala']) ? "selected" : "";
                        echo "<option value='{$s['IDsala']}' $sel>{$s['nome']}</option>";
                    }
                    ?>
                </select>

                <?php
                if ($selectedSalaEnc) {

                    $resCri = mysqli_query($link, "SELECT IDutl FROM crianca WHERE IDsala = $selectedSalaEnc AND estado = 1");

                    $encarregadosMostrados = [];

                    while ($c = mysqli_fetch_assoc($resCri)) {

                        $IDutl = $c['IDutl'];

                        if (in_array($IDutl, $encarregadosMostrados)) {
                            continue;
                        }
                        $encarregadosMostrados[] = $IDutl;

                        $resU = mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutl AND estado = 1");
                        $u = mysqli_fetch_assoc($resU);

                        $checked = in_array($IDutl, $checkedEncarregados) ? "checked" : "";

                        echo "<label class='block ml-2'>
                                <input type='checkbox' name='encarregados_sala[]' value='$IDutl' $checked>
                                {$u['nome']}
                              </label>";
                    }
                }
                ?>
            </div>

            <div class="flex justify-between mt-6">
                <a href="admin.php" class="px-4 py-2 bg-gray-500 text-white rounded">Cancelar</a>
                <button name="criar_reuniao" class="px-4 py-2 bg-blue-600 text-white rounded">Criar Reunião</button>
            </div>

        </form>
    </div>
</body>
</html>
