<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
define('BASE_PATH', '../');

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf();

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    
    if ($username !== "" && $password !== "") {
        try {
            ensure_login_attempts_table($conexion);

            if (is_login_rate_limited($conexion, $username)) {
                $error = "No se pudo iniciar sesión. Inténtalo de nuevo más tarde.";
            } else {
                $sql = "SELECT id_usuario, username, password_hash, nombre, rol, avatar_url
                        FROM usuarios
                        WHERE username = ?
                        LIMIT 1";
                $stmt = mysqli_prepare($conexion, $sql);

                if (!$stmt) {
                    throw new RuntimeException("No se pudo preparar el inicio de sesión: " . mysqli_error($conexion));
                }

                mysqli_stmt_bind_param($stmt, "s", $username);

                if (!mysqli_stmt_execute($stmt)) {
                    $databaseError = mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    throw new RuntimeException("No se pudo consultar el usuario: " . $databaseError);
                }

                $res = mysqli_stmt_get_result($stmt);
                $usuario = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);

                $passwordHash = $usuario["password_hash"]
                    ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
                $passwordIsValid = password_verify($password, $passwordHash);

                if ($usuario && $passwordIsValid) {
                    clear_login_failures($conexion, $username);
                    session_regenerate_id(true);

                    $_SESSION["usuario_id"] = $usuario["id_usuario"];
                    $_SESSION["username"] = $usuario["username"];
                    $_SESSION["nombre"] = $usuario["nombre"];
                    $_SESSION["rol"] = $usuario["rol"];
                    $_SESSION["avatar_url"] = $usuario["avatar_url"] ?? null;

                    $now = time();
                    $_SESSION['auth_started_at'] = $now;
                    $_SESSION['last_activity_at'] = $now;

                    $_SESSION["success"] = "¡Bienvenido de nuevo, " . $usuario["nombre"] . "!";
                    header("Location: ../index.php");
                    exit;
                }

                register_login_failure($conexion, $username);
                $error = "Nombre de usuario o contraseña incorrectos.";
            }
        } catch (RuntimeException $exception) {
            error_log($exception->getMessage());
            http_response_code(503);
            $error = "No se pudo procesar el inicio de sesión. Inténtalo de nuevo más tarde.";
        }
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}
?>
<?php
$pageTitle = "Iniciar Sesión - Movie Trailer Hub";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Iniciar Sesión</h1>
    <p>Accede a tu cuenta para guardar tus trailers favoritos.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <?= csrf_field() ?>
        <label for="username">Nombre de Usuario *</label>
        <input type="text" id="username" name="username" required placeholder="Ej: admin">

        <label for="password">Contraseña *</label>
        <input type="password" id="password" name="password" required placeholder="Ingresa tu contraseña">

        <button type="submit">Iniciar Sesión</button>
    </form>

    <div class="auth-footer">
        <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>

    <a class="volver" href="../index.php">← Volver al inicio</a>
<?php
require_once $rootPath . 'includes/footer.php';
?>
