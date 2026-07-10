<?php
require_once "../config/conexion.php";

// 1. Validar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = "Debes iniciar sesión para acceder a tu perfil.";
    header("Location: login.php");
    exit;
}

define('BASE_PATH', '../');

// 2. Automigración de la base de datos (Añadir columna avatar_url si no existe)
$checkCol = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'avatar_url'");
if (mysqli_num_rows($checkCol) == 0) {
    mysqli_query($conexion, "ALTER TABLE usuarios ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL");
}

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$user_id = $_SESSION['usuario_id'];
$error = null;

// 3. Procesar el formulario de edición
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $avatar_url = trim($_POST["avatar_url"] ?? "");
    
    $password_actual = trim($_POST["password_actual"] ?? "");
    $password_nueva = trim($_POST["password_nueva"] ?? "");
    $password_confirm = trim($_POST["password_confirm"] ?? "");

    // Validar datos básicos
    if ($nombre === "" || $apellidos === "" || $email === "") {
        $error = "Nombre, Apellidos y Correo Electrónico son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no tiene un formato válido.";
    } else {
        // Verificar que el correo no esté ocupado por otro usuario
        $sqlEmail = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ? LIMIT 1";
        $stmtEmail = mysqli_prepare($conexion, $sqlEmail);
        if ($stmtEmail) {
            mysqli_stmt_bind_param($stmtEmail, "si", $email, $user_id);
            mysqli_stmt_execute($stmtEmail);
            $resEmail = mysqli_stmt_get_result($stmtEmail);
            if (mysqli_num_rows($resEmail) > 0) {
                $error = "El correo electrónico ya está registrado por otro usuario.";
            }
            mysqli_stmt_close($stmtEmail);
        } else {
            $error = "Error al verificar el correo electrónico en la base de datos.";
        }

        // Si no hay error con el correo, evaluar la contraseña
        if (!$error) {
            $cambiar_pass = false;
            $pass_hash = "";

            if ($password_actual !== "" || $password_nueva !== "" || $password_confirm !== "") {
                if ($password_actual === "" || $password_nueva === "" || $password_confirm === "") {
                    $error = "Para cambiar tu contraseña debes rellenar la actual, la nueva y su confirmación.";
                } elseif ($password_nueva !== $password_confirm) {
                    $error = "La nueva contraseña y la confirmación no coinciden.";
                } else {
                    // Obtener la contraseña actual de la DB para verificarla
                    $sqlHash = "SELECT password_hash FROM usuarios WHERE id_usuario = ? LIMIT 1";
                    $stmtHash = mysqli_prepare($conexion, $sqlHash);
                    if ($stmtHash) {
                        mysqli_stmt_bind_param($stmtHash, "i", $user_id);
                        mysqli_stmt_execute($stmtHash);
                        $resHash = mysqli_stmt_get_result($stmtHash);
                        $rowHash = mysqli_fetch_assoc($resHash);
                        mysqli_stmt_close($stmtHash);

                        if ($rowHash && password_verify($password_actual, $rowHash['password_hash'])) {
                            $cambiar_pass = true;
                            $pass_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                        } else {
                            $error = "La contraseña actual es incorrecta.";
                        }
                    } else {
                        $error = "Error al verificar la contraseña en la base de datos.";
                    }
                }
            }
        }

        // Proceder con la actualización si no hay errores
        if (!$error) {
            if ($cambiar_pass) {
                $sqlUpdate = "UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, telefono = ?, avatar_url = ?, password_hash = ? WHERE id_usuario = ?";
                $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
                if ($stmtUpdate) {
                    mysqli_stmt_bind_param($stmtUpdate, "ssssssi", $nombre, $apellidos, $email, $telefono, $avatar_url, $pass_hash, $user_id);
                }
            } else {
                $sqlUpdate = "UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, telefono = ?, avatar_url = ? WHERE id_usuario = ?";
                $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
                if ($stmtUpdate) {
                    mysqli_stmt_bind_param($stmtUpdate, "sssssi", $nombre, $apellidos, $email, $telefono, $avatar_url, $user_id);
                }
            }

            if ($stmtUpdate) {
                if (mysqli_stmt_execute($stmtUpdate)) {
                    $_SESSION["success"] = "Perfil actualizado correctamente.";
                    $_SESSION["nombre"] = $nombre;
                    $_SESSION["avatar_url"] = $avatar_url !== "" ? $avatar_url : null;
                    mysqli_stmt_close($stmtUpdate);
                    header("Location: perfil.php");
                    exit;
                } else {
                    $error = "Error al actualizar los datos en la base de datos.";
                }
                mysqli_stmt_close($stmtUpdate);
            } else {
                $error = "Error de preparación SQL al actualizar el perfil.";
            }
        }
    }
    
    if ($error) {
        $_SESSION["error"] = $error;
        header("Location: perfil.php");
        exit;
    }
}

