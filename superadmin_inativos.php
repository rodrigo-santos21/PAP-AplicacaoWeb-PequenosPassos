<?php
session_start();
include("DBConnection.php");

//BUSCA A FOTO DE PERFIL DO UTILIZADOR
$IDutl = $_SESSION['id'];

$stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
mysqli_stmt_execute($stmtFoto);
$resFoto = mysqli_stmt_get_result($stmtFoto);
$foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

$fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Inativos — Superadmin</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <script>
    // Carregar dados da tab via AJAX
    function carregarTab(tipo) {

        // Atualizar estilo das tabs
        document.querySelectorAll(".tab-btn").forEach(btn => {
            btn.classList.remove("bg-blue-600", "text-white");
            btn.classList.add("bg-gray-200", "text-gray-700");
        });

        document.getElementById("tab-" + tipo).classList.remove("bg-gray-200", "text-gray-700");
        document.getElementById("tab-" + tipo).classList.add("bg-blue-600", "text-white");

        // Mostrar loading
        document.getElementById("conteudo").innerHTML = `
            <div class='text-center py-10 text-gray-600'>
                A carregar...
            </div>
        `;

        // AJAX
        fetch("get_inativos.php?tipo=" + tipo)
            .then(r => r.text())
            .then(html => {
                document.getElementById("conteudo").innerHTML = html;
            });
    }

    // Reativar um registo
    function reativarUm(tipo, id) {
        if (!confirm("Tem a certeza que deseja reativar este registo?")) return;

        fetch("reativar.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `acao=um&tipo=${tipo}&id=${id}`
        })
        .then(r => r.text())
        .then(res => {
            if (res.trim() === "OK") {
                carregarTab(tipo);
            } else {
                alert("Erro ao reativar: " + res);
            }
        });
    }

    // Reativar todos os registos da tab
    function reativarTodos(tipo) {
        if (!confirm("Tem a certeza que deseja reativar TODOS os registos desta categoria?")) return;

        fetch("reativar.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `acao=todos&tipo=${tipo}`
        })
        .then(r => r.text())
        .then(res => {
            if (res.trim() === "OK") {
                carregarTab(tipo);
            } else {
                alert("Erro ao reativar todos: " + res);
            }
        });
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

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php
            include("sidebar_superadmin.php");
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

		    <h1 class="text-3xl font-bold text-gray-800 mb-8">Gestão de Inativos </h1>
    
            <div class="w-full bg-white shadow-lg rounded-lg p-8">

                <!-- TABS -->
                <div class="flex flex-wrap gap-2 mb-6">

                    <button id="tab-criancas" class="tab-btn bg-blue-600 text-white px-4 py-2 rounded"
                            onclick="carregarTab('criancas')">
                        Crianças
                    </button>

                    <button id="tab-educadores" class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('educadores')">
                        Educadores
                    </button>

                    <button id="tab-utilizadores" class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('utilizadores')">
                        Utilizadores
                    </button>

                    <button id="tab-atividades" class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('atividades')">
                        Atividades
                    </button>

                    <button id="tab-salas" class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('salas')">
                        Salas
                    </button>

                    <button id="tab-ocorrencias" class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('ocorrencias')">
                        Ocorrências
                    </button>

                    <button id="tab-reunioes" class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('reunioes')">
                        Reuniões
                    </button>

                </div>

                <!-- ÁREA DE CONTEÚDO -->
                <div id="conteudo" class="mt-4">
                    <!-- Conteúdo carregado via AJAX -->
                </div>

                <div class="text-center mt-16">
                    <a href="superadmin.php"
                    class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Voltar
                    </a>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Carregar a primeira tab automaticamente
        carregarTab('criancas');
    </script>

</body>
</html>
