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

// Verificar login
if (!isset($_SESSION['id'])) {
    header("Location: index.php?erro=permissao");
    exit();
}

$IDutl = $_SESSION['id'];
$tipo = $_SESSION['tipo'];

// Definir página de cancelar consoante o tipo de utilizador
if ($tipo === "superadministrador") {
    $paginaCancelar = "superadmin.php";

} elseif ($tipo === "administrador") {
    $paginaCancelar = "admin.php";

} elseif ($tipo === "educador") {
    $paginaCancelar = "educador.php";

} elseif ($tipo === "encarregado") {
    $paginaCancelar = "encarregado.php";

} elseif ($tipo === "funcionario") {
    $paginaCancelar = "funcionario.php";

} elseif ($tipo === "superadmin") {
    $paginaCancelar = "superadmin.php";

} else {
    $paginaCancelar = "index.php"; // fallback de segurança
}

// Buscar dados do utilizador
$stmt = mysqli_prepare($link, "SELECT nome, email, telefone, datanascimento FROM utilizador WHERE IDutl = ?");
mysqli_stmt_bind_param($stmt, "i", $IDutl);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$utilizador = mysqli_fetch_assoc($result);

// PROCESSAR FORMULÁRIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];

    // Atualizar nome e telefone
    $stmt = mysqli_prepare($link, "UPDATE utilizador SET nome=?, telefone=? WHERE IDutl=?");
    mysqli_stmt_bind_param($stmt, "ssi", $nome, $telefone, $IDutl);
    mysqli_stmt_execute($stmt);

    // Se o utilizador preencheu password nova
    if (!empty($_POST['pass1']) || !empty($_POST['pass2'])) {

        if ($_POST['pass1'] !== $_POST['pass2']) {
            $erro = "As passwords não coincidem.";
        } else {
            $hash = password_hash($_POST['pass1'], PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($link, "UPDATE utilizador SET password=? WHERE IDutl=?");
            mysqli_stmt_bind_param($stmt, "si", $hash, $IDutl);
            mysqli_stmt_execute($stmt);

            $sucesso = "Password alterada com sucesso!";
        }
    }

    if (!isset($erro)) {
        $sucesso = "Dados atualizados com sucesso!";
    }

    // RESETAR FOTO PARA A PADRÃO
    if (isset($_POST['reset_foto']) && $_POST['reset_foto'] == "1") {

        $fotoPadrao = "imagens/perfildefault2.png";

        $stmtReset = mysqli_prepare($link, "UPDATE utilizador SET foto=? WHERE IDutl=?");
        mysqli_stmt_bind_param($stmtReset, "si", $fotoPadrao, $IDutl);
        mysqli_stmt_execute($stmtReset);

        $sucesso = "Foto reposta para a padrão!";
        header("Location: perfil.php?sucesso=1");
        exit;
    }

    // FOTO CORTADA (BASE64)
    if (!empty($_POST['foto_cortada'])) {

        $img = $_POST['foto_cortada'];

        // Remover prefixo base64
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);

        // Nome do ficheiro
        $nomeFinal = "user_" . $IDutl . "_" . time() . ".png";
        $caminhoRelativo = "uploads/perfis/" . $nomeFinal;
        $caminhoAbsoluto = __DIR__ . "/" . $caminhoRelativo;

        file_put_contents($caminhoAbsoluto, $data);

        // Guardar na BD
        $stmtFoto = mysqli_prepare($link, "UPDATE utilizador SET foto=? WHERE IDutl=?");
        mysqli_stmt_bind_param($stmtFoto, "si", $caminhoRelativo, $IDutl);
        mysqli_stmt_execute($stmtFoto);

        $sucesso = "Foto atualizada com sucesso!";
    }
}
?>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Perfil</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <!-- Ajuste de imagem -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

</head>

<!-- Ver password -->
<script>
    function togglePassword(inputId, eyeId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(eyeId);

        if (input.type === "password") {
            input.type = "text";
            eye.textContent = "👁️";
        } else {
            input.type = "password";
            eye.textContent = "👁️‍🗨️";
        }
    }