// 4. Obtener datos actuales del usuario para llenar el formulario
$sqlUser = "SELECT * FROM usuarios WHERE id_usuario = ? LIMIT 1";
$stmtUser = mysqli_prepare($conexion, $sqlUser);
if (!$stmtUser) {
    die("Error al preparar la consulta de datos del usuario: " . mysqli_error($conexion));
}
mysqli_stmt_bind_param($stmtUser, "i", $user_id);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$user = mysqli_fetch_assoc($resUser);
mysqli_stmt_close($stmtUser);

if (!$user) {
    die("Usuario no encontrado en el sistema.");
}

$pageTitle = "Mi Perfil - Movie Trailer Hub";
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

<main class="app-container" style="margin-top: 30px; margin-bottom: 50px;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin-bottom: 8px;">Configuración de la Cuenta</h1>
        <p style="color: var(--text-muted); margin: 0;">Administra tus datos personales, avatar y credenciales de acceso.</p>
    </div>



    <div class="profile-layout">
        
        <!-- Tarjeta Lateral Izquierda (Resumen) -->
        <aside class="profile-sidebar">
            <div id="avatarPreviewContainer" class="profile-avatar-preview">
                <?php if (!empty($user['avatar_url'])): ?>
                    <img id="avatarImg" src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <i id="avatarIcon" class="fa-solid fa-user"></i>
                    <img id="avatarImg" src="" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: none;">
                <?php endif; ?>
            </div>
            
            <h3 class="profile-username"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?></h3>
            <span class="profile-role-badge"><?php echo htmlspecialchars($user['rol'] === 'admin' ? 'Administrador' : 'Lector'); ?></span>
            
            <div class="profile-meta-info">
                <div class="profile-meta-row">
                    <span class="profile-meta-label">Usuario:</span>
                    <span class="profile-meta-value">@<?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="profile-meta-row">
                    <span class="profile-meta-label">Miembro desde:</span>
                    <span class="profile-meta-value"><?php echo date('d/m/Y', strtotime($user['fecha_alta'])); ?></span>
                </div>
            </div>
        </aside>

        <!-- Formulario de Configuración -->
        <section class="profile-form-container">
            <form action="perfil.php" method="POST" autocomplete="off">
                
                <h3 class="profile-section-title"><i class="fa-solid fa-id-card"></i> Datos Personales</h3>
                
                <div class="profile-form-grid">
                    <div>
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($user['nombre']); ?>">
                    </div>
                    <div>
                        <label for="apellidos">Apellidos *</label>
                        <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($user['apellidos']); ?>">
                    </div>
                </div>

                <div class="profile-form-grid" style="margin-top: 15px;">
                    <div>
                        <label for="email">Correo Electrónico *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div>
                        <label for="telefono">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>" placeholder="Ej: 600123456">
                    </div>
                </div>

                <div class="profile-form-grid full-width" style="margin-top: 15px; margin-bottom: 30px;">
                    <div>
                        <label for="avatar_url">URL de la Imagen de Avatar</label>
                        <input type="url" id="avatar_url" name="avatar_url" value="<?php echo htmlspecialchars($user['avatar_url'] ?? ''); ?>" placeholder="Ej: https://enlace-de-imagen.jpg/avatar.png">
                    </div>
                </div>

                <h3 class="profile-section-title"><i class="fa-solid fa-lock"></i> Seguridad y Acceso</h3>
                
                <div class="profile-form-grid full-width">
                    <div>
                        <label for="password_actual">Contraseña Actual (Requerida solo si vas a cambiarla)</label>
                        <input type="password" id="password_actual" name="password_actual" placeholder="Escribe tu contraseña actual" autocomplete="new-password">
                    </div>
                </div>

                <div class="profile-form-grid" style="margin-top: 15px; margin-bottom: 25px;">
                    <div>
                        <label for="password_nueva">Nueva Contraseña</label>
                        <input type="password" id="password_nueva" name="password_nueva" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div>
                        <label for="password_confirm">Confirmar Nueva Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Repite la nueva contraseña">
                    </div>
                </div>

                <button type="submit">Guardar Cambios</button>
            </form>
        </section>

    </div>

    <div style="margin-top: 30px;">
        <a class="volver" href="../index.php">← Volver al inicio</a>
    </div>

