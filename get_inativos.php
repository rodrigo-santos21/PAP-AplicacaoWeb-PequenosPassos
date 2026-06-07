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
            $html .= "<td class='p-3'>" . htmlspecialchars($valor ?? "") . "</td>";
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
        $sql = "SELECT IDcri, nome, datanascimento, IDsala FROM crianca WHERE estado = 0 AND aprovado = 1 ";
        break;

    case "educadores":

    // 1) Buscar educadores inativos
    $sql = "SELECT IDedu, IDutl, especialidade, IDsala 
            FROM educador 
            WHERE estado = 0";

    $res = mysqli_query($link, $sql);
    $dados = [];

    while ($e = mysqli_fetch_assoc($res)) {

        $IDutl = intval($e['IDutl']);
        $IDsala = $e['IDsala'];

        // 2) Buscar nome e email do utilizador (SEM JOIN)
        $nome = "—";
        $email = "—";

        $resU = mysqli_query($link, "SELECT nome, email FROM utilizador WHERE IDutl = $IDutl");
        if ($resU && mysqli_num_rows($resU) > 0) {
            $u = mysqli_fetch_assoc($resU);
            $nome = $u['nome'] ?? "—";
            $email = $u['email'] ?? "—";
        }

        // 3) Buscar nome da sala (SEM JOIN)
        $salaNome = "—";
        if (!empty($IDsala)) {
            $resSala = mysqli_query($link, "SELECT nome FROM sala WHERE IDsala = $IDsala");
            if ($resSala && mysqli_num_rows($resSala) > 0) {
                $s = mysqli_fetch_assoc($resSala);
                $salaNome = $s['nome'] ?? "—";
            }
        }

        // 4) Construir linha final
        $dados[] = [
            "IDedu"        => $e['IDedu'],
            "nome"         => $nome,
            "email"        => $email,
            "especialidade"=> $e['especialidade'] ?? "—",
            "sala"         => $salaNome
        ];
    }

    echo gerarTabela("Registos Inativos — Educadores", $dados, "educadores");
    exit();

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
