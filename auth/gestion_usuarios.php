<?php
// auth/gestion_usuarios.php
require_once "../config/conexion.php";

// 1. Validar accesos de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}

define('BASE_PATH', '../');

// Generar token CSRF seguro si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Cargar todos los usuarios inicialmente desde PHP para evitar saltos visuales
$sql = "SELECT u.*,
               (SELECT COUNT(*) FROM favoritos f WHERE f.id_usuario = u.id_usuario) as total_favoritos,
               (SELECT COUNT(*) FROM resenas r WHERE r.id_usuario = u.id_usuario) as total_resenas,
               (SELECT COUNT(*) FROM visualizaciones v WHERE v.id_usuario = u.id_usuario) as total_vistas
        FROM usuarios u
        ORDER BY u.id_usuario DESC";
$result = mysqli_query($conexion, $sql);
?>
<?php
$pageTitle = "Gestión de Usuarios - Movie Trailer Hub";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

<!-- Estilos específicos e integrados para el módulo de gestión de usuarios -->
<style>
    .admin-controls-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 25px;
        background: var(--bg-surface);
        padding: 16px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
    }
    .filters-group {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        flex-grow: 1;
        max-width: 600px;
    }
    .search-input-wrapper {
        position: relative;
        flex-grow: 1;
    }
    .search-input-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }
    .search-input-wrapper input {
        width: 100%;
        padding: 10px 12px 10px 38px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background: var(--bg-base);
        color: var(--text-primary);
        font-size: 14px;
        transition: var(--transition-smooth);
    }
    .search-input-wrapper input:focus {
        border-color: var(--border-color-focus);
        outline: none;
        box-shadow: 0 0 0 2px var(--primary-glow);
    }
    .filter-select {
        padding: 10px 30px 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background: var(--bg-base);
        color: var(--text-primary);
        font-size: 14px;
        cursor: pointer;
        outline: none;
        transition: var(--transition-smooth);
    }
    .filter-select:focus {
        border-color: var(--border-color-focus);
    }
    
    /* Roles badges */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .role-admin {
        background: rgba(245, 158, 11, 0.15);
        color: var(--primary);
        border: 1px solid rgba(245, 158, 11, 0.3);
    }
    .role-lector {
        background: rgba(216, 227, 251, 0.08);
        color: var(--text-primary);
        border: 1px solid rgba(216, 227, 251, 0.15);
    }
    
    /* Estilos del modal dinámico */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(4, 14, 31, 0.85);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1100;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.open {
        opacity: 1;
        pointer-events: auto;
    }
    .modal-content-card {
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        width: 100%;
        max-width: 550px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 30px;
        box-shadow: 0 20px 45px rgba(0,0,0,0.6);
        transform: translateY(-30px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .modal-overlay.open .modal-content-card {
        transform: translateY(0);
    }
    .modal-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 12px;
    }
    .modal-header-row h3 {
        margin: 0;
        color: var(--text-primary);
        font-family: var(--font-headline);
        font-size: 1.3rem;
    }
    .modal-close-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 20px;
        cursor: pointer;
        transition: var(--transition-smooth);
    }
    .modal-close-btn:hover {
        color: var(--secondary);
    }
    .modal-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .full-width-field {
        grid-column: 1 / -1;
    }
    .form-group-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group-item label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-primary);
    }
    .form-group-item input,
    .form-group-item select {
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background: var(--bg-base);
        color: var(--text-primary);
        font-size: 14px;
        transition: var(--transition-smooth);
        outline: none;
    }
    .form-group-item input:focus,
    .form-group-item select:focus {
        border-color: var(--border-color-focus);
        box-shadow: 0 0 0 2px var(--primary-glow);
    }
    .form-group-item input[readonly] {
        background: rgba(4, 14, 31, 0.5);
        color: var(--text-muted);
        cursor: not-allowed;
    }
    .modal-actions-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 25px;
        border-top: 1px solid var(--border-color);
        padding-top: 15px;
    }
    .user-table-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid var(--border-color);
    }
    .user-table-avatar-fallback {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(216, 227, 251, 0.08);
        border: 1px solid var(--border-color);
        color: var(--text-muted);
    }
    .stats-info-pill {
        font-size: 11px;
        color: var(--text-muted);
        display: inline-flex;
        gap: 8px;
    }
    .stats-info-pill span {
        background: rgba(4, 14, 31, 0.4);
        padding: 2px 6px;
        border-radius: 4px;
    }
</style>

