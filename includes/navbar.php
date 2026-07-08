<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Movie Trailer Hub'; ?></title>
    
    <meta name="description" content="Guarda, organiza y disfruta de los mejores trailers de tus películas favoritas. Tu hub centralizado de cine.">
    
    <link rel="icon" type="image/png" href="<?php echo $rootPath; ?>images/logo movie trailer hub (1) (1).png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $rootPath; ?>css/estilos.css">
</head>
<body>

<?php if (!isset($showNavbar) || $showNavbar !== false): ?>
    <!-- Navegación principal -->
    <header class="navbar">
        <div class="app-container navbar-content">
            <a href="<?php echo $rootPath; ?>index.php" class="brand">
                <img src="<?php echo $rootPath; ?>images/logo movie trailer hub (1) (1).png" alt="Logo" class="brand-icon">
                <h1 class="brand-name">Movie Trailer Hub</h1>
            </a>
            <div class="nav-actions">
                <a href="<?php echo $rootPath; ?>trailers/estadisticas.php" class="btn btn-secondary">
                    <i class="fa-solid fa-chart-simple"></i> Estadísticas
                </a>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="<?php echo $rootPath; ?>trailers/favoritos.php" class="btn btn-secondary btn-favoritos">
                        <i class="fa-solid fa-heart"></i> Mis Favoritos
                    </a>

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle">
                                <i class="fa-solid fa-gear"></i> Gestión
                            </button>
                            <div class="dropdown-menu">
                                <a href="<?php echo $rootPath; ?>trailers/listar_trailers.php" class="dropdown-item">
                                    <i class="fa-solid fa-list"></i> Administrar Trailers
                                </a>
                                <a href="<?php echo $rootPath; ?>trailers/listar_reparto.php" class="dropdown-item">
                                    <i class="fa-solid fa-users-gear"></i> Administrar Actores
                                </a>
                                <a href="<?php echo $rootPath; ?>trailers/listar_directores.php" class="dropdown-item">
                                    <i class="fa-solid fa-user-gear"></i> Administrar Directores
                                </a>
                                <a href="<?php echo $rootPath; ?>trailers/añadir_trailer.php" class="dropdown-item">
                                    <i class="fa-solid fa-plus"></i> Añadir Trailer
                                </a>
                                <a href="<?php echo $rootPath; ?>trailers/añadir_reparto.php" class="dropdown-item">
                                    <i class="fa-solid fa-user-plus"></i> Añadir Actor
                                </a>
                                <a href="<?php echo $rootPath; ?>trailers/añadir_director.php" class="dropdown-item">
                                    <i class="fa-solid fa-user-plus"></i> Añadir Director
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <span class="user-greeting">
                        <i class="fa-solid fa-circle-user"></i>Hola, <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>

                    <a href="<?php echo $rootPath; ?>auth/logout.php" class="btn btn-secondary">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </a>
                <?php else: ?>
                    <a href="<?php echo $rootPath; ?>auth/login.php" class="btn btn-secondary">
                        <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
                    </a>
                    <a href="<?php echo $rootPath; ?>auth/registro.php" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
<?php endif; ?>
