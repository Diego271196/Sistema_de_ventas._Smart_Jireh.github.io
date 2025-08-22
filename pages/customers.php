<?php
// pages/customers.php

require_once 'api/functions.php';

$data = getData();
$customers = $data['customers'] ?? [];
$message = '';

// Manejar acciones (agregar, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $newCustomer = [
                    'id' => uniqid(),
                    'name' => $_POST['name'] ?? '',
                    'cedula' => $_POST['cedula'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'address' => $_POST['address'] ?? '',
                ];
                $customers[] = $newCustomer;
                $message = '<div class="alert alert-success">Cliente agregado exitosamente.</div>';
                break;
            case 'edit':
                $customerId = $_POST['customer_id'] ?? '';
                foreach ($customers as &$customer) {
                    if ($customer['id'] === $customerId) {
                        $customer['name'] = $_POST['name'] ?? '';
                        $customer['cedula'] = $_POST['cedula'] ?? '';
                        $customer['phone'] = $_POST['phone'] ?? '';
                        $customer['address'] = $_POST['address'] ?? '';
                        $message = '<div class="alert alert-success">Cliente actualizado exitosamente.</div>';
                        break;
                    }
                }
                break;
            case 'delete':
                $customerId = $_POST['customer_id'] ?? '';
                $customers = array_filter($customers, function($customer) use ($customerId) {
                    return $customer['id'] !== $customerId;
                });
                $message = '<div class="alert alert-success">Cliente eliminado exitosamente.</div>';
                break;
        }
        $data['customers'] = array_values($customers); // Reindex array after filter
        saveData($data);
        // Redirigir para evitar reenvío del formulario
        header('Location: ?page=customers');
        exit;
    }
}

?>

<h1 class="mt-4">Gestión de Clientes</h1>

<?php echo $message; ?>

<!-- Formulario para Agregar/Editar Cliente -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-user-plus mr-1"></i>
        Agregar/Editar Cliente
    </div>
    <div class="card-body">
        <form method="POST" id="customerForm">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="customer_id" id="customer_id">
            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="name">Nombre</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cedula">Cédula</label>
                    <input type="text" class="form-control" id="cedula" name="cedula">
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="phone">Teléfono</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="address">Dirección</label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Guardar Cliente</button>
            <button class="btn btn-secondary" type="button" onclick="clearForm()">Limpiar Formulario</button>
        </form>
    </div>
</div>

<!-- Tabla de Clientes -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i>
        Listado de Clientes
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($customer['cedula'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($customer['address'] ?? ''); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='editCustomer(<?php echo json_encode($customer); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este cliente?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer['id'] ?? ''); ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay clientes registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editCustomer(customer) {
    document.getElementById('action').value = 'edit';
    document.getElementById('customer_id').value = customer.id;
    document.getElementById('name').value = customer.name;
    document.getElementById('cedula').value = customer.cedula;
    document.getElementById('phone').value = customer.phone;
    document.getElementById('address').value = customer.address;
}

function clearForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('customer_id').value = '';
    document.getElementById('customerForm').reset();
}
</script>
