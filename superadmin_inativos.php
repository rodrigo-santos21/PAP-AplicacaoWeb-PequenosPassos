<?php
session_start();
include("DBConnection.php");

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

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    header("Location: index.php?erro=permissao");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt" class="<?= ($tema ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Inativos — Superadmin</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
    // Carregar dados da tab via AJAX
    function carregarTab(tipo) {

        // Atualizar estilo das tabs
        document.querySelectorAll(".tab-btn").forEach(btn => {
            btn.classList.remove(
                "bg-blue-600", "text-white",
                "bg-gray-200", "text-gray-700",
                "dark:bg-gray-700", "dark:text-gray-100"
            );

            // Estado normal (não ativo)
            btn.classList.add(
                "bg-gray-200", "text-gray-700",
                "dark:bg-gray-700", "dark:text-gray-100"
            );
        });

        // Ativar a tab clicada
        const ativa = document.getElementById("tab-" + tipo);
        if (ativa) {
            ativa.classList.remove(
                "bg-gray-200", "text-gray-700",
                "dark:bg-gray-700", "dark:text-gray-100"
            );

            ativa.classList.add(
                "bg-blue-600", "text-white"
            );
        }

        // Mostrar loading
        document.getElementById("conteudo").innerHTML = `
            <div class='text-center py-10 text-gray-600 dark:text-gray-300'>
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

        abrirModalConfirmar("Tem a certeza que deseja reativar este registo?", () => {

            fetch("reativar.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `acao=um&tipo=${tipo}&id=${id}`
            })
            .then(r => r.text())
            .then(res => {
                if (res.trim() === "OK") {
                    mostrarMensagemModal("Registo reativado com sucesso.");
                    carregarTab(tipo);
                } else {
                    mostrarMensagemModal("Erro ao reativar: " + res);
                }
            });

        });
    }

    // Reativar todos os registos da tab
    function reativarTodos(tipo) {

        abrirModalConfirmar("Tem a certeza que deseja reativar TODOS os registos desta categoria?", () => {

            fetch("reativar.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `acao=todos&tipo=${tipo}`
            })
            .then(r => r.text())
            .then(res => {
                if (res.trim() === "OK") {
                    mostrarMensagemModal("Todos os registos foram reativados com sucesso.");
                    carregarTab(tipo);
                } else {
                    mostrarMensagemModal("Erro ao reativar todos: " + res);
                }
            });

        });
    }
    </script>

    <style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { scrollbar-width: none; }
    </style>
</head>

<body class="bg-gray-100 dark:bg-gray-900 dark:text-gray-100 min-h-screen">

    <!-- WRAPPER FLEX -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php include("sidebar_superadmin.php"); ?>
        </div>

        <!-- MENU MOBILE -->
        <?php include("menu_mobile_superadmin.php"); ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">Gestão de Inativos</h1>

            <a href="superadmin.php"
            class="mb-4 inline-block px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded-md font-semibold mt-5 hover:bg-blue-700 dark:hover:bg-blue-600">
                ← Voltar
            </a>
            
            <div class="w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">

                <!-- TABS -->
                <div class="flex flex-wrap gap-2 mb-6">

                    <button id="tab-criancas" class="tab-btn bg-blue-600 text-white px-4 py-2 rounded"
                            onclick="carregarTab('criancas')">
                        Crianças
                    </button>

                    <button id="tab-utilizadores" class="tab-btn bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('utilizadores')">
                        Utilizadores
                    </button>

                    <button id="tab-atividades" class="tab-btn bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('atividades')">
                        Atividades
                    </button>

                    <button id="tab-salas" class="tab-btn bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('salas')">
                        Salas
                    </button>

                    <button id="tab-ocorrencias" class="tab-btn bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('ocorrencias')">
                        Ocorrências
                    </button>

                    <button id="tab-reunioes" class="tab-btn bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-gray-700 px-4 py-2 rounded"
                            onclick="carregarTab('reunioes')">
                        Reuniões
                    </button>

                </div>

                <!-- ÁREA DE CONTEÚDO -->
                <div id="conteudo" class="mt-4 dark:text-gray-800">
                    <!-- Conteúdo carregado via AJAX -->
                </div>
            </div>
        </main>
    </div>

    <script>
        carregarTab('criancas');
    </script>

    <!-- MODAL CONFIRMAÇÃO -->
    <div id="modalConfirmar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Confirmar ação</h2>
            <p id="modalConfirmarTexto" class="text-gray-700 dark:text-gray-300 mb-6"></p>

            <div class="flex justify-end gap-3">
                <button onclick="fecharModalConfirmar()"
                    class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white rounded hover:bg-gray-600 dark:hover:bg-gray-500">
                    Cancelar
                </button>

                <button id="btnConfirmarAcao"
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded hover:bg-blue-700 dark:hover:bg-blue-600">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL MENSAGEM -->
    <div id="modalMensagem" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-md text-center">
            <p id="modalMensagemTexto" class="text-gray-800 dark:text-gray-100 text-lg mb-6"></p>

            <button onclick="fecharModalMensagem()"
                class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded hover:bg-blue-700 dark:hover:bg-blue-600">
                OK
            </button>
        </div>
    </div>

    <script>
    let acaoPendente = null;

    // Abrir modal de confirmação
    function abrirModalConfirmar(texto, callback) {
        document.getElementById("modalConfirmarTexto").textContent = texto;
        acaoPendente = callback;

        const modal = document.getElementById("modalConfirmar");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    // Fechar modal de confirmação
    function fecharModalConfirmar() {
        const modal = document.getElementById("modalConfirmar");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        acaoPendente = null;
    }

    // Confirmar ação
    document.getElementById("btnConfirmarAcao").addEventListener("click", () => {
        if (acaoPendente) acaoPendente();
        fecharModalConfirmar();
    });

    // Modal de mensagem
    function mostrarMensagemModal(texto) {
        document.getElementById("modalMensagemTexto").textContent = texto;

        const modal = document.getElementById("modalMensagem");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function fecharModalMensagem() {
        const modal = document.getElementById("modalMensagem");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
    </script>

</body>
</html>