<main class="app-container" style="margin-top: 30px; margin-bottom: 60px;">
    <div style="margin-bottom: 25px;">
        <h1 style="margin-bottom: 8px;">Gestión de Usuarios</h1>
        <p style="color: var(--text-muted); margin: 0;">Panel de administración para supervisar, registrar, editar y dar de baja usuarios del sistema.</p>
    </div>

    <!-- Controles de búsqueda y filtros -->
    <div class="admin-controls-row">
        <div class="filters-group">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="userSearchInput" placeholder="Buscar por usuario, nombre, email..." oninput="applyFilters()">
            </div>
            <select id="userRoleFilter" class="filter-select" onchange="applyFilters()">
                <option value="todos">Todos los roles</option>
                <option value="admin">Administrador</option>
                <option value="lector">Lector</option>
            </select>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="fa-solid fa-user-plus"></i> Crear Usuario
        </button>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Actividad</th>
                    <th>Rol</th>
                    <th>Fecha de Alta</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php while ($user = mysqli_fetch_assoc($result)): 
                    $isSelf = ((int)$user['id_usuario'] === (int)$_SESSION['usuario_id']);
                ?>
                    <tr id="user-row-<?= $user['id_usuario'] ?>" 
                        data-id="<?= $user['id_usuario'] ?>"
                        data-username="<?= htmlspecialchars($user['username']) ?>"
                        data-nombre="<?= htmlspecialchars($user['nombre']) ?>"
                        data-apellidos="<?= htmlspecialchars($user['apellidos']) ?>"
                        data-email="<?= htmlspecialchars($user['email']) ?>"
                        data-telefono="<?= htmlspecialchars($user['telefono'] ?? '') ?>"
                        data-avatar="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>"
                        data-rol="<?= htmlspecialchars($user['rol']) ?>">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php if (!empty($user['avatar_url'])): ?>
                                    <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar" class="user-table-avatar">
                                <?php else: ?>
                                    <div class="user-table-avatar-fallback">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <strong>@<?= htmlspecialchars($user['username']) ?></strong>
                                <?php if ($isSelf): ?>
                                    <span style="font-size: 10px; padding: 2px 5px; background: rgba(220,38,38,0.2); color:#ef4444; border-radius: 4px; font-weight: 700; border:1px solid rgba(220,38,38,0.3)">TÚ</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['telefono'] ?? '—') ?></td>
                        <td>
                            <div class="stats-info-pill">
                                <span><i class="fa-solid fa-heart" title="Favoritos" style="color:var(--secondary)"></i> <?= $user['total_favoritos'] ?></span>
                                <span><i class="fa-solid fa-comments" title="Reseñas" style="color:var(--primary)"></i> <?= $user['total_resenas'] ?></span>
                                <span><i class="fa-solid fa-play" title="Vistas"></i> <?= $user['total_vistas'] ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge role-<?= $user['rol'] ?>">
                                <i class="fa-solid <?= $user['rol'] === 'admin' ? 'fa-shield-halved' : 'fa-user' ?>"></i>
                                <?= $user['rol'] === 'admin' ? 'Administrador' : 'Lector' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($user['fecha_alta'])) ?></td>
                        <td class="text-center nowrap">
                            <button class="btn-tabla btn-modificar" onclick="openEditModal(this.closest('tr'))">
                                <i class="fa-solid fa-pen"></i> Editar
                            </button>
                            <?php if (!$isSelf): ?>
                                <button class="btn-tabla btn-eliminar" onclick="deleteUser(<?= $user['id_usuario'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="fa-solid fa-trash-can"></i> Eliminar
                                </button>
                            <?php else: ?>
                                <button class="btn-tabla btn-eliminar" disabled title="No puedes eliminar tu propia cuenta" style="opacity: 0.4; cursor: not-allowed;">
                                    <i class="fa-solid fa-trash-can"></i> Eliminar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</main>