</script>

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
        $tipo = $_SESSION['tipo'];

        if ($tipo === "administrador" || $tipo === "superadministrador") {
            include("sidebar_admin.php");
        } elseif ($tipo === "educador") {
            include("sidebar_educador.php");
        } else {
            include("sidebar_admin.php"); // fallback seguro
        }
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-10 ml-[20%] h-screen overflow-y-auto">

            <h1 class="text-3xl font-bold text-gray-800 mb-8">Perfil de <?= $_SESSION['user']; ?> </h1>

            <div class="w-full bg-white shadow-lg rounded-lg p-8">
                <?php
                    // Buscar foto atual
                    $stmtFoto = mysqli_prepare($link, "SELECT foto FROM utilizador WHERE IDutl = ?");
                    mysqli_stmt_bind_param($stmtFoto, "i", $IDutl);
                    mysqli_stmt_execute($stmtFoto);
                    $resFoto = mysqli_stmt_get_result($stmtFoto);
                    $foto = mysqli_fetch_assoc($resFoto)['foto'] ?? null;

                    $fotoPerfil = $foto ? $foto : "imagens/perfildefault2.png";
                ?>

                <div class="flex justify-center mb-6">
                    <img id="previewFoto" src="<?= $fotoPerfil ?>" 
                        class="w-40 h-40 rounded-full object-cover border shadow">
                </div>

                <?php if (isset($erro)): ?>
                    <div class="bg-red-200 text-red-800 p-3 rounded mb-4"><?= $erro ?></div>
                <?php endif; ?>

                <?php if (isset($sucesso)): ?>
                    <div class="bg-green-200 text-green-800 p-3 rounded mb-4"><?= $sucesso ?></div>
                <?php endif; ?>

                <!-- FORMULÁRIO ÚNICO -->
                <form method="post" enctype="multipart/form-data" class="space-y-5">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Foto de Perfil</label>

                        <!-- Botão estilizado para escolher ficheiro -->
                        <button type="button"
                                onclick="document.getElementById('inputFoto').click()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Escolher nova foto
                        </button>

                        <!-- Input real escondido (Cropper.js usa este) -->
                        <input type="file" id="inputFoto" name="foto" accept="image/*" class="hidden">

                        <!-- Botão para repor foto padrão -->
                        <button type="button"
                                onclick="definirFotoPadrao()"
                                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            Usar foto padrão
                        </button>
                    </div>

                    <!-- MODAL DE CROP -->
                    <div id="cropModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-[999999]">
                        <div class="bg-white p-4 rounded-lg shadow-lg">
                            <h3 class="text-lg font-bold mb-3">Ajustar Foto</h3>

                            <div class="max-w-md">
                                <img id="cropImage" class="max-w-full">
                            </div>

                            <div class="flex justify-end gap-3 mt-4">
                                <button type="button" id="cancelCrop" class="px-4 py-2 bg-gray-500 text-white rounded">Cancelar</button>
                                <button type="button" id="confirmCrop" class="px-4 py-2 bg-blue-600 text-white rounded">Confirmar</button>
                            </div>
                        </div>
                    </div>

                    <!-- FOTO CORTADA (BASE64) -->
                    <input type="hidden" name="foto_cortada" id="fotoCortada">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="nome" value="<?= $utilizador['nome'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email (não editável)</label>
                        <input type="email" value="<?= $utilizador['email'] ?>" disabled
                            class="mt-1 w-full px-4 py-2 border rounded-lg bg-gray-200">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="text" name="telefone" value="<?= $utilizador['telefone'] ?>"
                            class="mt-1 w-full px-4 py-2 border rounded-lg" 
                            required
                            oninput="this.value = this.value.replace(/[^0-9]/g, '');"> <!-- Só deixa introduzir números, impedindo assim a introdução de letras-->
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                        <input type="date" value="<?= $utilizador['datanascimento'] ?>" disabled
                            class="mt-1 w-full px-4 py-2 border rounded-lg bg-gray-200">
                    </div>

                    <hr class="my-6">

                    <h3 class="text-lg font-bold text-gray-800">Alterar Password</h3>

                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700">Nova Password</label>
                        <input type="password" id="pass1" name="pass1"
                            class="mt-1 w-full px-4 py-2 border rounded-lg">

                        <button type="button" onclick="togglePassword('pass1', 'eyePass')"
                            class="absolute right-3 top-9 text-gray-500">
                            <span id="eyePass">👁️‍🗨️</span>
                        </button>
                    </div>

                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700">Confirmar Password</label>
                        <input type="password" id="pass2" name="pass2"
                            class="mt-1 w-full px-4 py-2 border rounded-lg">

                        <button type="button" onclick="togglePassword('pass2', 'eyeConfirm')"
                            class="absolute right-3 top-9 text-gray-500">
                            <span id="eyeConfirm">👁️‍🗨️</span>
                        </button>
                    </div>

                    <!-- BOTÃO FINAL -->
                    <button type="submit"
                            class="w-[40%] mx-auto block px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Guardar Alterações
                    </button>
                </form>
                
                <a href="<?= $paginaCancelar ?>"
                    class="w-[40%] mx-auto px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 block text-center mt-4">
                    Cancelar
                </a>

            </div>
        </main>
    </div>

<!-- Script para definir foto padrão -->
<script>
    function definirFotoPadrao() {
        // Atualizar preview
        document.getElementById("previewFoto").src = "imagens/perfildefault2.png";

        // Criar campo hidden para avisar o PHP
        let campo = document.getElementById("resetFoto");
        if (!campo) {
            campo = document.createElement("input");
            campo.type = "hidden";
            campo.name = "reset_foto";
            campo.id = "resetFoto";
            campo.value = "1";
            document.querySelector("form").appendChild(campo);
        }

        // Limpar input e cropper
        document.getElementById("inputFoto").value = "";
        document.getElementById("fotoCortada").value = "";

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }
</script>
    
<!-- Ajuste de imagem de perfil -->
<script>
let cropper;

document.getElementById("inputFoto").addEventListener("change", function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const url = URL.createObjectURL(file);
    const cropImage = document.getElementById("cropImage");
    cropImage.src = url;

    document.getElementById("cropModal").classList.remove("hidden");

    setTimeout(() => {
        cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: "move",
            background: false,
            guides: false,
            autoCropArea: 1
        });
    }, 100);
});

document.getElementById("cancelCrop").addEventListener("click", () => {
    cropper.destroy();
    document.getElementById("cropModal").classList.add("hidden");
});

document.getElementById("confirmCrop").addEventListener("click", () => {
    const canvas = cropper.getCroppedCanvas({
        width: 400,
        height: 400
    });

    document.getElementById("previewFoto").src = canvas.toDataURL("image/png");
    document.getElementById("fotoCortada").value = canvas.toDataURL("image/png");

    cropper.destroy();
    document.getElementById("cropModal").classList.add("hidden");
});
</script>

</body>
</html>
