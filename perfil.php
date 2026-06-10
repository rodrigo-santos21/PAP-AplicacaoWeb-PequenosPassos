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

    if (!isset($erro)) {
        header("Location: perfil.php?sucesso=1");
        exit;
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

<!-- SCRIPT global de toast-->
<script>
    function mostrarMensagem(tipo, texto) {
        const box = document.getElementById("msgGlobal");
        const icon = document.getElementById("msgIcon");
        const msg = document.getElementById("msgTexto");

        // Limpar classes antigas
        box.classList.remove("border-blue-600", "border-green-600", "border-yellow-500", "border-red-600");
        msg.classList.remove("text-blue-600", "text-green-600", "text-yellow-500", "text-red-600");

        const icons = {
            adicionar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-blue-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m4.5 12.75 6 6 9-13.5" />
            </svg>`,

            editar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 
                        2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 
                        1.13L6 18l.8-2.685a4.5 4.5 0 0 1 
                        1.13-1.897l8.932-8.931Zm0 0L19.5 
                        7.125M18 14v4.75A2.25 2.25 0 0 1 
                        15.75 21H5.25A2.25 2.25 0 0 1 
                        3 18.75V8.25A2.25 2.25 0 0 1 
                        5.25 6H10" />
            </svg>`,

            reset: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-yellow-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 
                        3.374 1.948 3.374h14.71c1.73 0 
                        2.813-1.874 1.948-3.374L13.949 
                        3.378c-.866-1.5-3.032-1.5-3.898 
                        0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>`,

            eliminar: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6 text-red-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21
                        c.342.052.682.107 1.022.166m-1.022-.165L19.5 19.5
                        a2.25 2.25 0 0 1-2.244 2.25H6.744A2.25 2.25 0 0 1
                        4.5 19.5L5.772 5.79m14.456 0a48.108 48.108 0 0 0
                        -3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0
                        a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164
                        -2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09
                        1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>`
        };

        // Aplicar ícone
        icon.innerHTML = icons[tipo];
        msg.textContent = texto;

        // Aplicar cor do texto
        if (tipo === "adicionar") msg.classList.add("text-blue-600");
        if (tipo === "editar") msg.classList.add("text-green-600");
        if (tipo === "reset") msg.classList.add("text-yellow-500");
        if (tipo === "eliminar") msg.classList.add("text-red-600");

        // Aplicar cor da borda
        if (tipo === "adicionar") box.classList.add("border-blue-600");
        if (tipo === "editar") box.classList.add("border-green-600");
        if (tipo === "reset") box.classList.add("border-yellow-500");
        if (tipo === "eliminar") box.classList.add("border-red-600");

        // Mostrar
        box.classList.remove("hidden", "opacity-0");
        box.classList.add("opacity-100");

        // Ocultar após 3 segundos
        setTimeout(() => {
            box.classList.add("opacity-0");
            setTimeout(() => box.classList.add("hidden"), 300);
        }, 3000);
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
    <!-- MENSAGEM GLOBAL -->
    <div id="msgGlobal" 
        class="hidden fixed top-5 right-5 bg-white shadow-lg border-l-4 rounded-md p-4 flex items-center gap-3 z-[999999] transition-all duration-300">
        <span id="msgIcon"></span>
        <span id="msgTexto" class="font-medium"></span>
    </div>

    <!-- WRAPPER FLEX QUE RESOLVE O PROBLEMA DA ALTURA -->
    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- SIDEBAR -->
        <div class="hidden lg:block">
            <?php
                $tipo = $_SESSION['tipo'];

                if ($tipo === "administrador") {
                    include("sidebar_admin.php");
                } elseif ($tipo === "superadmin") {
                    include("sidebar_superadmin.php");
                } elseif ($tipo === "educador") {
                    include("sidebar_educador.php");
                } elseif ($tipo === "funcionario") {
                    include("sidebar_funcionario.php");
                } elseif ($tipo === "encarregado") {
                    include("sidebar_encarregado.php");
                }
            ?>
        </div>

        <!-- MENU MOBILE -->
        <?php
            $tipo = $_SESSION['tipo'];

            if ($tipo === "administrador") {
                include("menu_mobile_admin.php");
            } elseif ($tipo === "superadmin") {
                include("menu_mobile_superadmin.php");
            } elseif ($tipo === "educador") {
                include("menu_mobile_educador.php");
            } elseif ($tipo === "funcionario") {
                include("menu_mobile_funcionario.php");
            } elseif ($tipo === "encarregado") {
                include("menu_mobile_encarregado.php");
            }
        ?>

        <!-- CONTEÚDO -->
        <main class="flex-1 p-6 lg:p-10 lg:ml-[20%] overflow-y-auto">

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

<!-- SCRIPT Mostrar Toast -->
<?php if (isset($_GET['sucesso'])): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("editar", "Alterações guardadas com sucesso!");
});
</script>
<?php endif; ?>

<?php if (isset($_GET['erro'])): ?>
<script>
window.addEventListener("load", () => {
    mostrarMensagem("reset", "Ocorreu um erro ao atualizar os dados");
});
</script>
<?php endif; ?>


</body>
</html>
