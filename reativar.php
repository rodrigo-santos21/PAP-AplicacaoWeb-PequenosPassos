<?php
session_start();
include "DBConnection.php";

// Apenas superadmin pode reativar
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'superadmin') {
    exit("Acesso negado.");
}

if (!isset($_POST['acao']) || !isset($_POST['tipo'])) {
    exit("Parâmetros inválidos.");
}

$acao = $_POST['acao']; // um | todos
$tipo = $_POST['tipo'];
$id   = isset($_POST['id']) ? intval($_POST['id']) : null;

date_default_timezone_set("Europe/Lisbon");
$fdatahora = date("Y-m-d H:i:s");
$IDutl = $_SESSION['id'];

/* ============================================================
   FUNÇÕES DE REATIVAÇÃO
   ============================================================ */

function reativarSimples($link, $tabela, $campoID, $id) {
    return mysqli_query($link, "UPDATE $tabela SET estado = 1 WHERE $campoID = $id");
}

function reativarTodosSimples($link, $tabela) {
    return mysqli_query($link, "UPDATE $tabela SET estado = 1");
}

/* ============================================================
   REGRAS DE REATIVAÇÃO POR TIPO
   ============================================================ */

switch ($tipo) {

    /* ============================
       CRIANÇAS
       ============================ */
    case "criancas":

        if ($acao === "um") {

            reativarSimples($link, "crianca", "IDcri", $id);

            // Reativar relações
            mysqli_query($link, "UPDATE crianca_educador SET estado = 1 WHERE IDcri = $id");
            mysqli_query($link, "UPDATE crianca_atividade SET estado = 1 WHERE IDcri = $id");
            mysqli_query($link, "UPDATE ocorrencia SET estado = 1 WHERE IDcri = $id");
            mysqli_query($link, "UPDATE presenca SET estado = 1 WHERE IDcri = $id");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou criança ID $id', '$fdatahora', $IDutl)
            ");

        } else {

            reativarTodosSimples($link, "crianca");
            mysqli_query($link, "UPDATE crianca_educador SET estado = 1");
            mysqli_query($link, "UPDATE crianca_atividade SET estado = 1");
            mysqli_query($link, "UPDATE ocorrencia SET estado = 1");
            mysqli_query($link, "UPDATE presenca SET estado = 1");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou TODAS as crianças', '$fdatahora', $IDutl)
            ");
        }

        break;

    /* ============================
       UTILIZADORES
       ============================ */
    case "utilizadores":

    if ($acao === "um") {

        // 1) Reativar utilizador
        reativarSimples($link, "utilizador", "IDutl", $id);

        // 2) Buscar tipo do utilizador
        $resTipo = mysqli_query($link, "SELECT tipo FROM utilizador WHERE IDutl = $id");
        $tipoUser = mysqli_fetch_assoc($resTipo)['tipo'];

        /* ============================================================
           REGRAS POR TIPO
        ============================================================ */

        /* ============================
           EDUCADORES
        ============================ */
        if ($tipoUser === "educador") {

            // Buscar IDedu
            $resEdu = mysqli_query($link, "SELECT IDedu FROM educador WHERE IDutl = $id");
            if ($resEdu && mysqli_num_rows($resEdu) > 0) {

                $IDedu = mysqli_fetch_assoc($resEdu)['IDedu'];

                // Reativar educador
                mysqli_query($link, "UPDATE educador SET estado = 1 WHERE IDedu = $IDedu");

                // Reativar relações
                mysqli_query($link, "UPDATE crianca_educador SET estado = 1 WHERE IDedu = $IDedu");
                mysqli_query($link, "UPDATE ocorrencia SET estado = 1 WHERE IDedu = $IDedu");
                mysqli_query($link, "UPDATE atividade SET estado = 1 WHERE IDedu = $IDedu");

                // Reativar participações em reuniões
                mysqli_query($link, "
                    UPDATE reuniao_participante 
                    SET estado = 1 
                    WHERE IDutl = $id
                ");
            }
        }

        /* ============================
        ADMINISTRADORES
        ============================ */
        if ($tipoUser === "administrador") {

            // Reativar reuniões criadas pelo admin
            mysqli_query($link, "
                UPDATE reuniao 
                SET estado = 1 
                WHERE criadopor = $id
            ");

            // Reativar participações dessas reuniões
            mysqli_query($link, "
                UPDATE reuniao_participante 
                SET estado = 1
                WHERE IDreu IN (
                    SELECT IDreu FROM reuniao WHERE criadopor = $id
                )
            ");
        }

        /* ============================
           FUNCIONÁRIOS
        ============================ */
        if ($tipoUser === "funcionario") {

            // Reativar participações em reuniões
            mysqli_query($link, "
                UPDATE reuniao_participante 
                SET estado = 1 
                WHERE IDutl = $id
            ");
        }

        /* ============================
           ENCARREGADOS
        ============================ */
        if ($tipoUser === "encarregado") {

            // Reativar participações em reuniões
            mysqli_query($link, "
                UPDATE reuniao_participante 
                SET estado = 1 
                WHERE IDutl = $id
            ");

            // Reativar crianças associadas ao encarregado
            mysqli_query($link, "
                UPDATE crianca 
                SET estado = 1 
                WHERE IDutl = $id
            ");
        }

        // LOG
        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Superadmin reativou utilizador ID $id ($tipoUser)', '$fdatahora', $IDutl)
        ");

    } else {

        // Reativar todos os utilizadores
        reativarTodosSimples($link, "utilizador");

        // Reativar tudo o que depende de utilizadores
        mysqli_query($link, "UPDATE educador SET estado = 1");
        mysqli_query($link, "UPDATE crianca SET estado = 1");
        mysqli_query($link, "UPDATE crianca_educador SET estado = 1");
        mysqli_query($link, "UPDATE ocorrencia SET estado = 1");
        mysqli_query($link, "UPDATE atividade SET estado = 1");
        mysqli_query($link, "UPDATE reuniao SET estado = 1");
        mysqli_query($link, "UPDATE reuniao_participante SET estado = 1");

        mysqli_query($link, "
            INSERT INTO logs (descricao, datahora, IDutl)
            VALUES ('Superadmin reativou TODOS os utilizadores', '$fdatahora', $IDutl)
        ");
    }

    break;

    /* ============================
       ATIVIDADES
       ============================ */
    case "atividades":

        if ($acao === "um") {

            reativarSimples($link, "atividade", "IDatv", $id);
            mysqli_query($link, "UPDATE crianca_atividade SET estado = 1 WHERE IDatv = $id");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou atividade ID $id', '$fdatahora', $IDutl)
            ");

        } else {

            reativarTodosSimples($link, "atividade");
            mysqli_query($link, "UPDATE crianca_atividade SET estado = 1");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou TODAS as atividades', '$fdatahora', $IDutl)
            ");
        }

        break;

    /* ============================
       SALAS
       ============================ */
    case "salas":

        if ($acao === "um") {

            reativarSimples($link, "sala", "IDsala", $id);

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou sala ID $id', '$fdatahora', $IDutl)
            ");

        } else {

            reativarTodosSimples($link, "sala");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou TODAS as salas', '$fdatahora', $IDutl)
            ");
        }

        break;

    /* ============================
       OCORRÊNCIAS
       ============================ */
    case "ocorrencias":

        if ($acao === "um") {

            reativarSimples($link, "ocorrencia", "IDoc", $id);

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou ocorrência ID $id', '$fdatahora', $IDutl)
            ");

        } else {

            reativarTodosSimples($link, "ocorrencia");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou TODAS as ocorrências', '$fdatahora', $IDutl)
            ");
        }

        break;

    /* ============================
       REUNIÕES
       ============================ */
    case "reunioes":

        if ($acao === "um") {

            reativarSimples($link, "reuniao", "IDreu", $id);
            mysqli_query($link, "UPDATE reuniao_participante SET estado = 1 WHERE IDreu = $id");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou reunião ID $id', '$fdatahora', $IDutl)
            ");

        } else {

            reativarTodosSimples($link, "reuniao");
            mysqli_query($link, "UPDATE reuniao_participante SET estado = 1");

            mysqli_query($link, "
                INSERT INTO logs (descricao, datahora, IDutl)
                VALUES ('Superadmin reativou TODAS as reuniões', '$fdatahora', $IDutl)
            ");
        }

        break;

    default:
        exit("Tipo inválido.");
}

echo "OK";
