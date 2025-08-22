<?php
// pages/users.php

require_once 'api/functions.php';

// Asegurarse de que solo usuarios logueados y con rol de administrador puedan acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php'); // Redirigir si no es admin
    exit;
}

$users = getUsers();
$message = '';
$messageType = '';

// Handle user actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'user';

                if (empty($username) || empty($password)) {
                    $message = 'El nombre de usuario y la contraseña no pueden estar vacíos.';
                    $messageType = 'danger';
                } else {
                    // Check if username already exists
                    $userExists = false;
                    foreach ($users as $user) {
                        if ($user['username'] === $username) {
                            $userExists = true;
                            break;
                        }
                    }

                    if ($userExists) {
                        $message = 'El nombre de usuario ya existe.';
                        $messageType = 'danger';
                    } else {
                        if (addUser($username, $password, $role)) {
                            $message = 'Usuario agregado exitosamente.';
                            $messageType = 'success';
                            $users = getUsers(); // Refresh users list
                        } else {
                            $message = 'Error al agregar el usuario.';
                            $messageType = 'danger';
                        }
                    }
                }
                break;
            case 'edit':
                $userId = $_POST['user_id'] ?? '';
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? ''; // Optional, only update if provided
                $role = $_POST['role'] ?? 'user';

                if (empty($username)) {
                    $message = 'El nombre de usuario no puede estar vacío.';
                    $messageType = 'danger';
                } else {
                    // Check if username already exists for another user
                    $userExists = false;
                    foreach ($users as $user) {
                        if ($user['username'] === $username && $user['id'] !== $userId) {
                            $userExists = true;
                            break;
                        }
                    }

                    if ($userExists) {
                        $message = 'El nombre de usuario ya existe para otro usuario.';
                        $messageType = 'danger';
                    } else {
                        if (editUser($userId, $username, $password, $role)) {
                            $message = 'Usuario actualizado exitosamente.';
                            $messageType = 'success';
                            $users = getUsers(); // Refresh users list
                        } else {
                            $message = 'Error al actualizar el usuario.';
                            $messageType = 'danger';
                        }
                    }
                }
                break;
            case 'delete':
                $userId = $_POST['user_id'] ?? '';
                // Prevent deleting the currently logged-in user or the last admin
                if ($userId === ($_SESSION['user_id'] ?? '')) {
                    $message = 'No puedes eliminar tu propia cuenta.';
                    $messageType = 'danger';
                } else {
                    $adminCount = 0;
                    foreach ($users as $user) {
                        if ($user['role'] === 'admin') {
                            $adminCount++;
                        }
                    }
                    $userToDeleteRole = '';
                    foreach ($users as $user) {
                        if ($user['id'] === $userId) {
                            $userToDeleteRole = $user['role'];
                            break;
                        }
                    }

                    if ($userToDeleteRole === 'admin' && $adminCount <= 1) {
                        $message = 'No puedes eliminar el último usuario administrador.';
                        $messageType = 'danger';
                    } else {
                        if (deleteUser($userId)) {
                            $message = 'Usuario eliminado exitosamente.';
                            $messageType = 'success';
                            $users = getUsers(); // Refresh users list
                        } else {
                            $message = 'Error al eliminar el usuario.';
                            $messageType = 'danger';
                        }
                    }
                }
                break;
        }
    }
}

?>

<h1 class="mt-4">Gestión de Usuarios</h1>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-user-plus mr-1"></i>
            Agregar/Editar Usuario
        </div>
        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#userFormCollapse" aria-expanded="false" aria-controls="userFormCollapse">
            <i class="fas fa-chevron-down"></i> Mostrar/Ocultar Formulario
        </button>
    </div>
    <div class="collapse" id="userFormCollapse">
        <div class="card-body">
            <form method="POST" id="userForm">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="user_id" id="user_id">
                <div class="form-group">
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña (dejar vacío para no cambiar)</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="form-group">
                    <label for="role">Rol</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="vendedor">Vendedor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Guardar Usuario</button>
                <button class="btn btn-secondary" type="button" onclick="clearUserForm()">Limpiar Formulario</button>
            </form>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-users mr-1"></i>
        Lista de Usuarios
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Usuario</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'] ?? '')); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='editUser(<?php echo json_encode($user); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este usuario?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No hay usuarios registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('action').value = 'edit';
    document.getElementById('user_id').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('password').value = ''; // Clear password field for security
    document.getElementById('role').value = user.role;
    $('#userFormCollapse').collapse('show');
}

function clearUserForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('user_id').value = '';
    document.getElementById('userForm').reset();
    $('#userFormCollapse').collapse('hide'); // Hide form after clearing
}

// Show form on page load if there's a message (e.g., after an error or successful submission)
$(document).ready(function() {
    <?php if ($message): ?>
        $('#userFormCollapse').collapse('show');
    <?php endif; ?>
});
</script>