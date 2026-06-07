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

// Apenas educadores podem aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'educador') {
    header("Location: index.php?erro=permissao");
    exit;
}

$IDedu = $_SESSION['id'];

// Buscar sala do educador
$resSala = mysqli_query($link, "SELECT IDsala FROM educador WHERE IDutl = $IDedu AND estado = 1");
$sala = mysqli_fetch_assoc($resSala);
$IDsala = $sala['IDsala'] ?? null;

if (!$IDsala) {
    die("Erro: Educador sem sala atribuída.");
}

// Data selecionada (ou hoje)
$hoje = date('Y-m-d');
$dataSelecionada = $_GET['data'] ?? $hoje;

// Buscar menu do dia selecionado
$menu = mysqli_fetch_assoc(mysqli_query($link, "
    SELECT * FROM menu_semana WHERE data = '$dataSelecionada' AND estado = 1
"));

// Buscar crianças da sala
$criancas = mysqli_query($link, "
    SELECT * FROM crianca 
    WHERE IDsala = $IDsala AND estado = 1
    ORDER BY nome
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Refeições — Educador</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">

    <script>
    function marcar(IDcri, refeicao, valor) {

        fetch("marcar_refeicao.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "IDcri=" + IDcri + "&refeicao=" + refeicao + "&valor=" + valor + "&data=<?= $dataSelecionada ?>"
        })
        .then(r => r.text())
        .then(res => {
            if (res.trim() === "ok") {
                mostrarMensagem("Registo atualizado.");
            } else {
                mostrarMensagem("Erro ao atualizar.", true);
            }
        });
    }

    function mostrarMensagem(texto, erro = false) {
        const div = document.createElement("div");
        div.className = "fixed top-5 right-5 px-4 py-2 rounded shadow-lg text-white " +
                        (erro ? "bg-red-600" : "bg-green-600");
        div.textContent = texto;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 2000);
    }
    </script>
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

    <?php include("sidebar_educador.php"); ?>

    <main class="flex-1 p-10 ml-[20%]">

        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            Refeições — <?= date('d/m/Y', strtotime($dataSelecionada)) ?>
        </h1>

        <!-- NAVEGAÇÃO ENTRE DIAS -->
        <div class="flex gap-4 mb-6">

            <!-- DIA ANTERIOR -->
            <?php
            $anterior = date('Y-m-d', strtotime($dataSelecionada . ' -1 day'));
            $diaSemanaAnterior = date('N', strtotime($anterior));

            if ($diaSemanaAnterior > 5) {
                // sábado ou domingo → sexta anterior
                $anterior = date('Y-m-d', strtotime('last friday', strtotime($anterior)));
            }
            ?>
            <a href="educador_refeicoes.php?data=<?= $anterior ?>"
               class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                ← Dia anterior
            </a>

            <!-- HOJE -->
            <a href="educador_refeicoes.php?data=<?= date('Y-m-d') ?>"
               class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Hoje
            </a>

            <!-- DIA SEGUINTE -->
            <?php
            $seguinte = date('Y-m-d', strtotime($dataSelecionada . ' +1 day'));
            $diaSemanaSeguinte = date('N', strtotime($seguinte));

            if ($diaSemanaSeguinte > 5) {
                // sábado ou domingo → segunda seguinte
                $seguinte = date('Y-m-d', strtotime('next monday', strtotime($seguinte)));
            }
            ?>
            <a href="educador_refeicoes.php?data=<?= $seguinte ?>"
               class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Dia seguinte →
            </a>

        </div>

        <!-- MENU DO DIA -->
        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-700 mb-4">Menu do Dia</h2>

            <?php if ($menu): ?>
                <p><strong>Lanche da manhã:</strong> <?= $menu['lanche_manha'] ?></p>
                <p><strong>Almoço:</strong> <?= $menu['almoco'] ?></p>
                <p><strong>Lanche da tarde:</strong> <?= $menu['lanche_tarde'] ?></p>
            <?php else: ?>
                <p class="text-red-600 font-semibold">Ainda não foi definido menu para este dia.</p>
            <?php endif; ?>
        </div>

        <!-- LISTA DE CRIANÇAS -->
        <div class="bg-white shadow rounded-lg p-6">

            <h2 class="text-xl font-bold text-gray-700 mb-6">Marcar Refeições</h2>

            <?php
            $temMenu = $menu ? true : false;
            $disabled = $temMenu ? "" : "opacity-40 cursor-not-allowed";
            ?>

            <?php while ($c = mysqli_fetch_assoc($criancas)): ?>

                <div class="mb-6 p-4 bg-gray-50 rounded shadow">

                    <h3 class="text-lg font-semibold text-gray-800 mb-3"><?= $c['nome'] ?></h3>

                    <!-- LANCHE MANHÃ -->
                    <p class="font-semibold">Lanche da manhã:</p>
                    <div class="flex gap-2 mb-3">

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_manha', 2)\"" : "" ?>
                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 <?= $disabled ?>">
                            Tudo
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_manha', 1)\"" : "" ?>
                                class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 <?= $disabled ?>">
                            Parcial
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_manha', 0)\"" : "" ?>
                                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 <?= $disabled ?>">
                            Nada
                        </button>

                    </div>

                    <!-- ALMOÇO -->
                    <p class="font-semibold">Almoço:</p>
                    <div class="flex gap-2 mb-3">

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'almoco', 2)\"" : "" ?>
                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 <?= $disabled ?>">
                            Tudo
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'almoco', 1)\"" : "" ?>
                                class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 <?= $disabled ?>">
                            Parcial
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'almoco', 0)\"" : "" ?>
                                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 <?= $disabled ?>">
                            Nada
                        </button>

                    </div>

                    <!-- LANCHE TARDE -->
                    <p class="font-semibold">Lanche da tarde:</p>
                    <div class="flex gap-2">

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_tarde', 2)\"" : "" ?>
                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 <?= $disabled ?>">
                            Tudo
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_tarde', 1)\"" : "" ?>
                                class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 <?= $disabled ?>">
                            Parcial
                        </button>

                        <button <?= $temMenu ? "onclick=\"marcar({$c['IDcri']}, 'lanche_tarde', 0)\"" : "" ?>
                                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 <?= $disabled ?>">
                            Nada
                        </button>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    </main>
</div>

</body>
</html>
