<?php
session_start();
include "DBConnection.php";

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
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Presenças - Funcionário</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="assets/fullcalendar/index.global.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<body class="bg-gray-100 text-gray-900 min-h-screen 
    <?= ($tema ?? 'light') === 'dark'
        ? 'dark:bg-gray-900 dark:text-gray-100'
        : '' ?>">

    <!-- WRAPPER FLEX RESPONSIVO -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR (DESKTOP) -->
        <div class="hidden lg:block">
            <?php include("sidebar_funcionario.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_funcionario.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">
                Ver Presenças
            </h1>
    
            <a href="funcionario.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white 
                   rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>
            
            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <!-- FILTRO POR SALA -->
                <form method="get">
                    <label class="font-semibold dark:text-gray-200">Sala:</label>
                    <select name="sala" onchange="this.form.submit()"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full mb-4 
                               bg-white dark:bg-gray-900 dark:text-gray-100">
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

                    <label class="font-semibold dark:text-gray-200">Criança:</label>
                    <select name="crianca" onchange="this.form.submit()"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 rounded w-full mb-6 
                               bg-white dark:bg-gray-900 dark:text-gray-100">
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
                class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">

                <div class="bg-white dark:bg-gray-800 w-[350px] p-6 rounded-lg shadow-lg">

                    <h3 class="text-xl font-bold mb-3 dark:text-gray-100">Detalhes da Presença</h3>

                    <p class="dark:text-gray-200"><b>Data:</b> <span id="presData"></span></p>
                    <p class="dark:text-gray-200"><b>Entrada:</b> <span id="presEntrada"></span></p>
                    <p class="dark:text-gray-200"><b>Saída:</b> <span id="presSaida"></span></p>

                    <button onclick="document.getElementById('modalPresenca').style.display='none'"
                        class="bg-gray-600 dark:bg-gray-700 text-white px-4 py-2 rounded 
                               hover:bg-gray-700 dark:hover:bg-gray-600 mt-4">
                        Fechar
                    </button>

                </div>
            </div>

            <!-- MODAL FALTA -->
            <div id="modalFalta"
                class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">

                <div class="bg-white dark:bg-gray-800 w-[400px] p-6 rounded-lg shadow-lg">

                    <h3 class="text-xl font-bold mb-3 dark:text-gray-100">Justificação da Falta</h3>

                    <p class="dark:text-gray-200"><b>Data:</b> <span id="faltData"></span></p>
                    <p class="dark:text-gray-200"><b>Justificação enviada:</b></p>

                    <textarea id="faltTexto"
                        class="border border-gray-300 dark:border-gray-600 
                               p-2 w-full mb-3 bg-white dark:bg-gray-900 dark:text-gray-100"
                        readonly></textarea>

                    <form method="post" action="processarJustificacaoFuncionario.php">
                        <input type="hidden" name="idPres" id="faltIdPres">
                        <input type="hidden" name="sala" value="<?= $id_sala_sel ?>">
                        <input type="hidden" name="crianca" value="<?= $id_crianca_sel ?>">

                        <button name="acao" value="aceitar"
                            class="bg-green-600 dark:bg-green-700 text-white px-4 py-2 rounded 
                                   hover:bg-green-700 dark:hover:bg-green-600">
                            Aceitar
                        </button>

                        <button name="acao" value="recusar"
                            class="bg-red-600 dark:bg-red-700 text-white px-4 py-2 rounded 
                                   hover:bg-red-700 dark:hover:bg-red-600">
                            Recusar
                        </button>

                        <button type="button"
                            onclick="document.getElementById('modalFalta').style.display='none'"
                            class="bg-gray-600 dark:bg-gray-700 text-white px-4 py-2 rounded 
                                   hover:bg-gray-700 dark:hover:bg-gray-600">
                            Fechar
                        </button>
                    </form>

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
