<?php
// pages/inventory.php

require_once 'api/functions.php';

$data = getData();
$inventory = $data['inventory'] ?? [];
$message = '';

$searchTerm = $_GET['search_product_name'] ?? '';

if (!empty($searchTerm)) {
    $filteredInventory = [];
    $searchTermLower = strtolower($searchTerm);
    foreach ($inventory as $product) {
        if (isset($product['name']) && strpos(strtolower($product['name']), $searchTermLower) !== false) {
            $filteredInventory[] = $product;
        }
    }
    $inventory = $filteredInventory; // Use filtered inventory for display
}

// Manejar acciones (agregar, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $newProduct = [
                    'id' => uniqid(),
                    'name' => $_POST['name'] ?? '',
                    'imei' => $_POST['imei'] ?? '',
                    'quantity' => (int)($_POST['quantity'] ?? 0),
                    'price' => (float)($_POST['price'] ?? 0.00),
                    'cost_price' => (float)($_POST['cost_price'] ?? 0.00),
                    'supplier_id' => $_POST['supplier_id'] ?? ''
                ];
                $inventory[] = $newProduct;
                $message = '<div class="alert alert-success">Producto agregado exitosamente.</div>';
                break;
            case 'edit':
                $productId = $_POST['product_id'] ?? '';
                foreach ($inventory as &$product) {
                    if ($product['id'] === $productId) {
                        $product['name'] = $_POST['name'] ?? '';
                        $product['imei'] = $_POST['imei'] ?? '';
                        $product['quantity'] = (int)($_POST['quantity'] ?? 0);
                        $product['price'] = (float)($_POST['price'] ?? 0.00);
                        $product['cost_price'] = (float)($_POST['cost_price'] ?? 0.00);
                        $product['supplier_id'] = $_POST['supplier_id'] ?? '';
                        $message = '<div class="alert alert-success">Producto actualizado exitosamente.</div>';
                        break;
                    }
                }
                break;
            case 'delete':
                $productId = $_POST['product_id'] ?? '';
                $inventory = array_filter($inventory, function($product) use ($productId) {
                    return $product['id'] !== $productId;
                });
                $message = '<div class="alert alert-success">Producto eliminado exitosamente.</div>';
                break;
        }
        $data['inventory'] = array_values($inventory); // Reindex array after filter
        saveData($data);
        // Redirigir para evitar reenvío del formulario
        header('Location: ?page=inventory');
        exit;
    }
}

?>

<h1 class="mt-4">Gestión de Inventario</h1>

<?php echo $message; ?>

<button class="btn btn-primary mb-3" type="button" onclick="showAddProductForm()">Agregar Nuevo Producto</button>

<!-- Formulario para Agregar/Editar Producto -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-table mr-1"></i>
            Formulario de Producto
        </div>
        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#productFormCollapse" aria-expanded="false" aria-controls="productFormCollapse">
            <i class="fas fa-chevron-down"></i> Ocultar Formulario
        </button>
    </div>
    <div class="collapse" id="productFormCollapse">
        <div class="card-body">
            <form method="POST" id="productForm">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="product_id" id="product_id">
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="name">Nombre del Producto</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="imei">IMEI/Código</label>
                        <input type="text" class="form-control" id="imei" name="imei">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="quantity">Cantidad</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="price">Precio Venta</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="cost_price">Precio de Costo</label>
                        <input type="number" class="form-control" id="cost_price" name="cost_price" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="supplier_id">Proveedor</label>
                        <select class="form-control" id="supplier_id" name="supplier_id" required>
                            <option value="">Seleccione un proveedor</option>
                            <?php foreach ($data['suppliers'] as $supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier['id']); ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Guardar Producto</button>
                <button class="btn btn-secondary" type="button" onclick="clearForm()">Limpiar Formulario</button>
            </form>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-search mr-1"></i>
        Buscar Productos
    </div>
    <div class="card-body">
        <form method="GET" action="?page=inventory">
            <input type="hidden" name="page" value="inventory">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Buscar por nombre de producto..." name="search_product_name" value="<?php echo htmlspecialchars($_GET['search_product_name'] ?? ''); ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Productos -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i>
        Productos en Inventario
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>IMEI/Código</th>
                        <th>Cantidad</th>
                        <th>Precio Venta</th>
                        <th>Precio de Costo</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inventory)): ?>
                        <?php foreach ($inventory as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($product['imei'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($product['quantity'] ?? ''); ?></td>
                                <td>$<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                <td>$<?php echo number_format($product['cost_price'] ?? 0, 2); ?></td>
                                <td>
                                    <?php
                                        $supplierName = 'Desconocido';
                                        foreach ($data['suppliers'] as $supplier) {
                                            if (($supplier['id'] ?? '') === ($product['supplier_id'] ?? '')) {
                                                $supplierName = htmlspecialchars($supplier['name']);
                                                break;
                                            }
                                        }
                                        echo $supplierName;
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='editProduct(<?php echo json_encode($product); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este producto?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id'] ?? ''); ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay productos en el inventario.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('action').value = 'edit';
    document.getElementById('product_id').value = product.id;
    document.getElementById('name').value = product.name;
    document.getElementById('imei').value = product.imei;
    document.getElementById('quantity').value = product.quantity;
    document.getElementById('price').value = product.price;
    document.getElementById('cost_price').value = product.cost_price;
    document.getElementById('supplier_id').value = product.supplier_id;
    // Show the form when editing
    $('#productFormCollapse').collapse('show');
}

function clearForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('product_id').value = '';
    document.getElementById('productForm').reset();
    // Show the form when clearing
    $('#productFormCollapse').collapse('show');
}

function showAddProductForm() {
    clearForm(); // Clear the form and set action to add
    $('#productFormCollapse').collapse('show'); // Show the form
}
</script>