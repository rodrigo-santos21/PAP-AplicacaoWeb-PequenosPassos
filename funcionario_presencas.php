<?php
session_start();
include "DBConnection.php";

/* ============================================================
   1) VERIFICAR PERMISSÕES (FUNCIONÁRIO)
   ============================================================ */

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit();
}

/* ============================================================
   2) BUSCAR SALAS ATIVAS
   ============================================================ */

$sqlSalas = "SELECT * FROM sala WHERE estado = 1 ORDER BY nome";
$resSalas = mysqli_query($link, $sqlSalas);

$salas = [];
while ($row = mysqli_fetch_assoc($resSalas)) {
    $salas[] = $row;
}

/* ============================================================
   3) BUSCAR CRIANÇAS DA SALA SELECIONADA
   ============================================================ */

$id_sala_sel = $_GET['sala'] ?? "";
$id_crianca_sel = $_GET['crianca'] ?? "";

$criancas = [];

if ($id_sala_sel) {
    $sqlCri = "SELECT * FROM crianca 
               WHERE IDsala = $id_sala_sel 
                 AND estado = 1
               ORDER BY nome";

    $resCri = mysqli_query($link, $sqlCri);

    while ($row = mysqli_fetch_assoc($resCri)) {
        $criancas[] = $row;
    }
}

?>
<html>
<head>
    <meta charset="utf-8">
    <title>Presenças - Funcionário</title>
    <link rel="stylesheet" href="style.css">
    <script src="assets/fullcalendar/index.global.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-5xl mx-auto bg-white p-6 rounded-lg shadow">

    <h2 class="text-2xl font-bold mb-4">Gestão de Presenças</h2>

    <!-- FILTRO POR SALA -->
    <form method="get">
        <label class="font-semibold">Sala:</label>
        <select name="sala" onchange="this.form.submit()" class="border p-2 rounded w-full mb-4">
            <option value="">-- Selecionar Sala --</option>
            <?php foreach ($salas as $s): ?>
                <option value="<?= $s['IDsala'] ?>" <?= ($id_sala_sel == $s['IDsala']) ? 'selected' : '' ?>>
                    <?= $s['nome'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- FILTRO POR CRIANÇA -->
    <?php if ($id_sala_sel): ?>
    <form method="get">
        <input type="hidden" name="sala" value="<?= $id_sala_sel ?>">

        <label class="font-semibold">Criança:</label>
        <select name="crianca" onchange="this.form.submit()" class="border p-2 rounded w-full mb-6">
            <option value="">-- Selecionar Criança --</option>
            <?php foreach ($criancas as $c): ?>
                <option value="<?= $c['IDcri'] ?>" <?= ($id_crianca_sel == $c['IDcri']) ? 'selected' : '' ?>>
                    <?= $c['nome'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>

    <!-- CALENDÁRIO -->
    <?php if ($id_crianca_sel): ?>
        <div id="calendar"></div>
    <?php endif; ?>

    <a href="funcionario.php" 
    style="
        display:inline-block;
        padding:10px 18px;
        background:#2563eb;
        color:white;
        text-decoration:none;
        border-radius:6px;
        font-weight:600;
        margin-top:20px;
        font-family:Arial, sans-serif;
    ">
        ← Voltar
    </a>

</div>

<!-- MODAL PRESENÇA -->
<div id="modalPresenca" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.5); padding-top:100px; z-index:9999;">
    <div style="background:white; width:350px; margin:auto; padding:20px; border-radius:8px; z-index:10000;">
        <h3 class="text-xl font-bold mb-3">Detalhes da Presença</h3>

        <p><b>Data:</b> <span id="presData"></span></p>
        <p><b>Entrada:</b> <span id="presEntrada"></span></p>
        <p><b>Saída:</b> <span id="presSaida"></span></p>

        <button onclick="document.getElementById('modalPresenca').style.display='none'"
                style="background:#6b7280; color:white; padding:8px 12px; border-radius:5px; margin-top:15px;">
            Fechar
        </button>
    </div>
</div>

<!-- MODAL FALTA -->
<div id="modalFalta" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.5); padding-top:100px; z-index:9999;">
    <div style="background:white; width:400px; margin:auto; padding:20px; border-radius:8px; z-index:10000;">
        <h3 class="text-xl font-bold mb-3">Justificação da Falta</h3>

        <p><b>Data:</b> <span id="faltData"></span></p>
        <p><b>Justificação enviada:</b></p>
        <textarea id="faltTexto" class="border p-2 w-full mb-3" readonly></textarea>

        <form method="post" action="processarJustificacaoFuncionario.php">
            <input type="hidden" name="idPres" id="faltIdPres">
            <input type="hidden" name="sala" value="<?= $id_sala_sel ?>">
            <input type="hidden" name="crianca" value="<?= $id_crianca_sel ?>">

            <button name="acao" value="aceitar"
                    style="background:#16a34a; color:white; padding:8px 12px; border-radius:5px;">
                Aceitar
            </button>

            <button name="acao" value="recusar"
                    style="background:#dc2626; color:white; padding:8px 12px; border-radius:5px;">
                Recusar
            </button>

            <button type="button" onclick="document.getElementById('modalFalta').style.display='none'"
                    style="background:#6b7280; color:white; padding:8px 12px; border-radius:5px;">
                Fechar
            </button>
        </form>
    </div>
</div>

<script>
<?php if ($id_crianca_sel): ?>

document.addEventListener('DOMContentLoaded', function() {

    let calendarEl = document.getElementById('calendar');

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        timeZone: 'local',

        events: "getPresencasFuncionario.php?id_crianca=<?= $id_crianca_sel ?>",

        eventClick: function(info) {

            let tipo = info.event.extendedProps.tipo;

            // PRESENÇA
            if (tipo === "presenca") {
                document.getElementById("presData").innerText = info.event.startStr.substring(0,10);
                document.getElementById("presEntrada").innerText = info.event.extendedProps.horaE;
                document.getElementById("presSaida").innerText = info.event.extendedProps.horaS ?? "-";
                document.getElementById("modalPresenca").style.display = "block";
                return;
            }

            // FALTA
            if (tipo === "falta") {
                document.getElementById("faltData").innerText = info.event.startStr.substring(0,10);
                document.getElementById("faltTexto").value = info.event.extendedProps.justificacao ?? "Sem justificação";
                document.getElementById("faltIdPres").value = info.event.id;
                document.getElementById("modalFalta").style.display = "block";
            }
        }
    });

    calendar.render();
});

<?php endif; ?>
</script>

</body>
</html>
