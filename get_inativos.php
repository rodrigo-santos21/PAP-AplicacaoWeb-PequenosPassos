<?php
session_start();
include "DBConnection.php";

// Apenas superadmin pode aceder
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    exit("Acesso negado.");
}

if (!isset($_GET['tipo'])) {
    exit("Tipo inválido.");
}

$tipo = $_GET['tipo'];

/* ============================================================
   FUNÇÃO PARA GERAR TABELA
   ============================================================ */
function gerarTabela($titulo, $dados, $tipo) {

    if (count($dados) === 0) {
        return "
            <h2 class='text-xl font-bold text-gray-700 mb-4'>$titulo</h2>
            <p class='text-gray-600 text-center'>Nenhum registo inativo encontrado.</p>
        ";
    }

    $html = "
        <h2 class='text-xl font-bold text-gray-700 mb-4'>$titulo</h2>

        <div class='mb-4'>
            <button onclick=\"reativarTodos('$tipo')\"
                class='px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700'>
                Reativar Todos
            </button>
        </div>

        <div class='overflow-x-auto'>
            <table class='w-full border-collapse bg-white shadow rounded-lg'>
                <thead>
                    <tr class='bg-blue-600 text-white'>
    ";

    // Cabeçalhos dinâmicos
    foreach (array_keys($dados[0]) as $coluna) {
        $html .= "<th class='p-3 text-left'>" . ucfirst($coluna) . "</th>";
    }

    $html .= "<th class='p-3 text-left'>Ações</th></tr></thead><tbody>";

    // Linhas
    foreach ($dados as $linha) {
        $html .= "<tr class='border-b hover:bg-gray-100'>";

        foreach ($linha as $valor) {
            $html .= "<td class='p-3'>" . htmlspecialchars($valor) . "</td>";
        }

        $id = $linha[array_key_first($linha)];

        $html .= "
            <td class='p-3'>
                <button onclick=\"reativarUm('$tipo', $id)\"
                    class='px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700'>
                    Reativar
                </button>
            </td>
        </tr>";
    }

    $html .= "</tbody></table></div>";

    return $html;
}

/* ============================================================
   BUSCAR DADOS DEPENDENDO DO TIPO
   ============================================================ */

switch ($tipo) {

    case "criancas":
        $sql = "SELECT IDcri, nome, datanascimento, IDsala FROM crianca WHERE estado = 0";
        break;

    case "educadores":
        // Buscar educadores + nome/email do utilizador
        $sql = "
            SELECT e.IDedu, u.nome, u.email, e.especialidade, e.IDsala
            FROM educador e
            LEFT JOIN utilizador u ON u.IDutl = e.IDutl
            WHERE e.estado = 0
        ";
        break;

    case "utilizadores":
        $sql = "SELECT IDutl, nome, email, tipo FROM utilizador WHERE estado = 0";
        break;

    case "atividades":
        $sql = "SELECT IDatv, titulo, datahora, IDedu FROM atividade WHERE estado = 0";
        break;

    case "salas":
        $sql = "SELECT IDsala, nome, capacidade FROM sala WHERE estado = 0";
        break;

    case "ocorrencias":
        $sql = "SELECT IDoc, tipo, datahora, IDcri, IDedu FROM ocorrencia WHERE estado = 0";
        break;

    case "reunioes":
        $sql = "SELECT IDreu, titulo, datahora, localidade FROM reuniao WHERE estado = 0";
        break;

    default:
        exit("Tipo inválido.");
}

$res = mysqli_query($link, $sql);
$dados = [];

while ($row = mysqli_fetch_assoc($res)) {
    $dados[] = $row;
}

/* ============================================================
   GERAR HTML DA TABELA
   ============================================================ */

echo gerarTabela("Registos Inativos — " . ucfirst($tipo), $dados, $tipo);
