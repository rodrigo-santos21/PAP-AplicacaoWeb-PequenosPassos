<?php
session_start();
include "DBConnection.php";

// Verifica se o utilizador é administrador
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'administrador') {
    header("Location: index.php?erro=permissao");
    exit;
}

/* ============================================================
   AJAX: DEVOLVER EVENTOS PARA O FULLCALENDAR
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'events') {
    header('Content-Type: application/json; charset=utf-8');

    $events = [];
    $res = mysqli_query($link, "SELECT IDreu, titulo, datahora FROM reuniao WHERE estado = 1");

    while ($r = mysqli_fetch_assoc($res)) {
        $events[] = [
            'id'    => $r['IDreu'],
            'title' => $r['titulo'],
            'start' => $r['datahora']
        ];
    }

    echo json_encode($events);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER DADOS DA REUNIÃO (INCLUINDO PARTICIPANTES)
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)$_GET['id'];

    // Dados base
    $stmt = mysqli_prepare($link, "SELECT IDreu, titulo, datahora, localidade, objetivo FROM reuniao WHERE IDreu = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $reu = mysqli_fetch_assoc($res);

    if (!$reu) {
        echo json_encode(['error' => 'Reunião não encontrada']);
        exit;
    }

    // Participantes agrupados por tipo
    $func = [];
    $edu  = [];
    $enc  = [];

    $resP = mysqli_query($link, "SELECT IDutl FROM reuniao_participante WHERE IDreu = $id AND estado = 1");

    while ($p = mysqli_fetch_assoc($resP)) {
        $IDutl = $p['IDutl'];

        $u = mysqli_fetch_assoc(mysqli_query($link, "SELECT tipo FROM utilizador WHERE IDutl = $IDutl AND estado = 1"));

        if (!$u) continue;

        if ($u['tipo'] === 'funcionario') $func[] = $IDutl;
        if ($u['tipo'] === 'educador')   $edu[]  = $IDutl;
        if ($u['tipo'] === 'encarregado') $enc[] = $IDutl;
    }

    echo json_encode([
        'id'           => $reu['IDreu'],
        'titulo'       => $reu['titulo'],
        'datahora'     => $reu['datahora'],
        'localidade'   => $reu['localidade'],
        'objetivo'     => $reu['objetivo'],
        'funcionarios' => $func,
        'educadores'   => $edu,
        'encarregados' => $enc
    ]);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER SALA DE UM EDUCATOR
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getSalaEducador') {
    $id = (int)$_GET['id'];

    $res = mysqli_query($link, "SELECT IDsala FROM educador WHERE IDutl = $id AND estado = 1");
    $row = mysqli_fetch_assoc($res);

    echo json_encode(['sala' => $row ? $row['IDsala'] : null]);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER SALA DE UM ENCARREGADO
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'getSalaEncarregado') {
    $id = (int)$_GET['id'];

    $res = mysqli_query($link, "SELECT IDsala FROM crianca WHERE IDutl = $id AND estado = 1");
    $row = mysqli_fetch_assoc($res);

    echo json_encode(['sala' => $row ? $row['IDsala'] : null]);
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

/* ============================================================
   AJAX: ATUALIZAR REUNIÃO
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $id         = (int)$_POST['id'];
    $titulo     = $_POST['titulo'];
    $datahora   = $_POST['datahora'];
    $localidade = $_POST['localidade'];
    $objetivo   = $_POST['objetivo'];

    $func = $_POST['funcionarios'] ?? [];
    $edu  = $_POST['educadores'] ?? [];
    $enc  = $_POST['encarregados'] ?? [];

    /* ============================================================
       FUNCIONÁRIOS — SE O ADMIN ESCOLHEU "TODOS"
       ============================================================ */
    if (isset($_POST['funcionario_tipo']) && $_POST['funcionario_tipo'] === "todos") {

        $func = [];
        $res = mysqli_query($link, "SELECT IDutl FROM utilizador WHERE tipo='funcionario' AND estado=1");

        while ($row = mysqli_fetch_assoc($res)) {
            $func[] = $row['IDutl'];
        }
    }

    mysqli_query($link, "UPDATE reuniao SET titulo='$titulo', datahora='$datahora', localidade='$localidade', objetivo='$objetivo' WHERE IDreu=$id");

    mysqli_query($link, "DELETE FROM reuniao_participante WHERE IDreu = $id");

    $todos = array_unique(array_merge($func, $edu, $enc));
    foreach ($todos as $IDutl) {
        mysqli_query($link, "INSERT INTO reuniao_participante (IDreu, IDutl) VALUES ($id, $IDutl)");
    }

    echo json_encode(['success' => true]);
    exit;
}

