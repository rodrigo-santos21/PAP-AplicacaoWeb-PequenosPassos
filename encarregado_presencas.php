<?php
session_start();
include "DBConnection.php";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit();
}

$id_encarregado = $_SESSION['id'];

// Buscar filhos
$sql = "SELECT * FROM crianca 
        WHERE IDutl = $id_encarregado 
          AND estado = 1";

$res = mysqli_query($link, $sql);

$criancas = [];
while ($row = mysqli_fetch_assoc($res)) {
    $criancas[] = $row;
}

// Justificação enviada
if (isset($_POST['acao']) && $_POST['acao'] === 'justificar') {

    $idPres = $_POST['idPres'];
    $justificacao = mysqli_real_escape_string($link, $_POST['justificacao']);
    $id_crianca_sel = $_POST['id_crianca_sel'];

    $sql = "UPDATE presenca 
            SET justificacao = '$justificacao',
                justificacao_estado = 'pendente'
            WHERE IDpre = $idPres";

    mysqli_query($link, $sql);

    header("Location: encarregado_presencas.php?id_crianca=$id_crianca_sel");
    exit();
}

$id_crianca_sel = $_GET['id_crianca'] ?? "";
?>
<html>
<head>
    <meta charset="utf-8">
    <title>Presenças e Faltas</title>
    <link rel="stylesheet" href="style.css">
    <script src="assets/fullcalendar/index.global.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">

    <h2 class="text-2xl font-bold mb-4">Presenças e Faltas</h2>

    <label class="font-semibold">Escolha a criança:</label>
    <select id="crianca" class="border p-2 rounded w-full mb-6">
        <option value="">-- Selecionar --</option>
        <?php foreach ($criancas as $c): ?>
            <option value="<?= $c['IDcri'] ?>" <?= ($id_crianca_sel == $c['IDcri']) ? 'selected' : '' ?>>
                <?= $c['nome'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div id="calendar"></div>

    <a href="encarregado.php"
       style="display:inline-block; margin-top:20px; padding:10px 18px; background:#2563eb; color:white; border-radius:6px;">
        ← Voltar
    </a>
</div>

<!-- MODAL JUSTIFICAÇÃO -->
<div id="modalJust" 
     style="
        display:none; 
        position:fixed; 
        top:0; 
        left:0; 
        width:100%; 
        height:100%; 
        background:rgba(0,0,0,0.5); 
        padding-top:100px;
        z-index:9999;
     ">
    <div style="
        background:white;
        width:400px;
        margin:auto;
        padding:20px;
        border-radius:8px;
        position:relative;
        z-index:10000;
    ">
        <h3 class="text-xl font-bold mb-3">Justificar falta</h3>

        <form method="post">
            <input type="hidden" name="acao" value="justificar">
            <input type="hidden" name="idPres" id="just_idPres">
            <input type="hidden" name="id_crianca_sel" id="just_idCri">

            <label>Justificação:</label>
            <textarea name="justificacao" rows="4" class="border p-2 w-full mb-3" required></textarea>

            <button type="submit" style="background:#16a34a; color:white; padding:8px 12px; border-radius:5px;">
                Enviar
            </button>

            <button type="button" onclick="fecharJustificacao()" 
                    style="background:#6b7280; color:white; padding:8px 12px; border-radius:5px;">
                Cancelar
            </button>
        </form>
    </div>
</div>

<!-- Modal PRESENÇA NORMAL -->
<div id="modalPresenca" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.5); padding-top:100px; z-index:9999;">
    <div style="background:white; width:350px; margin:auto; padding:20px; border-radius:8px; z-index:10000;">
        <h3 class="text-xl font-bold mb-3">Detalhes da Presença</h3>

        <p><b>Data:</b> <span id="modalPresencaData"></span></p>
        <p><b>Entrada:</b> <span id="modalPresencaEntrada"></span></p>
        <p><b>Saída:</b> <span id="modalPresencaSaida"></span></p>

        <button onclick="document.getElementById('modalPresenca').style.display='none'"
                style="background:#6b7280; color:white; padding:8px 12px; border-radius:5px; margin-top:15px;">
            Fechar
        </button>
    </div>
</div>


<script>
let calendar;

document.addEventListener('DOMContentLoaded', function() {

    let calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        timeZone: 'local',

        events: function(fetchInfo, successCallback) {

            let id_crianca = document.getElementById('crianca').value;

            if (!id_crianca) {
                successCallback([]);
                return;
            }

            fetch("getPresencasEncarregado.php?id_crianca=" + id_crianca)
                .then(r => r.json())
                .then(data => successCallback(data));
        },

        eventClick: function(info) {

            let tipo = info.event.extendedProps.tipo;

            // ============================
            // PRESENÇA → modal readonly
            // ============================
            if (tipo === "presenca") {

                let entrada = info.event.extendedProps.horaE || "-";
                let saida   = info.event.extendedProps.horaS || "-";
                let data    = info.event.startStr.substring(0, 10);

                document.getElementById("modalPresencaData").innerText = data;
                document.getElementById("modalPresencaEntrada").innerText = entrada;
                document.getElementById("modalPresencaSaida").innerText = saida;

                document.getElementById("modalPresenca").style.display = "block";
                return;
            }

            // ============================
            // FALTA → modal de justificação
            // ============================
            if (tipo === "falta") {

                document.getElementById("just_idPres").value = info.event.id;
                document.getElementById("just_idCri").value = document.getElementById("crianca").value;

                let justificacao = info.event.extendedProps.justificacao;
                let estado = info.event.extendedProps.estado;

                let textarea = document.querySelector("#modalJust textarea");
                let botaoEnviar = document.querySelector("#modalJust button[type='submit']");

                // Se já existe justificação → mostrar readonly
                if (justificacao) {
                    textarea.value = justificacao;
                    textarea.readOnly = true;

                    if (estado === "pendente") {
                        botaoEnviar.style.display = "none";
                    }
                    else if (estado === "aceite") {
                        botaoEnviar.style.display = "none";
                    }
                    else if (estado === "recusada") {
                        textarea.readOnly = false; // permitir reenviar
                        botaoEnviar.style.display = "inline-block";
                    }
                }
                else {
                    textarea.value = "";
                    textarea.readOnly = false;
                    botaoEnviar.style.display = "inline-block";
                }

                document.getElementById("modalJust").style.display = "block";
            }
        }

    });

    calendar.render();

    document.getElementById('crianca').addEventListener('change', function() {
        calendar.refetchEvents();
    });
});

function fecharJustificacao() {
    document.getElementById("modalJust").style.display = "none";
}
</script>

</body>
</html>
