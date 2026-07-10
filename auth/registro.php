<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $password_confirm = trim($_POST["password_confirm"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    
    if ($username !== "" && $nombre !== "" && $apellidos !== "" && $email !== "" && $password !== "" && $password_confirm !== "") {
        if ($password !== $password_confirm) {
            $error = "Las contraseñas no coinciden.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El formato del correo electrónico no es válido.";
        } else {
            // Verificar si el usuario ya existe (por username o email)
            $sqlCheck = "SELECT id_usuario FROM usuarios WHERE username = ? OR email = ? LIMIT 1";
            $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
            if (!$stmtCheck) {
                die("Error al preparar la verificación del registro: " . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtCheck, "ss", $username, $email);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
            
            if (mysqli_num_rows($resCheck) > 0) {
                $error = "El nombre de usuario o el correo electrónico ya están registrados.";
                mysqli_stmt_close($stmtCheck);
            } else {
                mysqli_stmt_close($stmtCheck);
                
                // Registrar usuario
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $rol = 'lector';
                $fecha_alta = date("Y-m-d");
                
                $sqlInsert = "INSERT INTO usuarios (username, password_hash, nombre, apellidos, email, telefono, rol, fecha_alta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = mysqli_prepare($conexion, $sqlInsert);
                if (!$stmtInsert) {
                    die("Error al preparar el registro de usuario: " . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmtInsert, "ssssssss", $username, $passwordHash, $nombre, $apellidos, $email, $telefono, $rol, $fecha_alta);
            
            if (mysqli_stmt_execute($stmtInsert)) {
                mysqli_stmt_close($stmtInsert);
                $_SESSION["success"] = "¡Registro exitoso! Ya puedes iniciar sesión.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Error al realizar el registro: " . mysqli_error($conexion);
                mysqli_stmt_close($stmtInsert);
            }
        }
      }
    } else {
        $error = "Por favor, completa todos los campos obligatorios (*).";
    }
}
?>
<?php
$pageTitle = "Registro - Movie Trailer Hub";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Registro de Usuario</h1>
    <p>Crea tu cuenta gratuita de lector para guardar tus trailers favoritos.</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form action="registro.php" method="POST">
        <label for="username">Nombre de Usuario *</label>
        <input type="text" id="username" name="username" required placeholder="Ej: lector123">

        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan">

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required placeholder="Ej: Pérez">

        <label for="email">Correo Electrónico *</label>
        <input type="email" id="email" name="email" required placeholder="Ej: juan.perez@email.com">

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" placeholder="Ej: 600123456">

        <label for="password">Contraseña *</label>
        <input type="password" id="password" name="password" required placeholder="Crea una contraseña segura">

        <label for="password_confirm">Confirmar Contraseña *</label>
        <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repite tu contraseña">

        <button type="submit">Crear Cuenta</button>
    </form>

    <div class="auth-footer">
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>

    <a class="volver" href="../index.php">← Volver al inicio</a>
<?php
require_once $rootPath . 'includes/footer.php';
?>