/* ============================================================
   AJAX: ELIMINAR REUNIÃO
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)$_POST['id'];

    mysqli_query($link, "DELETE FROM reuniao_participante WHERE IDreu = $id");
    mysqli_query($link, "UPDATE reuniao SET estado = 0 WHERE IDreu = $id");

    echo json_encode(['success' => true]);
    exit;
}

/* ============================================================
   CARREGAR LISTA DE FUNCIONÁRIOS
   ============================================================ */
$listaFuncionarios = [];
$resF = mysqli_query($link, "SELECT IDutl, nome FROM utilizador WHERE tipo='funcionario' AND estado=1");
while ($f = mysqli_fetch_assoc($resF)) $listaFuncionarios[] = $f;

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Listar Reuniões</title>
    <link rel="stylesheet" href="style.css">

    <!-- FullCalendar local -->
    <script src="http://localhost/PAP/PAP-AplicacaoWeb-PequenosPassos/assets/fullcalendar/index.global.min.js"></script>

    <style>
        #modalReuniao { 
            z-index: 999999 !important; 
            position: fixed !important; 
        }
        .fc { 
            z-index: 1 !important; 
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-5xl mx-auto mt-10 bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">Página do Administrador</h1>
        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">Reuniões (Calendário)</h3>

        <div id="calendar"></div>

        <div class="mt-6 text-center">
            <a href="admin.php" class="px-6 py-2 bg-blue-600 text-white rounded-lg">Página Inicial</a>
        </div>
    </div>

    <!-- MODAL -->
    <div id="modalReuniao" class="hidden inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

            <h2 class="text-xl font-bold mb-4">Editar Reunião</h2>

            <form id="formReuniao" class="space-y-4">
                <input type="hidden" id="reu_id">

                <!-- CAMPOS BASE -->
                <div>
                    <label class="block text-sm font-medium">Título</label>
                    <input type="text" id="reu_titulo" class="w-full border p-2 rounded" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Data e Hora</label>
                    <input type="datetime-local" id="reu_datahora" class="w-full border p-2 rounded" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Localidade</label>
                    <input type="text" id="reu_localidade" class="w-full border p-2 rounded" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Objetivo</label>
                    <textarea id="reu_objetivo" rows="3" class="w-full border p-2 rounded" required></textarea>
                </div>

                <hr>

                <!-- PARTICIPANTES -->
                <h3 class="text-lg font-semibold mb-3">Participantes</h3>

                <!-- BOTÕES -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <button type="button" id="btn_func" class="p-3 bg-gray-200 rounded text-center font-semibold hover:bg-gray-300">
                        Funcionários
                    </button>

                    <button type="button" id="btn_edu" class="p-3 bg-gray-200 rounded text-center font-semibold hover:bg-gray-300">
                        Educadores
                    </button>

                    <button type="button" id="btn_enc" class="p-3 bg-gray-200 rounded text-center font-semibold hover:bg-gray-300">
                        Encarregados
                    </button>
                </div>

                <!-- FUNCIONÁRIOS -->
                <div id="sec_func" class="hidden border p-4 rounded mb-4">

                    <label class="block font-medium">Selecionar:</label>
                    <select id="funcionario_tipo" class="border p-2 rounded w-full mb-3">
                        <option value="">-- Escolher --</option>
                        <option value="todos">Todos os funcionários</option>
                        <option value="especificos">Selecionar específicos</option>
                    </select>

                    <div id="funcionario_lista" 
                         class="hidden border p-3 rounded"
                         data-total="<?= count($listaFuncionarios) ?>">

                        <?php foreach ($listaFuncionarios as $f): ?>
                            <label class="block ml-2">
                                <input type="checkbox" class="chk-func" value="<?= $f['IDutl'] ?>">
                                <?= htmlspecialchars($f['nome']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                </div>

                <!-- EDUCADORES -->
                <div id="sec_edu" class="hidden border p-4 rounded mb-4">

                    <label class="block font-medium">Sala:</label>
                    <select id="educador_sala" class="border p-2 rounded w-full mb-3">
                        <option value="">-- Escolher sala --</option>
                        <?php
                        $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                        while ($s = mysqli_fetch_assoc($salas)) {
                            echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                        }
                        ?>
                    </select>

                    <div id="educador_lista" class="hidden border p-3 rounded"></div>

                </div>

                <!-- ENCARREGADOS -->
                <div id="sec_enc" class="hidden border p-4 rounded mb-4">

                    <label class="block font-medium">Sala:</label>
                    <select id="encarregado_sala" class="border p-2 rounded w-full mb-3">
                        <option value="">-- Escolher sala --</option>
                        <?php
                        $salas = mysqli_query($link, "SELECT IDsala, nome FROM sala WHERE estado=1");
                        while ($s = mysqli_fetch_assoc($salas)) {
                            echo "<option value='{$s['IDsala']}'>{$s['nome']}</option>";
                        }
                        ?>
                    </select>

                    <div id="encarregado_lista" class="hidden border p-3 rounded"></div>

                </div>

                <!-- BOTÕES -->
                <div class="flex justify-between mt-4">
                    <button type="button" id="btnEliminar"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Eliminar
                    </button>

                    <div class="flex gap-2">
                        <button type="button" onclick="fecharModal()"
                                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Guardar
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
<script>
/* ============================================================
   FUNÇÕES DO MODAL
   ============================================================ */

function abrirModal() {
    document.getElementById('modalReuniao').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modalReuniao').classList.add('hidden');
}

/* ============================================================
   FULLCALENDAR
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        height: 'auto',

        events: 'listarreu.php?action=events',

        eventClick: function (info) {
            const id = info.event.id;

            fetch('listarreu.php?action=get&id=' + id)
                .then(r => r.json())
                .then(data => {

                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    /* ============================================================
                       PREENCHER CAMPOS BASE
                       ============================================================ */
                    document.getElementById('reu_id').value = data.id;
                    document.getElementById('reu_titulo').value = data.titulo;
                    document.getElementById('reu_localidade').value = data.localidade;
                    document.getElementById('reu_objetivo').value = data.objetivo;

                    const dt = data.datahora.replace(' ', 'T').slice(0, 16);
                    document.getElementById('reu_datahora').value = dt;

                    /* ============================================================
                       LIMPAR LISTAS
                       ============================================================ */
                    document.querySelectorAll('.chk-func').forEach(chk => chk.checked = false);
                    document.getElementById("educador_lista").innerHTML = "";
                    document.getElementById("encarregado_lista").innerHTML = "";

                    /* ============================================================
                       FUNCIONÁRIOS — MARCAR AUTOMATICAMENTE
                       ============================================================ */
                    const idsFunc = data.funcionarios.map(String); // garantir tudo como string

                    document.querySelectorAll('.chk-func').forEach(chk => {
                        if (idsFunc.includes(chk.value)) {
                            chk.checked = true;
                        }
                    });
                    
                    /* ============================================================
                       FUNCIONÁRIOS — DETETAR "TODOS" OU "ESPECÍFICOS"
                       ============================================================ */
                    const selFuncTipo   = document.getElementById('funcionario_tipo');
                    const boxFuncLista  = document.getElementById('funcionario_lista');
                    const totalFunc     = parseInt(boxFuncLista.dataset.total, 10);
                    const selecionados  = data.funcionarios.length;

                    if (selecionados === totalFunc) {
                        selFuncTipo.value = "todos";
                        boxFuncLista.classList.add('hidden');
                    } else if (selecionados > 0) {
                        selFuncTipo.value = "especificos";
                        boxFuncLista.classList.remove('hidden');
                    } else {
                        selFuncTipo.value = "";
                        boxFuncLista.classList.add('hidden');
                    }

                    /* FUNCIONÁRIOS — MARCAR AUTOMATICAMENTE (DEPOIS DE MOSTRAR/ESCONDER) */
                    setTimeout(() => {
                        document.querySelectorAll('.chk-func').forEach(chk => {
                            if (data.funcionarios.includes(parseInt(chk.value))) {
                                chk.checked = true;
                            }
                        });
                    }, 10);

                    /* ============================================================
                       EDUCADORES — CARREGAR SALA AUTOMATICAMENTE
                       ============================================================ */
                    if (data.educadores.length > 0) {

                        fetch("listarreu.php?action=getSalaEducador&id=" + data.educadores[0])
                            .then(r => r.json())
                            .then(salaData => {

                                const sala = salaData.sala;
                                document.getElementById("educador_sala").value = sala;

                                fetch("listarreu.php?action=getEducadores&sala=" + sala)
                                    .then(r => r.text())
                                    .then(html => {
                                        const box = document.getElementById("educador_lista");
                                        box.innerHTML = html;
                                        box.classList.remove("hidden");

                                        data.educadores.forEach(id => {
                                            const chk = box.querySelector(`input[value="${id}"]`);
                                            if (chk) chk.checked = true;
                                        });
                                    });
                            });
                    }

                    /* ============================================================
                       ENCARREGADOS — CARREGAR SALA AUTOMATICAMENTE
                       ============================================================ */
                    if (data.encarregados.length > 0) {

                        fetch("listarreu.php?action=getSalaEncarregado&id=" + data.encarregados[0])
                            .then(r => r.json())
                            .then(salaData => {

                                const sala = salaData.sala;
                                document.getElementById("encarregado_sala").value = sala;

                                fetch("listarreu.php?action=getEncarregados&sala=" + sala)
                                    .then(r => r.text())
                                    .then(html => {
                                        const box = document.getElementById("encarregado_lista");
                                        box.innerHTML = html;
                                        box.classList.remove("hidden");

                                        data.encarregados.forEach(id => {
                                            const chk = box.querySelector(`input[value="${id}"]`);
                                            if (chk) chk.checked = true;
                                        });
                                    });
                            });
                    }

                    abrirModal();
                });
        }
    });

    calendar.render();

    /* ============================================================
       SUBMETER EDIÇÃO
       ============================================================ */

    document.getElementById('formReuniao').addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('reu_id').value;
        const titulo = document.getElementById('reu_titulo').value;
        const datahora = document.getElementById('reu_datahora').value;
        const localidade = document.getElementById('reu_localidade').value;
        const objetivo = document.getElementById('reu_objetivo').value;

        const funcionarios = [];
        const educadores = [];
        const encarregados = [];

        document.querySelectorAll('.chk-func:checked').forEach(chk => funcionarios.push(chk.value));
        document.querySelectorAll('.chk-edu:checked').forEach(chk => educadores.push(chk.value));
        document.querySelectorAll('.chk-enc:checked').forEach(chk => encarregados.push(chk.value));

        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('titulo', titulo);
        formData.append('datahora', datahora);
        formData.append('localidade', localidade);
        formData.append('objetivo', objetivo);

        // IMPORTANTE: enviar o tipo selecionado
        formData.append('funcionario_tipo', document.getElementById('funcionario_tipo').value);

        funcionarios.forEach(v => formData.append('funcionarios[]', v));
        educadores.forEach(v => formData.append('educadores[]', v));
        encarregados.forEach(v => formData.append('encarregados[]', v));

        fetch('listarreu.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Reunião atualizada com sucesso.');
                    fecharModal();
                    calendar.refetchEvents();
                } else {
                    alert('Erro ao atualizar reunião.');
                }
            });
    });

    /* ============================================================
       ELIMINAR REUNIÃO
       ============================================================ */

    document.getElementById('btnEliminar').addEventListener('click', function () {
        const id = document.getElementById('reu_id').value;

        if (!confirm('Tem a certeza que deseja eliminar esta reunião?')) return;

        const formData = new URLSearchParams();
        formData.append('id', id);

        fetch('listarreu.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Reunião eliminada com sucesso.');
                    fecharModal();
                    calendar.refetchEvents();
                } else {
                    alert('Erro ao eliminar reunião.');
                }
            });
    });

});

/* ============================================================
   BOTÕES DE PARTICIPANTES
   ============================================================ */

const sec_func = document.getElementById("sec_func");
const sec_edu  = document.getElementById("sec_edu");
const sec_enc  = document.getElementById("sec_enc");

document.getElementById("btn_func").onclick = () => {
    sec_func.classList.toggle("hidden");
    sec_edu.classList.add("hidden");
    sec_enc.classList.add("hidden");
};

document.getElementById("btn_edu").onclick = () => {
    sec_edu.classList.toggle("hidden");
    sec_func.classList.add("hidden");
    sec_enc.classList.add("hidden");
};

document.getElementById("btn_enc").onclick = () => {
    sec_enc.classList.toggle("hidden");
    sec_func.classList.add("hidden");
    sec_edu.classList.add("hidden");
};

/* ============================================================
   FUNCIONÁRIOS — mostrar checkboxes se escolher "específicos"
   ============================================================ */

document.getElementById("funcionario_tipo").addEventListener("change", function () {
    const lista = document.getElementById("funcionario_lista");
    lista.classList.toggle("hidden", this.value !== "especificos");
});
</script>

</body>
</html>
