<?php require_once 'api/check_auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Venta e Inventario - Smart Jireh</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Smart Jireh - Sistema de Venta e Inventario</h1>
        <nav>
            <a href="#" class="nav-link active" data-view="inventory">Inventario</a>
            <a href="#" class="nav-link" data-view="new-sale">Nueva Venta</a>
            <a href="#" class="nav-link" data-view="sales-list">Lista de Ventas</a>
            <a href="#" class="nav-link" data-view="reports">Reportes y Caja</a>
            <a href="#" class="nav-link" data-view="backup">Respaldo</a>
            <a href="#" class="nav-link" data-view="users">Usuarios</a>
            <a href="#" class="nav-link" data-view="clients">Clientes</a>
            <a href="#" class="nav-link" data-view="providers">Proveedores</a>
            <a href="#" class="nav-link" data-view="settings">Ajustes</a>
        </nav>
    </header>

    <main>
        <!-- Vista de Inventario -->
        <section id="inventory-view" class="view active-view">
            <h2>Gestión de Inventario</h2>
            <button id="add-product-btn">Agregar Nuevo Producto</button>
            <table id="inventory-table">
                <thead>
                    <tr>
                        <th>IMEI/Código</th>
                        <th>Marca/Producto</th>
                        <th>Proveedor</th>
                        <th>Cantidad</th>
                        <th>Precio Costo</th>
                        <th>Precio Venta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas de inventario se insertarán aquí -->
                </tbody>
            </table>
        </section>

        <!-- Vista de Nueva Venta -->
        <section id="new-sale-view" class="view">
            <h2>Registrar Nueva Venta</h2>
            <form id="sale-form">
                <div class="form-grid">
                    <div>
                        <label for="customer-select">Cliente:</label>
                        <div class="customer-selection">
                            <select id="customer-select" name="customer_id"></select>
                            <button type="button" id="add-new-customer-btn" class="button-small">+</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="add-item-form">
                <h3>Añadir Producto a la Venta</h3>
                <select id="sale-product-select"></select>
                <input type="number" id="sale-quantity" min="1" value="1">
                <button id="add-to-sale-btn">Añadir</button>
            </div>

            <h3>Productos en esta Venta</h3>
            <table id="current-sale-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Productos de la venta actual -->
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th id="sale-total">$0.00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
            <button id="complete-sale-btn">Finalizar Venta y Generar Factura</button>
        </section>

        <!-- Vista de Lista de Ventas -->
        <section id="sales-list-view" class="view">
            <h2>Historial de Ventas</h2>
            <table id="sales-list-table">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Historial de ventas -->
                </tbody>
            </table>
        </section>

        <!-- Vista de Reportes -->
        <section id="reports-view" class="view">
            <h2>Reportes y Estado de Caja</h2>
            <div class="form-grid">
                <div>
                    <label for="start-date">Fecha de Inicio:</label>
                    <input type="date" id="start-date" name="start_date">
                </div>
                <div>
                    <label for="end-date">Fecha de Fin:</label>
                    <input type="date" id="end-date" name="end_date">
                </div>
            </div>
            <button id="generate-report-btn">Generar Reporte</button>

            <div id="report-results" class="report-container">
                <h3>Resultados</h3>
                <div class="report-item">
                    <span>Inversión Total:</span>
                    <strong id="report-investment">$0.00</strong>
                </div>
                <div class="report-item">
                    <span>Ingresos por Ventas:</span>
                    <strong id="report-revenue">$0.00</strong>
                </div>
                <div class="report-item profit">
                    <span>Ganancia Neta:</span>
                    <strong id="report-profit">$0.00</strong>
                </div>
            </div>
        </section>

        <!-- Vista de Respaldo/Restauración -->
        <section id="backup-view" class="view">
            <h2>Copia de Seguridad y Restauración</h2>
            <div class="backup-container">
                <h3>Descargar Copia de Seguridad</h3>
                <p>Guarda una copia de todos tus datos (inventario y ventas) en un solo archivo.</p>
                <a href="api/backup.php?action=download" class="button">Descargar Respaldo</a>
            </div>
            <div class="backup-container">
                <h3>Restaurar desde Copia de Seguridad</h3>
                <p>Selecciona un archivo de respaldo (.json) para restaurar los datos del sistema.</p>
                <form id="restore-form" enctype="multipart/form-data">
                    <input type="file" name="backup_file" accept=".json" required>
                    <button type="submit">Restaurar Sistema</button>
                </form>
            </div>
        </section>

        <!-- Vista de Gestión de Usuarios -->
        <section id="users-view" class="view">
            <h2>Gestión de Usuarios</h2>
            <button id="add-user-btn">Agregar Nuevo Usuario</button>
            <table id="users-table">
                <thead>
                    <tr>
                        <th>Nombre de Usuario</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas de usuarios se insertarán aquí -->
                </tbody>
            </table>
        </section>

        <!-- Vista de Gestión de Clientes -->
        <section id="clients-view" class="view">
            <h2>Gestión de Clientes</h2>
            <button id="add-client-btn">Agregar Nuevo Cliente</button>
            <table id="clients-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas de clientes se insertarán aquí -->
                </tbody>
            </table>
        </section>

        <!-- Vista de Gestión de Proveedores -->
        <section id="providers-view" class="view">
            <h2>Gestión de Proveedores</h2>
            <button id="add-provider-btn">Agregar Nuevo Proveedor</button>
            <table id="providers-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas de proveedores se insertarán aquí -->
                </tbody>
            </table>
        </section>

        <!-- Vista de Ajustes -->
        <section id="settings-view" class="view">
            <h2>Ajustes del Sistema</h2>
            <form id="settings-form" enctype="multipart/form-data">
                <div class="settings-container">
                    <h3>Datos de la Empresa</h3>
                    <label for="company-name">Nombre de la Empresa:</label>
                    <input type="text" id="company-name" name="company_name">
                    <label for="company-phone">Teléfono:</label>
                    <input type="tel" id="company-phone" name="company_phone">
                    <label for="company-whatsapp">WhatsApp:</label>
                    <input type="tel" id="company-whatsapp" name="company_whatsapp">
                    <label for="company-logo">Logo de la Empresa:</label>
                    <input type="file" id="company-logo" name="logo" accept="image/*">
                    <img id="logo-preview" src="" alt="Vista previa del logo" style="max-width: 150px; margin-top: 10px; display: none;">
                </div>
                <div class="settings-container">
                    <h3>Personalización</h3>
                    <label for="primary-color">Color Principal:</label>
                    <input type="color" id="primary-color" name="primary_color">
                </div>
                <button type="submit">Guardar Ajustes</button>
            </form>
        </section>
    </main>

    <!-- Modal para agregar/editar producto -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="modal-title">Agregar Nuevo Producto</h3>
            <form id="product-form">
                <input type="hidden" id="product-id" name="id">
                <label for="product-type">Tipo:</label>
                <select id="product-type" name="type">
                    <option value="telefono">Teléfono</option>
                    <option value="accesorio">Accesorio</option>
                </select>
                <label for="product-imei">IMEI/Código:</label>
                <input type="text" id="product-imei" name="imei" required>
                <label for="product-brand">Marca/Producto:</label>
                <input type="text" id="product-brand" name="brand" required>
                <label for="product-supplier">Proveedor:</label>
                <select id="product-supplier" name="supplier"></select>
                <label for="product-quantity">Cantidad:</label>
                <input type="number" id="product-quantity" name="quantity" required min="0">
                <label for="product-cost">Precio Costo (RD$):</label>
                <input type="number" id="product-cost" name="cost" required min="0" step="0.01">
                <label for="product-sale-price">Precio Venta (RD$):</label>
                <input type="number" id="product-sale-price" name="sale_price" required min="0" step="0.01">
                <button type="submit" id="save-product-btn">Guardar</button>
            </form>
        </div>
    </div>

    <!-- Modal para agregar/editar usuario -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="user-modal-title">Agregar Nuevo Usuario</h3>
            <form id="user-form">
                <input type="hidden" id="user-id" name="id">
                <label for="user-username">Nombre de Usuario:</label>
                <input type="text" id="user-username" name="username" required>
                <label for="user-password">Contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" id="user-password" name="password">
                <label for="user-role">Rol:</label>
                <select id="user-role" name="role">
                    <option value="vendedor">Vendedor</option>
                    <option value="inventario">Inventario</option>
                    <option value="admin">Administrador</option>
                </select>
                <button type="submit" id="save-user-btn">Guardar</button>
            </form>
        </div>
    </div>

    <!-- Modal para agregar/editar cliente -->
    <div id="client-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="client-modal-title">Agregar Nuevo Cliente</h3>
            <form id="client-form">
                <input type="hidden" id="client-id" name="id">
                <label for="client-name">Nombre:</label>
                <input type="text" id="client-name" name="name" required>
                <label for="client-id-card">Cédula:</label>
                <input type="text" id="client-id-card" name="id_card">
                <label for="client-address">Dirección:</label>
                <input type="text" id="client-address" name="address">
                <label for="client-phone">Teléfono:</label>
                <input type="tel" id="client-phone" name="phone" required>
                <button type="submit" id="save-client-btn">Guardar</button>
            </form>
        </div>
    </div>

    <!-- Modal para agregar/editar proveedor -->
    <div id="provider-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="provider-modal-title">Agregar Nuevo Proveedor</h3>
            <form id="provider-form">
                <input type="hidden" id="provider-id" name="id">
                <label for="provider-name">Nombre:</label>
                <input type="text" id="provider-name" name="name" required>
                <label for="provider-id-card">Cédula:</label>
                <input type="text" id="provider-id-card" name="id_card">
                <label for="provider-address">Dirección:</label>
                <input type="text" id="provider-address" name="address">
                <label for="provider-phone">Teléfono:</label>
                <input type="tel" id="provider-phone" name="phone" required>
                <button type="submit" id="save-provider-btn">Guardar</button>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>