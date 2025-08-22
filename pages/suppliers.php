<?php
// pages/suppliers.php

require_once 'api/functions.php';

$data = getData();
$suppliers = $data['suppliers'] ?? [];
$message = '';

// Manejar acciones (agregar, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $newSupplier = [
                    'id' => uniqid(),
                    'name' => $_POST['name'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'address' => $_POST['address'] ?? '',
                ];
                $suppliers[] = $newSupplier;
                $message = '<div class="alert alert-success">Proveedor agregado exitosamente.</div>';
                break;
            case 'edit':
                $supplierId = $_POST['supplier_id'] ?? '';
                foreach ($suppliers as &$supplier) {
                    if ($supplier['id'] === $supplierId) {
                        $supplier['name'] = $_POST['name'] ?? '';
                        $supplier['phone'] = $_POST['phone'] ?? '';
                        $supplier['address'] = $_POST['address'] ?? '';
                        $message = '<div class="alert alert-success">Proveedor actualizado exitosamente.</div>';
                        break;
                    }
                }
                break;
            case 'delete':
                $supplierId = $_POST['supplier_id'] ?? '';
                $suppliers = array_filter($suppliers, function($supplier) use ($supplierId) {
                    return $supplier['id'] !== $supplierId;
                });
                $message = '<div class="alert alert-success">Proveedor eliminado exitosamente.</div>';
                break;
        }
        $data['suppliers'] = array_values($suppliers); // Reindex array after filter
        saveData($data);
        // Redirigir para evitar reenvío del formulario
        header('Location: ?page=suppliers');
        exit;
    }
}

?>

<h1 class="mt-4">Gestión de Proveedores</h1>

<?php echo $message; ?>

<!-- Formulario para Agregar/Editar Proveedor -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-truck mr-1"></i>
        Agregar/Editar Proveedor
    </div>
    <div class="card-body">
        <form method="POST" id="supplierForm">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="supplier_id" id="supplier_id">
            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="name">Nombre del Proveedor</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone">Teléfono</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-12 mb-3">
                    <label for="address">Dirección</label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Guardar Proveedor</button>
            <button class="btn btn-secondary" type="button" onclick="clearForm()">Limpiar Formulario</button>
        </form>
    </div>
</div>

<!-- Tabla de Proveedores -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i>
        Listado de Proveedores
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($suppliers)): ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($supplier['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='editSupplier(<?php echo json_encode($supplier); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este proveedor?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="supplier_id" value="<?php echo htmlspecialchars($supplier['id'] ?? ''); ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No hay proveedores registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editSupplier(supplier) {
    document.getElementById('action').value = 'edit';
    document.getElementById('supplier_id').value = supplier.id;
    document.getElementById('name').value = supplier.name;
    document.getElementById('phone').value = supplier.phone;
    document.getElementById('address').value = supplier.address;
}

function clearForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('supplier_id').value = '';
    document.getElementById('supplierForm').reset();
}
</script>
