<?php
session_start();
include "DBConnection.php";

// Verifica se o utilizador é encarregado de educação
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDEE = $_SESSION['id'];

/* ============================================================
   AJAX: DEVOLVER EVENTOS DO EE PARA O FULLCALENDAR (SEM JOIN)
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'events') {
    header('Content-Type: application/json; charset=utf-8');

    $events = [];

    // 1) Buscar todas as reuniões onde o EE participa
    $resIDs = mysqli_query($link, "
        SELECT IDreu 
        FROM reuniao_participante 
        WHERE IDutl = $IDEE
    ");

    while ($row = mysqli_fetch_assoc($resIDs)) {

        $IDREU = $row['IDreu'];

        // 2) Buscar dados da reunião (sem JOIN)
        $r = mysqli_fetch_assoc(mysqli_query($link, "
            SELECT IDreu, titulo, datahora 
            FROM reuniao 
            WHERE IDreu = $IDREU AND estado = 1
        "));

        if ($r) {
            $events[] = [
                'id'    => $r['IDreu'],
                'title' => $r['titulo'],
                'start' => $r['datahora']
            ];
        }
    }

    echo json_encode($events);
    exit;
}

/* ============================================================
   AJAX: DEVOLVER DADOS DA REUNIÃO (APENAS LEITURA)
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)$_GET['id'];

    // Verificar se o EE pertence à reunião
    $check = mysqli_query($link, "
        SELECT 1 FROM reuniao_participante 
        WHERE IDreu = $id AND IDutl = $IDEE
    ");

    if (mysqli_num_rows($check) === 0) {
        echo json_encode(['error' => 'Não tem permissão para ver esta reunião.']);
        exit;
    }

    // Dados base da reunião
    $reu = mysqli_fetch_assoc(mysqli_query($link, "
        SELECT IDreu, titulo, datahora, localidade, objetivo
        FROM reuniao 
        WHERE IDreu = $id
    "));

    /* ============================================================
       PARTICIPANTES — DEVOLVER NOMES (SEM JOIN)
       ============================================================ */

    // Funcionários
    $funcionarios = [];
    $res = mysqli_query($link, "
        SELECT IDutl FROM reuniao_participante 
        WHERE IDreu=$id AND estado=1
    ");
    while ($p = mysqli_fetch_assoc($res)) {
        $IDUTL = $p['IDutl'];

        $u = mysqli_fetch_assoc(mysqli_query($link, "
            SELECT nome, tipo FROM utilizador WHERE IDutl=$IDUTL
        "));

        if ($u['tipo'] === 'funcionario') {
            $funcionarios[] = $u['nome'];
        }
    }

    // Educadores
    $educadores = [];
    $res = mysqli_query($link, "
        SELECT IDutl FROM reuniao_participante 
        WHERE IDreu=$id AND estado=1
    ");
    while ($p = mysqli_fetch_assoc($res)) {
        $IDUTL = $p['IDutl'];

        $u = mysqli_fetch_assoc(mysqli_query($link, "
            SELECT nome, tipo FROM utilizador WHERE IDutl=$IDUTL
        "));

        if ($u['tipo'] === 'educador') {
            $educadores[] = $u['nome'];
        }
    }

    // Encarregados
    $encarregados = [];
    $res = mysqli_query($link, "
        SELECT IDutl FROM reuniao_participante 
        WHERE IDreu=$id AND estado=1
    ");
    while ($p = mysqli_fetch_assoc($res)) {
        $IDUTL = $p['IDutl'];

        $u = mysqli_fetch_assoc(mysqli_query($link, "
            SELECT nome, tipo FROM utilizador WHERE IDutl=$IDUTL
        "));

        if ($u['tipo'] === 'encarregado') {
            $encarregados[] = $u['nome'];
        }
    }

    echo json_encode([
        'id'            => $reu['IDreu'],
        'titulo'        => $reu['titulo'],
        'datahora'      => $reu['datahora'],
        'localidade'    => $reu['localidade'],
        'objetivo'      => $reu['objetivo'],
        'funcionarios'  => $funcionarios,
        'educadores'    => $educadores,
        'encarregados'  => $encarregados
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Reuniões — Encarregado de Educação</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <!-- FullCalendar -->
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
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-4">
            Reuniões do Encarregado de Educação
        </h1>

        <h3 class="text-xl font-semibold text-center text-gray-600 mb-6">
            Apenas reuniões onde está associado
        </h3>

        <div id="calendar"></div>

        <div class="mt-6 text-center">
            <a href="encarregado.php"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-block">
                Página Inicial
            </a>
        </div>
    </div>

    <!-- MODAL -->
    <div id="modalReuniao" class="hidden inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

            <h2 class="text-xl font-bold mb-4">Detalhes da Reunião</h2>

            <form class="space-y-4">
                <input type="hidden" id="reu_id">

                <!-- CAMPOS BASE (APENAS LEITURA) -->
                <div>
                    <label class="block text-sm font-medium">Título</label>
                    <input type="text" id="reu_titulo" class="w-full border p-2 rounded bg-gray-100" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium">Data e Hora</label>
                    <input type="text" id="reu_datahora" class="w-full border p-2 rounded bg-gray-100" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium">Localidade</label>
                    <input type="text" id="reu_localidade" class="w-full border p-2 rounded bg-gray-100" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium">Objetivo</label>
                    <textarea id="reu_objetivo" rows="3" class="w-full border p-2 rounded bg-gray-100" readonly></textarea>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <label class="font-semibold">Funcionários:</label>
                    <ul id="lista_funcionarios" class="list-disc ml-6 text-gray-700"></ul>
                </div>

                <div class="mb-3">
                    <label class="font-semibold">Educadores:</label>
                    <ul id="lista_educadores" class="list-disc ml-6 text-gray-700"></ul>
                </div>

                <div class="mb-3">
                    <label class="font-semibold">Encarregados:</label>
                    <ul id="lista_encarregados" class="list-disc ml-6 text-gray-700"></ul>
                </div>

                <!-- BOTÃO FECHAR -->
                <div class="flex justify-end mt-4">
                    <button type="button" onclick="fecharModal()"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Fechar
                    </button>
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

        events: 'listarreuee.php?action=events',

        eventClick: function (info) {
            const id = info.event.id;

            fetch('listarreuee.php?action=get&id=' + id)
                .then(r => r.json())
                .then(data => {

                    document.getElementById('reu_id').value = data.id;
                    document.getElementById('reu_titulo').value = data.titulo;
                    document.getElementById('reu_localidade').value = data.localidade;
                    document.getElementById('reu_objetivo').value = data.objetivo;
                    document.getElementById('reu_datahora').value = data.datahora;

                    // limpar listas
                    document.getElementById("lista_funcionarios").innerHTML = "";
                    document.getElementById("lista_educadores").innerHTML = "";
                    document.getElementById("lista_encarregados").innerHTML = "";

                    // preencher funcionários
                    data.funcionarios.forEach(nome => {
                        document.getElementById("lista_funcionarios").innerHTML += `<li>${nome}</li>`;
                    });

                    // preencher educadores
                    data.educadores.forEach(nome => {
                        document.getElementById("lista_educadores").innerHTML += `<li>${nome}</li>`;
                    });

                    // preencher encarregados
                    data.encarregados.forEach(nome => {
                        document.getElementById("lista_encarregados").innerHTML += `<li>${nome}</li>`;
                    });

                    abrirModal();
                });
        }
    });

    calendar.render();
});
</script>

</body>
</html>