<!-- Modal Único de Creación / Modificación de Usuarios -->
<div class="modal-overlay" id="userFormModal">
    <div class="modal-content-card">
        <div class="modal-header-row">
            <h3 id="modalTitle">Nuevo Usuario</h3>
            <button class="modal-close-btn" onclick="closeUserModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form id="userForm" onsubmit="submitUserForm(event)">
            <!-- Input oculto para identificar actualización -->
            <input type="hidden" id="formUserId" name="id_usuario" value="0">
            
            <div class="modal-form-grid">
                <!-- Username -->
                <div class="form-group-item" id="usernameFieldGroup">
                    <label for="formUsername">Nombre de Usuario *</label>
                    <input type="text" id="formUsername" name="username" placeholder="lector123" required>
                </div>
                
                <!-- Rol -->
                <div class="form-group-item">
                    <label for="formRol">Rol del Sistema *</label>
                    <select id="formRol" name="rol" required>
                        <option value="lector">Lector</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <!-- Nombre -->
                <div class="form-group-item">
                    <label for="formNombre">Nombre *</label>
                    <input type="text" id="formNombre" name="nombre" placeholder="Ej: Carlos" required>
                </div>
                
                <!-- Apellidos -->
                <div class="form-group-item">
                    <label for="formApellidos">Apellidos *</label>
                    <input type="text" id="formApellidos" name="apellidos" placeholder="Ej: Ruiz Casas" required>
                </div>
                
                <!-- Correo Electrónico -->
                <div class="form-group-item full-width-field">
                    <label for="formEmail">Correo Electrónico *</label>
                    <input type="email" id="formEmail" name="email" placeholder="Ej: carlos.ruiz@email.com" required>
                </div>
                
                <!-- Teléfono -->
                <div class="form-group-item">
                    <label for="formTelefono">Teléfono</label>
                    <input type="text" id="formTelefono" name="telefono" placeholder="Ej: 600111222">
                </div>
                
                <!-- Avatar URL -->
                <div class="form-group-item">
                    <label for="formAvatarUrl">URL de Imagen Avatar</label>
                    <input type="url" id="formAvatarUrl" name="avatar_url" placeholder="https://ejemplo.com/avatar.jpg">
                </div>
                
                <!-- Contraseña -->
                <div class="form-group-item" id="passwordFieldGroup">
                    <label for="formPassword" id="passwordLabel">Contraseña *</label>
                    <input type="password" id="formPassword" name="password" placeholder="Mínimo 6 caracteres">
                </div>
                
                <!-- Confirmar Contraseña -->
                <div class="form-group-item" id="passwordConfirmFieldGroup">
                    <label for="formPasswordConfirm" id="passwordConfirmLabel">Confirmar Contraseña *</label>
                    <input type="password" id="formPasswordConfirm" placeholder="Repite la contraseña">
                </div>
            </div>
            
            <div class="modal-actions-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="formSubmitBtn"><i class="fa-solid fa-save"></i> Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Token CSRF generado en sesión PHP
    const csrfToken = "<?= $_SESSION['csrf_token'] ?>";
    const loggedUserId = <?= (int)$_SESSION['usuario_id'] ?>;

    const modal = document.getElementById('userFormModal');
    const form = document.getElementById('userForm');
    const formUserId = document.getElementById('formUserId');
    const formUsername = document.getElementById('formUsername');
    const formRol = document.getElementById('formRol');
    const formNombre = document.getElementById('formNombre');
    const formApellidos = document.getElementById('formApellidos');
    const formEmail = document.getElementById('formEmail');
    const formTelefono = document.getElementById('formTelefono');
    const formAvatarUrl = document.getElementById('formAvatarUrl');
    const formPassword = document.getElementById('formPassword');
    const formPasswordConfirm = document.getElementById('formPasswordConfirm');
    
    const modalTitle = document.getElementById('modalTitle');
    const formSubmitBtn = document.getElementById('formSubmitBtn');
    
    const passwordLabel = document.getElementById('passwordLabel');
    const passwordConfirmLabel = document.getElementById('passwordConfirmLabel');
    
    // Filtrado en tiempo real (Client-side)
    function applyFilters() {
        const searchVal = document.getElementById('userSearchInput').value.toLowerCase().trim();
        const roleVal = document.getElementById('userRoleFilter').value;
        const rows = document.querySelectorAll('#usersTableBody tr');
        
        rows.forEach(row => {
            const username = row.dataset.username.toLowerCase();
            const nombre = row.dataset.nombre.toLowerCase();
            const apellidos = row.dataset.apellidos.toLowerCase();
            const email = row.dataset.email.toLowerCase();
            const rol = row.dataset.rol;
            
            const matchSearch = username.includes(searchVal) || nombre.includes(searchVal) || apellidos.includes(searchVal) || email.includes(searchVal);
            const matchRole = roleVal === 'todos' || rol === roleVal;
            
            if (matchSearch && matchRole) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Abrir Modal: Modo Creación
    function openCreateModal() {
        form.reset();
        formUserId.value = "0";
        modalTitle.innerText = "Registrar Nuevo Usuario";
        formSubmitBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Crear Usuario';
        
        // El nombre de usuario y contraseña son obligatorios al crear
        formUsername.readOnly = false;
        formUsername.required = true;
        formPassword.required = true;
        formPasswordConfirm.required = true;
        
        passwordLabel.innerText = "Contraseña *";
        passwordConfirmLabel.innerText = "Confirmar Contraseña *";
        
        // Habilitar selección de rol libremente
        formRol.disabled = false;
        
        modal.classList.add('open');
    }

    // Abrir Modal: Modo Edición
    function openEditModal(row) {
        form.reset();
        
        const id = row.dataset.id;
        const username = row.dataset.username;
        const nombre = row.dataset.nombre;
        const apellidos = row.dataset.apellidos;
        const email = row.dataset.email;
        const telefono = row.dataset.telefono;
        const avatar = row.dataset.avatar;
        const rol = row.dataset.rol;
        
        formUserId.value = id;
        formUsername.value = username;
        formNombre.value = nombre;
        formApellidos.value = apellidos;
        formEmail.value = email;
        formTelefono.value = telefono;
        formAvatarUrl.value = avatar;
        formRol.value = rol;
        
        modalTitle.innerText = `Modificar Usuario: @${username}`;
        formSubmitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Cambios';
        
        // En edición, el username es inmutable y la contraseña es opcional
        formUsername.readOnly = true;
        formUsername.required = false;
        formPassword.required = false;
        formPasswordConfirm.required = false;
        
        passwordLabel.innerText = "Nueva Contraseña (opcional)";
        passwordConfirmLabel.innerText = "Confirmar Nueva Contraseña";
        
        // Si el usuario es el propio administrador logueado, no puede cambiarse el rol a lector
        if (parseInt(id) === loggedUserId) {
            formRol.disabled = true;
        } else {
            formRol.disabled = false;
        }
        
        modal.classList.add('open');
    }

    // Cerrar Modal
    function closeUserModal() {
        modal.classList.remove('open');
    }

    // Escuchar la tecla de Escape para cerrar el modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
            closeUserModal();
        }
    });

    // Enviar formulario (Creación / Edición)
    function submitUserForm(event) {
        event.preventDefault();
        
        // Validar contraseñas
        const pass = formPassword.value;
        const passConf = formPasswordConfirm.value;
        
        if (pass !== '' && pass !== passConf) {
            showToast('Las contraseñas no coinciden.', 'error');
            return;
        }
        
        if (pass !== '' && pass.length < 6) {
            showToast('La contraseña debe tener al menos 6 caracteres.', 'error');
            return;
        }

        const id = formUserId.value;
        const isEdit = id !== "0";
        const actionUrl = isEdit ? 'api_usuarios.php?action=update' : 'api_usuarios.php?action=create';
        
        // Usar FormData para enviar los campos cómodamente
        const formData = new FormData(form);
        
        // Si el rol está deshabilitado (automedición del admin logueado), no se envía en FormData por defecto, lo forzamos
        if (formRol.disabled) {
            formData.set('rol', 'admin');
        }

        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error en el servidor') });
            }
            return res.json();
        })
        .then(data => {
            showToast(data.success, 'success');
            closeUserModal();
            // Recargar la página tras 1 segundo para refrescar la tabla de forma limpia y mostrar las estadísticas correspondientes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch(err => {
            console.error(err);
            showToast(err.message, 'error');
        });
    }

    // Eliminar Usuario
    function deleteUser(id, username) {
        if (id === loggedUserId) {
            showToast('No puedes eliminar tu propia cuenta de administrador.', 'error');
            return;
        }

        if (!confirm(`¿Estás completamente seguro de que deseas eliminar permanentemente al usuario @${username}?\nEsta acción es irreversible y eliminará todas sus reseñas, favoritos y racha de logins en cascada.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('id_usuario', id);

        fetch('api_usuarios.php?action=delete', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.error || 'Error en el servidor') });
            }
            return res.json();
        })
        .then(data => {
            showToast(data.success, 'success');
            
            // Efecto visual de desvanecimiento
            const row = document.getElementById(`user-row-${id}`);
            if (row) {
                row.style.transition = 'all 0.4s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.remove();
                }, 400);
            }
        })
        .catch(err => {
            console.error(err);
            showToast(err.message, 'error');
        });
    }
</script>

<?php
require_once $rootPath . 'includes/footer.php';
?>
