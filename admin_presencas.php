<?php
session_start();
include "DBConnection.php";

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

/* ============================================================
   1) VERIFICAR PERMISSÕES (ADMIN)
   ============================================================ */

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'administrador') {
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
    <title>Presenças - Administrador</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="assets/fullcalendar/index.global.min.js"></script>
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
        <?php
            include("sidebar_admin.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Lista de presençasdas crianças da creche </h1>

            <a href="admin.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold mt-5 hover:bg-blue-700">
                ← Voltar
            </a>
            
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

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
                    <h3 class="text-xl font-bold mb-3">Detalhes da Falta</h3>

                    <p><b>Data:</b> <span id="faltData"></span></p>
                    <p><b>Justificação enviada:</b></p>
                    <textarea id="faltTexto" class="border p-2 w-full mb-3" readonly></textarea>

                    <p><b>Estado:</b> <span id="faltEstado"></span></p>

                    <button type="button" onclick="document.getElementById('modalFalta').style.display='none'"
                            style="background:#6b7280; color:white; padding:8px 12px; border-radius:5px;">
                        Fechar
                    </button>
                </div>
            </div>
        </main>
    </div>

<script>
<?php if ($id_crianca_sel): ?>

document.addEventListener('DOMContentLoaded', function() {

    let calendarEl = document.getElementById('calendar');

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        timeZone: 'local',

        events: function(fetchInfo, successCallback) {

            let id = "<?= $id_crianca_sel ?>";

            if (!id) {
                successCallback([]);
                return;
            }

            fetch("getPresencasAdmin.php?id_crianca=" + id)
                .then(r => r.json())
                .then(data => successCallback(data));
        },

        eventClick: function(info) {
            console.log(info.event);
            console.log(info.event.extendedProps);

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
                document.getElementById("faltEstado").innerText = info.event.extendedProps.estado ?? "-";
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
