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

// Apenas admin e superadmin podem aceder
if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['administrador', 'superadmin'])) {
    header("Location: index.php?erro=permissao");
    exit;
}

$mensagem = "";

/* ============================================================
   PROCESSAR FORMULÁRIO
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data_inicio'])) {

    $dataInicio = $_POST['data_inicio'];

    // Validar se é segunda-feira
    $diaSemana = date('N', strtotime($dataInicio)); // 1 = segunda-feira
        if ($diaSemana != 1) {
            $mensagem = "<div class='bg-red-200 text-red-800 p-3 rounded mb-4'>
                            A data escolhida deve ser uma segunda-feira.
                        </div>";
        } else {

        // Gerar as datas de segunda a sexta
        $datas = [];
        for ($i = 0; $i < 5; $i++) {
            $datas[] = date('Y-m-d', strtotime("$dataInicio +$i day"));
        }

        // Inserir 5 registos
        for ($i = 0; $i < 5; $i++) {

            $lanche_manha = mysqli_real_escape_string($link, $_POST["lanche_manha_$i"]);
            $almoco = mysqli_real_escape_string($link, $_POST["almoco_$i"]);
            $lanche_tarde = mysqli_real_escape_string($link, $_POST["lanche_tarde_$i"]);

            mysqli_query($link, "
                INSERT INTO menu_semana (data, lanche_manha, almoco, lanche_tarde)
                VALUES ('{$datas[$i]}', '$lanche_manha', '$almoco', '$lanche_tarde')
            ");
        }

        $mensagem = "<div class='bg-green-200 text-green-800 p-3 rounded mb-4'>
                        Menu semanal criado com sucesso!
                     </div>";
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Adicionar Menu Semanal</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
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

    <!-- SIDEBAR -->
    <?php
        if ($_SESSION['tipo'] === 'superadmin') include("sidebar_superadmin.php");
        else include("sidebar_admin.php");
    ?>

    <!-- CONTEÚDO -->
    <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto no-scrollbar">

        <h1 class="text-3xl font-bold text-gray-800 mb-8">Adicionar Menu Semanal</h1>

        <a href="<?php echo $_SESSION['tipo'] === 'superadmin' ? 'superadmin.php' : 'admin.php'; ?>"
           class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700">
            ← Voltar
        </a>

        <div class="bg-white shadow-lg rounded-lg p-8">

            <?= $mensagem ?>

            <form method="POST">

                <!-- Escolher segunda-feira -->
                <label class="block font-semibold mb-2">Escolher Segunda-feira:</label>
                <input type="date" name="data_inicio" required
                       class="border p-2 rounded mb-6 w-60">

                <hr class="my-6">

                <?php
                // Pré-gerar dias da semana (placeholders)
                $dias = ["Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira"];

                for ($i = 0; $i < 5; $i++):
                ?>

                <div class="mb-8 p-4 bg-green-50 rounded shadow">

                    <h2 class="text-xl font-bold text-gray-700 mb-4">
                        <?= $dias[$i] ?>
                    </h2>

                    <label class="block font-semibold">Lanche da manhã:</label>
                    <textarea name="lanche_manha_<?= $i ?>" class="border p-2 rounded w-full mb-3"></textarea>

                    <label class="block font-semibold">Almoço:</label>
                    <textarea name="almoco_<?= $i ?>" class="border p-2 rounded w-full mb-3"></textarea>

                    <label class="block font-semibold">Lanche da tarde:</label>
                    <textarea name="lanche_tarde_<?= $i ?>" class="border p-2 rounded w-full"></textarea>

                </div>

                <?php endfor; ?>

                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700">
                    Guardar Semana
                </button>

            </form>
        </div>
    </main>
</div>

<!-- SCRIPT para só permitir segundas-feiras -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.querySelector("input[name='data_inicio']");

    // Impede selecionar dias que não sejam segunda-feira
    input.addEventListener("input", function () {
        const data = new Date(this.value);
        const diaSemana = data.getDay(); // 1 = segunda-feira

        if (diaSemana !== 1) {
            this.setCustomValidity("Só podes selecionar segundas-feiras.");
            this.reportValidity();
            this.value = "";
        } else {
            this.setCustomValidity("");
        }
    });

    // Bloqueia visualmente no calendário (Chrome, Edge, etc.)
    input.addEventListener("click", function () {
        this.showPicker(); // força abrir o calendário
    });
});
</script>

</body>
</html>
