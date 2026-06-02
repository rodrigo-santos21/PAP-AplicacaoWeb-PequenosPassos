<?php
session_start();
include("DBConnection.php");

// Apenas superadmins podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit;
}

// PROCESSAR SUBMISSÃO FINAL (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = $_POST['titulo'];
    $datahora = $_POST['datahora'];
    $localidade = $_POST['localidade'];
    $objetivo = $_POST['objetivo'];
    $criadopor = $_SESSION['id'];

    $func = $_POST['funcionarios'] ?? [];
    $edu  = $_POST['educadores'] ?? [];
    $enc  = $_POST['encarregados'] ?? [];

    // TRATAR "TODOS OS FUNCIONÁRIOS"
    if (is_array($func) && in_array("todos", $func)) {

        $func = [];

        $res = mysqli_query($link, "SELECT IDutl FROM utilizador WHERE tipo='funcionario' AND estado=1");

        while ($row = mysqli_fetch_assoc($res)) {
            $func[] = $row['IDutl'];
        }
    }

    // Inserir reunião
    $sql = "INSERT INTO reuniao (titulo, datahora, localidade, objetivo, criadopor)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $titulo, $datahora, $localidade, $objetivo, $criadopor);

    if (mysqli_stmt_execute($stmt)) {

        $IDreu = mysqli_insert_id($link);

        // Participantes (sem duplicados)
        $todos = array_unique(array_merge($func, $edu, $enc));

        foreach ($todos as $IDutl) {
            mysqli_query($link, "INSERT INTO reuniao_participante (IDreu, IDutl, estado) VALUES ($IDreu, $IDutl, 1)");
        }

        // LOG
        date_default_timezone_set("Europe/Lisbon");
        $fdatahora = date("Y-m-d H:i:s");

        mysqli_query($link, "INSERT INTO logs (descricao, datahora, IDutl)
                             VALUES ('Superadmin criou reunião (ID $IDreu)', '$fdatahora', '$criadopor')");

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'erro' => mysqli_error($link)]);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER EDUCADORES POR SALA
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getEducadores') {
    $sala = (int)$_GET['sala'];

    $res = mysqli_query($link, "SELECT IDutl FROM educador WHERE IDsala = $sala AND estado = 1");

    while ($e = mysqli_fetch_assoc($res)) {
        $IDutl = $e['IDutl'];
        $u = mysqli_fetch_assoc(mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutl AND estado = 1"));

        echo "<label class='block ml-2'>
                <input type='checkbox' class='chk-edu' value='$IDutl'>
                {$u['nome']}
              </label>";
    }
    exit;
}

