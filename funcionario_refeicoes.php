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

// Apenas funcionários podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'funcionario') {
    header("Location: index.php?erro=permissao");
    exit;
}

// Buscar todas as salas ativas
$salas = mysqli_query($link, "
    SELECT * FROM sala
    WHERE estado = 1
    ORDER BY nome
");

// Ler filtros
$salaSelecionada = $_GET['sala'] ?? "";
$criancaSelecionada = $_GET['crianca'] ?? "";

// Data base da semana
$hoje = date('Y-m-d');
$base = $_GET['data'] ?? $hoje;

// Encontrar segunda-feira da semana
$diaSemana = date('N', strtotime($base));
$segunda = date('Y-m-d', strtotime($base . " -" . ($diaSemana - 1) . " days"));
$sexta = date('Y-m-d', strtotime($segunda . " +4 days"));

// Buscar menus da semana
$menus = mysqli_query($link, "
    SELECT * FROM menu_semana
    WHERE data BETWEEN '$segunda' AND '$sexta'
      AND estado = 1
    ORDER BY data ASC
");


// Organizar menus por data
$menuDia = [];
while ($m = mysqli_fetch_assoc($menus)) {
    $menuDia[$m['data']] = $m;
}

// Função para mostrar estado da refeição
function estado($v) {
    if ($v === null) return "<span class='estado-nenhum'>—</span>";
    if ($v == 2) return "<span class='estado-tudo'>🟢 Tudo</span>";
    if ($v == 1) return "<span class='estado-parcial'>🟡 Parcial</span>";
    if ($v == 0) return "<span class='estado-nada'>🔴 Nada</span>";
}

//paginação
$queryStringFiltros = "&sala=$salaSelecionada&crianca=$criancaSelecionada";
?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <title>Refeições — Funcionário</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <style>
        .estado-tudo { color: #16a34a; font-weight: bold; }
        .estado-parcial { color: #ca8a04; font-weight: bold; }
        .estado-nada { color: #dc2626; font-weight: bold; }
        .estado-nenhum { color: #6b7280; font-weight: bold; }
    </style>
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

        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-6">
            Refeições — Funcionário
        </h1>

        <!-- FORMULÁRIO DE FILTROS -->
        <form method="GET" class="mb-8">

            <!-- SELECT DA SALA -->
            <label class="font-semibold dark:text-gray-200">Escolher sala:</label>
            <select name="sala" onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 
                       rounded bg-white dark:bg-gray-900 dark:text-gray-100">
                <option value="">-- Selecionar sala --</option>

                <?php while ($s = mysqli_fetch_assoc($salas)): ?>
                    <option value="<?= $s['IDsala'] ?>"
                        <?= ($salaSelecionada == $s['IDsala']) ? 'selected' : '' ?>>
                        <?= $s['nome'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <?php
            if ($salaSelecionada != ""):
                $criancasSala = mysqli_query($link, "
                    SELECT * FROM crianca
                    WHERE IDsala = $salaSelecionada AND estado = 1
                    ORDER BY nome
                ");
            ?>

                <br><br>

                <!-- SELECT DA CRIANÇA -->
                <label class="font-semibold dark:text-gray-200">Escolher criança:</label>
                <select name="crianca" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 
                           rounded bg-white dark:bg-gray-900 dark:text-gray-100">
                    <option value="todas">-- Todas as crianças --</option>

                    <?php while ($c = mysqli_fetch_assoc($criancasSala)): ?>
                        <option value="<?= $c['IDcri'] ?>"
                            <?= ($criancaSelecionada == $c['IDcri']) ? 'selected' : '' ?>>
                            <?= $c['nome'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

            <?php endif; ?>

        </form>

        <!-- SE NENHUMA SALA FOI ESCOLHIDA -->
        <?php if ($salaSelecionada == ""): ?>
            <p class="text-gray-600 dark:text-gray-300 italic">
                Selecione uma sala para ver as refeições.
            </p>
            </main></div></body></html>
            <?php exit; ?>
        <?php endif; ?>

        <!-- NAVEGAÇÃO SEMANAL -->
        <div class="flex gap-4 mb-6">

            <a href="funcionario_refeicoes.php?sala=<?= $salaSelecionada ?>&crianca=<?= $criancaSelecionada ?>&data=<?= date('Y-m-d', strtotime($segunda . ' -7 days')) ?>"
               class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                      rounded hover:bg-gray-400 dark:hover:bg-gray-600">
                ← Semana anterior
            </a>

            <a href="funcionario_refeicoes.php?sala=<?= $salaSelecionada ?>&crianca=<?= $criancaSelecionada ?>&data=<?= date('Y-m-d') ?>"
               class="px-4 py-2 bg-blue-500 dark:bg-blue-700 text-white rounded hover:bg-blue-600 dark:hover:bg-blue-600">
                Semana atual
            </a>

            <a href="funcionario_refeicoes.php?sala=<?= $salaSelecionada ?>&crianca=<?= $criancaSelecionada ?>&data=<?= date('Y-m-d', strtotime($segunda . ' +7 days')) ?>"
               class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                      rounded hover:bg-gray-400 dark:hover:bg-gray-600">
                Semana seguinte →
            </a>

        </div>

        <h2 class="text-2xl font-bold text-gray-700 dark:text-gray-200 mb-4">
            Semana: <?= date('d/m', strtotime($segunda)) ?> - <?= date('d/m', strtotime($sexta)) ?>
        </h2>

        <?php
        if ($criancaSelecionada == "" || $criancaSelecionada == "todas") {
            $criancas = mysqli_query($link, "
                SELECT * FROM crianca
                WHERE IDsala = $salaSelecionada AND estado = 1
                ORDER BY nome
            ");
        } else {
            $idCri = intval($criancaSelecionada);
            $criancas = mysqli_query($link, "
                SELECT * FROM crianca
                WHERE IDcri = $idCri AND estado = 1
            ");
        }
        
        $porPagina = 3;

        if ($criancaSelecionada === "todas") {
            $sqlTotal = "
                SELECT COUNT(*) AS total
                FROM crianca
                WHERE IDsala = $salaSelecionada AND estado = 1
            ";
        } else {
            $idCri = intval($criancaSelecionada);
            $sqlTotal = "
                SELECT COUNT(*) AS total
                FROM crianca
                WHERE IDcri = $idCri AND estado = 1
            ";
        }

        $totalRegistos = mysqli_fetch_assoc(mysqli_query($link, $sqlTotal))['total'];
        $totalPaginas  = max(1, ceil($totalRegistos / $porPagina));

        $paginaAtual = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset      = ($paginaAtual - 1) * $porPagina;

        if ($criancaSelecionada === "todas") {
            $criancas = mysqli_query($link, "
                SELECT *
                FROM crianca
                WHERE IDsala = $salaSelecionada AND estado = 1
                ORDER BY nome
                LIMIT $porPagina OFFSET $offset
            ");
        } else {
            $criancas = mysqli_query($link, "
                SELECT *
                FROM crianca
                WHERE IDcri = $idCri AND estado = 1
                LIMIT 1
            ");
        }

        while ($c = mysqli_fetch_assoc($criancas)):
        ?>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-10">

                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
                    <?= $c['nome'] ?>
                </h3>

                <!-- TABELA SEMANAL -->
                <table class="w-full border-collapse mb-6">
                    <thead>
                        <tr class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <th class="p-3 border dark:border-gray-600">Dia</th>
                            <th class="p-3 border dark:border-gray-600">Menu</th>
                            <th class="p-3 border dark:border-gray-600">Lanche manhã</th>
                            <th class="p-3 border dark:border-gray-600">Almoço</th>
                            <th class="p-3 border dark:border-gray-600">Lanche tarde</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    for ($i = 0; $i < 5; $i++):
                        $dia = date('Y-m-d', strtotime($segunda . " +$i days"));
                        $ref = mysqli_fetch_assoc(mysqli_query($link, "
                            SELECT * FROM refeicao_crianca
                            WHERE IDcri = {$c['IDcri']} AND data = '$dia'
                        "));
                        $m = $menuDia[$dia] ?? null;
                    ?>
                        <tr class="bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <td class="p-3 border dark:border-gray-600 font-semibold">
                                <?= date('d/m', strtotime($dia)) ?>
                            </td>

                            <td class="p-3 border dark:border-gray-600">
                                <?php if ($m): ?>
                                    <strong>Lanche manhã:</strong> <?= $m['lanche_manha'] ?><br>
                                    <strong>Almoço:</strong> <?= $m['almoco'] ?><br>
                                    <strong>Lanche tarde:</strong> <?= $m['lanche_tarde'] ?>
                                <?php else: ?>
                                    <span class="text-red-600 dark:text-red-400 font-semibold">Sem menu</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 border dark:border-gray-600"><?= estado($ref['lanche_manha'] ?? null) ?></td>
                            <td class="p-3 border dark:border-gray-600"><?= estado($ref['almoco'] ?? null) ?></td>
                            <td class="p-3 border dark:border-gray-600"><?= estado($ref['lanche_tarde'] ?? null) ?></td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>

            </div>

        <?php endwhile; ?>

        <!-- PAGINAÇÃO -->
        <?php if ($totalPaginas > 1): ?>
        <div class="flex justify-center mt-10 text-center">
            <div class="flex items-center space-x-2">

                <!-- PRIMEIRA -->
                <a href="?p=1<?= $queryStringFiltros ?>&data=<?= $base ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    ««
                </a>

                <!-- ANTERIOR -->
                <a href="?p=<?= max(1, $paginaAtual - 1) ?><?= $queryStringFiltros ?>&data=<?= $base ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    «
                </a>

                <?php
                    $inicio = max(1, $paginaAtual - 2);
                    $fim = min($totalPaginas, $inicio + 4);

                    if ($fim - $inicio < 4) {
                        $inicio = max(1, $fim - 4);
                    }

                    for ($i = $inicio; $i <= $fim; $i++):
                ?>

                <!-- NÚMEROS -->
                <a href="?p=<?= $i ?><?= $queryStringFiltros ?>&data=<?= $base ?>"
                class="w-12 h-12 flex items-center justify-center rounded
                <?= $i == $paginaAtual 
                        ? 'bg-blue-600 dark:bg-blue-700 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                    <?= $i ?>
                </a>

                <?php endfor; ?>

                <!-- SEGUINTE -->
                <a href="?p=<?= min($totalPaginas, $paginaAtual + 1) ?><?= $queryStringFiltros ?>&data=<?= $base ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    »
                </a>

                <!-- ÚLTIMA -->
                <a href="?p=<?= $totalPaginas ?><?= $queryStringFiltros ?>&data=<?= $base ?>"
                class="w-12 h-12 flex items-center justify-center 
                        bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 
                        rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    »»
                </a>

            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
