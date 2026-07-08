<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    
    if ($username !== "" && $password !== "") {
        $sql = "SELECT * FROM usuarios WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        
        if ($usuario && password_verify($password, $usuario["password_hash"])) {
            $_SESSION["usuario_id"] = $usuario["id_usuario"];
            $_SESSION["username"] = $usuario["username"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["rol"] = $usuario["rol"];
            
            $_SESSION["success"] = "¡Bienvenido de nuevo, " . $usuario["nombre"] . "!";
            header("Location: ../index.php");
            exit;
        } else {
            $error = "Nombre de usuario o contraseña incorrectos.";
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
</body>
</html>
