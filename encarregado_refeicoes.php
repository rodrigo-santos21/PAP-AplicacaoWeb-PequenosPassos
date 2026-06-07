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

// Apenas encarregados podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'encarregado') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDenc = $_SESSION['id'];

// Buscar filhos do encarregado
$filhos = mysqli_query($link, "
    SELECT * FROM crianca 
    WHERE IDutl = $IDenc AND estado = 1
");

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
    ORDER BY data ASC
");

// Organizar menus por data
$menuDia = [];
while ($m = mysqli_fetch_assoc($menus)) {
    $menuDia[$m['data']] = $m;
}

function estado($v) {
    if ($v === null) return "<span class='estado-nenhum'>—</span>";
    if ($v == 2) return "<span class='estado-tudo'>🟢 Tudo</span>";
    if ($v == 1) return "<span class='estado-parcial'>🟡 Parcial</span>";
    if ($v == 0) return "<span class='estado-nada'>🔴 Nada</span>";
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Refeições — Encarregado</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">

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

<body class="bg-gray-100 min-h-screen">

<div class="flex min-h-screen">

    <?php include("sidebar_encarregado.php"); ?>

    <main class="flex-1 p-10 ml-[20%]">

        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            Refeições da Semana (<?= date('d/m', strtotime($segunda)) ?> - <?= date('d/m', strtotime($sexta)) ?>)
        </h1>

        <!-- NAVEGAÇÃO SEMANAL -->
        <div class="flex gap-4 mb-6">

            <a href="encarregado_refeicoes.php?data=<?= date('Y-m-d', strtotime($segunda . ' -7 days')) ?>"
               class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                ← Semana anterior
            </a>

            <a href="encarregado_refeicoes.php?data=<?= date('Y-m-d') ?>"
               class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Semana atual
            </a>

            <a href="encarregado_refeicoes.php?data=<?= date('Y-m-d', strtotime($segunda . ' +7 days')) ?>"
               class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Semana seguinte →
            </a>

        </div>

        <!-- LISTA DE FILHOS -->
        <?php while ($f = mysqli_fetch_assoc($filhos)): ?>

            <div class="bg-white shadow rounded-lg p-6 mb-10">

                <h2 class="text-2xl font-bold text-gray-700 mb-4">
                    <?= $f['nome'] ?>
                </h2>

                <!-- TABELA SEMANAL -->
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-3 border">Dia</th>
                            <th class="p-3 border">Menu</th>
                            <th class="p-3 border">Lanche manhã</th>
                            <th class="p-3 border">Almoço</th>
                            <th class="p-3 border">Lanche tarde</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    for ($i = 0; $i < 5; $i++):
                        $dia = date('Y-m-d', strtotime($segunda . " +$i days"));

                        // Buscar refeições marcadas
                        $ref = mysqli_fetch_assoc(mysqli_query($link, "
                            SELECT * FROM refeicao_crianca
                            WHERE IDcri = {$f['IDcri']} AND data = '$dia'
                        "));

                        // Menu do dia
                        $m = $menuDia[$dia] ?? null;
                    ?>
                        <tr class="bg-gray-50">
                            <td class="p-3 border font-semibold">
                                <?= date('d/m', strtotime($dia)) ?>
                            </td>

                            <td class="p-3 border">
                                <?php if ($m): ?>
                                    <strong>Lanche manhã:</strong> <?= $m['lanche_manha'] ?><br>
                                    <strong>Almoço:</strong> <?= $m['almoco'] ?><br>
                                    <strong>Lanche tarde:</strong> <?= $m['lanche_tarde'] ?>
                                <?php else: ?>
                                    <span class="text-red-600 font-semibold">Sem menu</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 border"><?= estado($ref['lanche_manha'] ?? null) ?></td>
                            <td class="p-3 border"><?= estado($ref['almoco'] ?? null) ?></td>
                            <td class="p-3 border"><?= estado($ref['lanche_tarde'] ?? null) ?></td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>

            </div>

        <?php endwhile; ?>

    </main>
</div>

</body>
</html>