/* ============================================================
   AJAX: DEVOLVER ENCARREGADOS POR SALA
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getEncarregados') {
    $sala = (int)$_GET['sala'];

    $res = mysqli_query($link, "SELECT IDutl FROM crianca WHERE IDsala = $sala AND estado = 1");

    $mostrados = [];

    while ($c = mysqli_fetch_assoc($res)) {
        $IDutl = $c['IDutl'];

        if (in_array($IDutl, $mostrados)) continue;
        $mostrados[] = $IDutl;

        $u = mysqli_fetch_assoc(mysqli_query($link, "SELECT nome FROM utilizador WHERE IDutl = $IDutl AND estado = 1"));

        echo "<label class='block ml-2'>
                <input type='checkbox' class='chk-enc' value='$IDutl'>
                {$u['nome']}
              </label>";
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Reunião (Superadmin)</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-2xl bg-white rounded-lg shadow-md p-8">

        <h2 class="text-xl font-bold text-gray-800 mb-6">Adicionar Reunião (Superadmin)</h2>

        <div id="erroBox" class="hidden bg-red-200 text-red-800 p-3 rounded mb-4"></div>

        <form id="formReuniao" class="space-y-6">

            <!-- CAMPOS BASE -->
            <div>
                <label class="block text-sm font-medium">Título</label>
                <input name="titulo" id="titulo" type="text" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Data e Hora</label>
                <input name="datahora" id="datahora" type="datetime-local" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Localidade</label>
                <input name="localidade" id="localidade" type="text" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Objetivo</label>
                <textarea name="objetivo" id="objetivo" rows="4" class="w-full border p-2 rounded" required></textarea>
            </div>

            <hr>

            <!-- PARTICIPANTES -->
            <h3 class="text-lg font-semibold">Participantes</h3>

            <!-- FUNCIONÁRIOS -->
            <button type="button" onclick="toggle('sec_func')" class="w-full bg-gray-200 p-2 rounded">
                Funcionários
            </button>
            <div id="sec_func" class="hidden p-3 border rounded">

                <label>Selecionar:</label>
                <select id="tipo_funcionario" class="border p-2 rounded w-full mb-3">
                    <option value="">-- Escolher --</option>
                    <option value="todos">Todos os funcionários</option>
                    <option value="especificos">Selecionar específicos</option>
                </select>

                <div id="lista_funcionarios" class="hidden border p-3 rounded">
                    <?php
                    $res = mysqli_query($link, "SELECT IDutl, nome FROM utilizador WHERE tipo='funcionario' AND estado=1");
                    while ($u = mysqli_fetch_assoc($res)) {
                        echo "<label class='block ml-2'>
                                <input type='checkbox' class='chk-func' value='{$u['IDutl']}'>
                                {$u['nome']}
                              </label>";
                    }
                    ?>
                </div>

            </div>

            <!-- EDUCADORES -->
            <button type="button" onclick="toggle('sec_edu')" class="w-full bg-gray-200 p-2 rounded">
                Educadores
            </button>
            <div id="sec_edu" class="hidden p-3 border rounded">

                <label>Sala:</label>
                <select id="sala_educador" class="border p-2 rounded w-full mb-3">
                    <option value="">-- Escolher sala --</option>
                    <?php
                    $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                    while ($s = mysqli_fetch_assoc($salas)) {
                        echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                    }
                    ?>
                </select>

                <div id="lista_educadores" class="hidden border p-3 rounded"></div>

            </div>

            <!-- ENCARREGADOS -->
            <button type="button" onclick="toggle('sec_enc')" class="w-full bg-gray-200 p-2 rounded">
                Encarregados
            </button>
            <div id="sec_enc" class="hidden p-3 border rounded">

                <label>Sala:</label>
                <select id="sala_encarregado" class="border p-2 rounded w-full mb-3">
                    <option value="">-- Escolher sala --</option>
                    <?php
                    $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                    while ($s = mysqli_fetch_assoc($salas)) {
                        echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                    }
                    ?>
                </select>

                <div id="lista_encarregados" class="hidden border p-3 rounded"></div>

            </div>

            <div class="flex justify-between mt-6">
                <a href="superadmin.php" class="px-4 py-2 bg-gray-500 text-white rounded">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Criar Reunião</button>
            </div>

        </form>
    </div>

<script>

function toggle(id) {
    document.getElementById(id).classList.toggle("hidden");
}

let selecionadosEducadores = [];
let selecionadosEncarregados = [];

document.addEventListener("DOMContentLoaded", () => {

    /* ============================================================
       FUNCIONÁRIOS
       ============================================================ */
    const tipoFunc = document.getElementById("tipo_funcionario");
    const listaFunc = document.getElementById("lista_funcionarios");

    tipoFunc.addEventListener("change", () => {
        if (tipoFunc.value === "especificos") {
            listaFunc.classList.remove("hidden");
        } else {
            listaFunc.classList.add("hidden");
        }
    });

    /* ============================================================
       EDUCADORES — MUDAR SALA
       ============================================================ */
    document.getElementById("sala_educador").addEventListener("change", function () {
        const sala = this.value;
        const box = document.getElementById("lista_educadores");

        if (!sala) {
            box.innerHTML = "";
            box.classList.add("hidden");
            return;
        }

        fetch("adicionarreusuper.php?action=getEducadores&sala=" + sala)
            .then(r => r.text())
            .then(html => {
                box.innerHTML = html;
                box.classList.remove("hidden");

                box.querySelectorAll('.chk-edu').forEach(chk => {
                    if (selecionadosEducadores.includes(chk.value)) chk.checked = true;

                    chk.addEventListener("change", function () {
                        if (this.checked) {
                            if (!selecionadosEducadores.includes(this.value)) {
                                selecionadosEducadores.push(this.value);
                            }
                        } else {
                            selecionadosEducadores = selecionadosEducadores.filter(id => id !== this.value);
                        }
                    });
                });
            });
    });

    /* ============================================================
       ENCARREGADOS — MUDAR SALA
       ============================================================ */
    document.getElementById("sala_encarregado").addEventListener("change", function () {
        const sala = this.value;
        const box = document.getElementById("lista_encarregados");

        if (!sala) {
            box.innerHTML = "";
            box.classList.add("hidden");
            return;
        }

        fetch("adicionarreusuper.php?action=getEncarregados&sala=" + sala)
            .then(r => r.text())
            .then(html => {
                box.innerHTML = html;
                box.classList.remove("hidden");

                box.querySelectorAll('.chk-enc').forEach(chk => {
                    if (selecionadosEncarregados.includes(chk.value)) chk.checked = true;

                    chk.addEventListener("change", function () {
                        if (this.checked) {
                            if (!selecionadosEncarregados.includes(this.value)) {
                                selecionadosEncarregados.push(this.value);
                            }
                        } else {
                            selecionadosEncarregados = selecionadosEncarregados.filter(id => id !== this.value);
                        }
                    });
                });
            });
    });

    /* ============================================================
       SUBMETER FORMULÁRIO (AJAX)
       ============================================================ */
    document.getElementById("formReuniao").addEventListener("submit", function (e) {
        e.preventDefault();

        let funcionarios = [];
        const tipoFunc = document.getElementById("tipo_funcionario").value;

        if (tipoFunc === "todos") {
            funcionarios = ["todos"];
        } else {
            document.querySelectorAll('.chk-func:checked').forEach(chk => funcionarios.push(chk.value));
        }

        const educadores = [...new Set(selecionadosEducadores)];
        const encarregados = [...new Set(selecionadosEncarregados)];

        const formData = new URLSearchParams();
        formData.append("titulo", document.getElementById("titulo").value);
        formData.append("datahora", document.getElementById("datahora").value);
        formData.append("localidade", document.getElementById("localidade").value);
        formData.append("objetivo", document.getElementById("objetivo").value);

        funcionarios.forEach(v => formData.append("funcionarios[]", v));
        educadores.forEach(v => formData.append("educadores[]", v));
        encarregados.forEach(v => formData.append("encarregados[]", v));

        fetch("adicionarreusuper.php?action=criar", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: formData.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert("Reunião criada com sucesso!");
                window.location.href = "listarreu.php";
            } else {
                document.getElementById("erroBox").innerText = data.erro;
                document.getElementById("erroBox").classList.remove("hidden");
            }
        });
    });

});
</script>

</body>
</html>