</main>

<script>
function closeToast(id) {
    const toast = document.getElementById(id);
    if (toast) {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => {
            toast.remove();
        }, 400);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar toasts
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach((toast) => {
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            closeToast(toast.id);
        }, 4000);
    });

    const avatarInput = document.getElementById('avatar_url');
    const avatarImg = document.getElementById('avatarImg');
    const avatarIcon = document.getElementById('avatarIcon');

    // Escuchar cambios en la URL del avatar para previsualización inmediata
    avatarInput.addEventListener('input', () => {
        const url = avatarInput.value.trim();
        if (url !== "") {
            avatarImg.src = url;
            avatarImg.style.display = 'block';
            if (avatarIcon) avatarIcon.style.display = 'none';
        } else {
            avatarImg.src = "";
            avatarImg.style.display = 'none';
            if (avatarIcon) avatarIcon.style.display = 'block';
        }
    });

    // Validar que si intentan cambiar contraseña completen todos los campos
    const form = document.querySelector('form');
    const passActual = document.getElementById('password_actual');
    const passNueva = document.getElementById('password_nueva');
    const passConfirm = document.getElementById('password_confirm');

    form.addEventListener('submit', (e) => {
        const valActual = passActual.value.trim();
        const valNueva = passNueva.value.trim();
        const valConfirm = passConfirm.value.trim();

        if (valActual !== "" || valNueva !== "" || valConfirm !== "") {
            if (valActual === "" || valNueva === "" || valConfirm === "") {
                e.preventDefault();
                alert("Por favor completa los tres campos de contraseña (actual, nueva y confirmación).");
                return;
            }
            if (valNueva !== valConfirm) {
                e.preventDefault();
                alert("La nueva contraseña y la confirmación no coinciden.");
                return;
            }
            if (valNueva.length < 6) {
                e.preventDefault();
                alert("La nueva contraseña debe tener al menos 6 caracteres.");
                return;
            }
        }
    });
});
</script>

<!-- Toast Notification Container -->
<div class="toast-container" id="toastContainer">
    <?php if ($successMsg): ?>
        <div class="toast toast-success" id="successToast">
            <i class="fa-solid fa-circle-check toast-icon"></i>
            <div class="toast-message"><?= htmlspecialchars($successMsg) ?></div>
            <button class="toast-close" onclick="closeToast('successToast')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="toast toast-error" id="errorToast">
            <i class="fa-solid fa-circle-exclamation toast-icon"></i>
            <div class="toast-message"><?= htmlspecialchars($errorMsg) ?></div>
            <button class="toast-close" onclick="closeToast('errorToast')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>
</div>

<?php
require_once $rootPath . 'includes/footer.php';
mysqli_close($conexion);
?>
