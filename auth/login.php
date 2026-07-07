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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Movie Trailer Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <h1>Iniciar Sesión</h1>
    <p>Accede a tu cuenta para guardar tus trailers favoritos.</p>

    <?php if ($error): ?>
        <div class="alert alert-error" style="max-width: 550px; margin: 0 auto 20px auto; display: flex; align-items: center; gap: 10px; padding: 12px 20px; background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.3); border-radius: var(--radius-md); color: #ffffff;">
            <i class="fa-solid fa-circle-exclamation" style="color: var(--secondary);"></i>
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

    <div style="text-align: center; margin-top: 15px;">
        <p style="font-size: 14px; color: var(--text-muted);">¿No tienes una cuenta? <a href="registro.php" style="color: var(--primary); text-decoration: underline;">Regístrate aquí</a></p>
    </div>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</body>
</html>
