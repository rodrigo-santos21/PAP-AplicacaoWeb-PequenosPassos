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
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Adicionar Reunião (Superadmin)</title>
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

<body class="bg-gray-100 dark:bg-gray-900 dark:text-gray-100 min-h-screen">

    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal" 
        class="hidden fixed top-5 right-5 bg-white dark:bg-gray-800 shadow-lg border-l-4 rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

    <!-- WRAPPER FLEX -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_superadmin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_superadmin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">Adicionar Reunião</h1>
    
            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <div id="erroBox" class="hidden bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200 p-3 rounded mb-4"></div>

                <form id="formReuniao" class="space-y-6">

                    <!-- CAMPOS BASE -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Título</label>
                        <input name="titulo" id="titulo" type="text"
                               class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded 
                                      bg-white dark:bg-gray-700 dark:text-gray-100" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Data e Hora</label>
                        <input name="datahora" id="datahora" type="datetime-local"
                               class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded 
                                      bg-white dark:bg-gray-700 dark:text-gray-100" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Localidade</label>
                        <input name="localidade" id="localidade" type="text"
                               class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded 
                                      bg-white dark:bg-gray-700 dark:text-gray-100" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Objetivo</label>
                        <textarea name="objetivo" id="objetivo" rows="4"
                                  class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded 
                                         bg-white dark:bg-gray-700 dark:text-gray-100" required></textarea>
                    </div>

                    <hr class="border-gray-300 dark:border-gray-700">

                    <!-- PARTICIPANTES -->
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Participantes</h3>

                    <!-- FUNCIONÁRIOS -->
                    <button type="button" onclick="toggle('sec_func')"
                        class="w-full bg-gray-200 dark:bg-gray-700 dark:text-gray-100 p-2 rounded">
                        Funcionários
                    </button>

                    <div id="sec_func" class="hidden p-3 border border-gray-300 dark:border-gray-600 rounded 
                                             bg-gray-50 dark:bg-gray-700">

                        <label class="text-gray-700 dark:text-gray-200">Selecionar:</label>
                        <select id="tipo_funcionario"
                                class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                                       bg-white dark:bg-gray-700 dark:text-gray-100">
                            <option value="">-- Escolher --</option>
                            <option value="todos">Todos os funcionários</option>
                            <option value="especificos">Selecionar específicos</option>
                        </select>

                        <div id="lista_funcionarios"
                             class="hidden border border-gray-300 dark:border-gray-600 p-3 rounded 
                                    bg-white dark:bg-gray-700 dark:text-gray-100">
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
                    <button type="button" onclick="toggle('sec_edu')"
                        class="w-full bg-gray-200 dark:bg-gray-700 dark:text-gray-100 p-2 rounded">
                        Educadores
                    </button>

                    <div id="sec_edu" class="hidden p-3 border border-gray-300 dark:border-gray-600 rounded 
                                             bg-gray-50 dark:bg-gray-700">

                        <label class="text-gray-700 dark:text-gray-200">Sala:</label>
                        <select id="sala_educador"
                                class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                                       bg-white dark:bg-gray-700 dark:text-gray-100">
                            <option value="">-- Escolher sala --</option>
                            <?php
                            $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                            while ($s = mysqli_fetch_assoc($salas)) {
                                echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                            }
                            ?>
                        </select>

                        <div id="lista_educadores"
                             class="hidden border border-gray-300 dark:border-gray-600 p-3 rounded 
                                    bg-white dark:bg-gray-700 dark:text-gray-100"></div>

                    </div>

                    <!-- ENCARREGADOS -->
                    <button type="button" onclick="toggle('sec_enc')"
                        class="w-full bg-gray-200 dark:bg-gray-700 dark:text-gray-100 p-2 rounded">
                        Encarregados
                    </button>

                    <div id="sec_enc" class="hidden p-3 border border-gray-300 dark:border-gray-600 rounded 
                                             bg-gray-50 dark:bg-gray-700">

                        <label class="text-gray-700 dark:text-gray-200">Sala:</label>
                        <select id="sala_encarregado"
                                class="border border-gray-300 dark:border-gray-600 p-2 rounded w-full mb-3 
                                       bg-white dark:bg-gray-700 dark:text-gray-100">
                            <option value="">-- Escolher sala --</option>
                            <?php
                            $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                            while ($s = mysqli_fetch_assoc($salas)) {
                                echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                            }
                            ?>
                        </select>

                        <div id="lista_encarregados"
                             class="hidden border border-gray-300 dark:border-gray-600 p-3 rounded 
                                    bg-white dark:bg-gray-700 dark:text-gray-100"></div>

                    </div>

                    <!-- BOTÕES -->
                    <div class="flex justify-between mt-6">
                        <a href="listarreusuper.php"
                           class="w-[40%] px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white text-center 
                                  hover:bg-gray-600 dark:hover:bg-gray-500 rounded">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="w-[40%] px-4 py-2 bg-green-600 dark:bg-green-700 text-white 
                                       hover:bg-green-700 dark:hover:bg-green-600 rounded">
                            Criar Reunião
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>
</body>

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
                    // Redireciona para o listar com flag de sucesso
                    window.location.href = "listarreusuper.php?sucesso=adicionado";
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
