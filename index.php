<?php
// index.php - Punto de entrada principal del sistema
// Aquí se manejará el enrutamiento y la carga de las diferentes secciones.

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Incluir funciones de configuración y datos
require_once 'api/functions.php';

// Obtener la configuración del sistema
$settings = getSettings();

// Definir la página por defecto
$page = $_GET['page'] ?? 'dashboard'; // Por ejemplo, 'dashboard', 'sales', 'inventory', 'settings'

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['system_title'] ?? 'Sistema de Venta e Inventario'); ?></title>
    <!-- Incluir CSS de Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-right" id="sidebar-wrapper">
            <div class="sidebar-heading d-flex align-items-center justify-content-center py-3">
                <?php if (!empty($settings['company_logo'])): ?>
                    <img src="<?php echo htmlspecialchars($settings['company_logo']); ?>" alt="Logo" class="img-fluid rounded-circle mr-2" style="max-width: 50px; max-height: 50px; object-fit: cover;">
                <?php endif; ?>
                <h5 class="mb-0"><?php echo htmlspecialchars($settings['company_name'] ?? 'Smart Jireh'); ?></h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="?page=dashboard" class="list-group-item list-group-item-action bg-light">Dashboard</a>
                <a href="?page=sales" class="list-group-item list-group-item-action bg-light">Ventas</a>
                <a href="?page=sales_report" class="list-group-item list-group-item-action bg-light">Lista de Ventas</a>
                <a href="?page=inventory" class="list-group-item list-group-item-action bg-light">Inventario</a>
                <a href="?page=customers" class="list-group-item list-group-item-action bg-light">Clientes</a>
                <a href="?page=suppliers" class="list-group-item list-group-item-action bg-light">Proveedores</a>
                <a href="?page=caja" class="list-group-item list-group-item-action bg-light">Caja de Ventas</a>
                <a href="?page=backups" class="list-group-item list-group-item-action bg-light">Respaldo</a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="?page=users" class="list-group-item list-group-item-action bg-light">Gestión de Usuarios</a>
                <?php endif; ?>
                <a href="?page=settings" class="list-group-item list-group-item-action bg-light">Ajustes</a>
                <a href="#" id="logout-button" class="list-group-item list-group-item-action bg-light">Cerrar Sesión</a>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
            </nav>

            <div class="container-fluid">
                <?php
                // Cargar el contenido de la página según la variable $page
                $filePath = 'pages/' . $page . '.php';
                if (file_exists($filePath)) {
                    include $filePath;
                } else {
                    include 'pages/404.php'; // Página de error 404
                }
                ?>
            </div>
        </div>
        <!-- /#page-content-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- Modal de Confirmación de Cierre de Sesión -->
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" role="dialog" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutConfirmModalLabel">Confirmar Cierre de Sesión</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas cerrar la sesión?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirm-logout">Cerrar Sesión</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir JS de jQuery y Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Scripts personalizados -->
    <script src="js/scripts.js"></script>
    <script>
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });

        // Manejar el botón de cerrar sesión
        $('#logout-button').on('click', function(e) {
            e.preventDefault();
            $('#logoutConfirmModal').modal('show');
        });

        $('#confirm-logout').on('click', function() {
            // Aquí iría la lógica para cerrar la sesión, por ejemplo, redirigir a una página de logout
            window.location.href = 'logout.php'; // Redirige a una página de logout
        });
    </script>
</body>
</html>