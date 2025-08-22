<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// pages/sales.php

require_once 'api/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annul_sale') {
    $saleIdToAnnul = $_POST['sale_id'] ?? null;

    if ($saleIdToAnnul) {
        $data = getData();
        $saleFound = false;
        foreach ($data['sales'] as &$sale) {
            if ($sale['id'] === $saleIdToAnnul && ($sale['status'] ?? 'Pendiente') !== 'Anulada') {
                // Mark sale as annulled
                $sale['status'] = 'Anulada';
                $saleFound = true;

                // Return products to inventory
                if (isset($sale['products']) && is_array($sale['products'])) {
                    foreach ($sale['products'] as $product) {
                        updateInventoryQuantity($data, $product['id'], $product['quantity']);
                    }
                }
                break;
            }
        }
        if ($saleFound) {
            saveData($data);
            // Redirect to prevent form re-submission and refresh the page
            header('Location: ?page=sales'); // Redirect to sales page
            exit;
        }
    }
}

// Filtering logic for sales list
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$invoiceSearch = $_GET['invoice_search'] ?? '';

$filteredSales = [];
// Re-fetch data to ensure it's fresh after potential annulment
$data = getData();
$sales = $data['sales'] ?? [];

foreach ($sales as $sale) {
    $saleTimestamp = strtotime($sale['date'] ?? '');
    $includeSale = true;

    if (!empty($startDate)) {
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        if ($saleTimestamp < $startTimestamp) {
            $includeSale = false;
        }
    }

    if (!empty($endDate)) {
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        if ($saleTimestamp > $endTimestamp) {
            $includeSale = false;
        }
    }

    // Filter by invoice number if search term is provided
    if (!empty($invoiceSearch)) {
        $invoiceNumber = $sale['invoice_number'] ?? '';
        // Only filter if the sale has an invoice number and it matches exactly (case-insensitive)
        if (empty($invoiceNumber) || strtolower($invoiceNumber) !== strtolower($invoiceSearch)) {
            $includeSale = false;
        }
    }

    if ($includeSale) {
        $filteredSales[] = $sale;
    }
}

// Sort sales by date, newest first
usort($filteredSales, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$data = getData();
$sales = $data['sales'] ?? [];
$inventory = $data['inventory'] ?? [];
$message = '';

// Ensure "Cliente Regular" exists and is the default
$regularCustomerExists = false;
$regularCustomerId = '';
foreach ($data['customers'] as $customer) {
    if ($customer['name'] === 'Cliente Regular') {
        $regularCustomerExists = true;
        $regularCustomerId = $customer['id'];
        break;
    }
}

if (!$regularCustomerExists) {
    $newRegularCustomer = [
        'id' => uniqid(),
        'name' => 'Cliente Regular',
        'phone' => 'N/A', // Or any default phone number
        'address' => 'N/A' // Or any default address
    ];
    $data['customers'][] = $newRegularCustomer;
    saveData($data);
    $regularCustomerId = $newRegularCustomer['id'];
}

// Manejar la adición de una nueva venta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_sale') {
        $selectedCustomerId = $_POST['customer_id'] ?? '';
        $customerName = '';
        $customerPhone = '';

        // Find customer details from the data array
        foreach ($data['customers'] as $customer) {
            if ($customer['id'] === $selectedCustomerId) {
                $customerName = $customer['name'] ?? '';
                $customerPhone = $customer['phone'] ?? '';
                break;
            }
        }
        $selectedProducts = json_decode($_POST['selected_products'] ?? '[]', true);

        if (empty($selectedProducts)) {
            $message = '<div class="alert alert-danger">Debe seleccionar al menos un producto para la venta.</div>';
        } else {
            $newSaleProducts = [];
            $canCompleteSale = true;

            // Verificar disponibilidad y preparar productos para la venta
            foreach ($selectedProducts as $sp) {
                $productId = $sp['id'];
                $quantitySold = (int)$sp['quantity'];
                $productFound = false;

                foreach ($inventory as &$invProduct) {
                    if ($invProduct['id'] === $productId) {
                        $productFound = true;
                        if ($invProduct['quantity'] >= $quantitySold) {
                            $newSaleProducts[] = [
                                'id' => $invProduct['id'],
                                'name' => $invProduct['name'],
                                'imei' => $invProduct['imei'],
                                'quantity' => $quantitySold,
                                'price' => $invProduct['price'],
                                'cost_price' => $invProduct['cost_price'] // Add cost_price
                            ];
                            // No actualizamos el inventario aquí, lo haremos después de confirmar la venta
                        } else {
                            $message = '<div class="alert alert-danger">Cantidad insuficiente de ' . htmlspecialchars($invProduct['name']) . '. Disponible: ' . $invProduct['quantity'] . '.</div>';
                            $canCompleteSale = false;
                            break 2; // Salir de ambos bucles
                        }
                        break;
                    }
                }
                if (!$productFound) {
                    $message = '<div class="alert alert-danger">Producto seleccionado no encontrado en el inventario.</div>';
                    $canCompleteSale = false;
                    break;
                }
            }

            if ($canCompleteSale) {
                // Actualizar inventario
                foreach ($selectedProducts as $sp) {
                    $productId = $sp['id'];
                    $quantitySold = (int)$sp['quantity'];
                    foreach ($inventory as &$invProduct) {
                        if ($invProduct['id'] === $productId) {
                            $invProduct['quantity'] -= $quantitySold;
                            break;
                        }
                    }
                }

                $saleDate = $_POST['sale_date'] ?? date('Y-m-d'); // Get date from form, default to current
                $saleDateTime = $saleDate . ' ' . date('H:i:s'); // Combine with current time

                                $newSale = [
                    'id' => uniqid(),
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'date' => $saleDateTime,
                    'products' => $newSaleProducts,
                    'status' => 'Pendiente'
                ];

                $sales[] = $newSale;
                $data['sales'] = $sales;
                $data['inventory'] = $inventory;
                saveData($data);

                $message = '<div class="alert alert-success">Venta registrada exitosamente.</div>';
                echo '<script type="text/javascript">';
                echo 'window.open("factura.php?id=' . $newSale['id'] . '&context=new", "_blank");';;
                echo '</script>';
                exit;
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_customer_modal') {
        $data = getData(); // Added this line
        $newCustomer = [
            'id' => uniqid(),
            'name' => $_POST['customer_name_modal'] ?? '',
            'phone' => $_POST['customer_phone_modal'] ?? '',
            'address' => $_POST['customer_address_modal'] ?? ''
        ];
        $data['customers'][] = $newCustomer;
        // Check if saveData was successful
        if (saveData($data)) {
            header('Content-Type: application/json'); // Ensure JSON header is sent
            echo json_encode([
                'status' => 'success',
                'message' => 'Cliente agregado exitosamente.',
                'customer' => [
                    'id' => $newCustomer['id'],
                    'name' => $newCustomer['name'],
                    'phone' => $newCustomer['phone']
                ]
            ]);
        } else {
            header('Content-Type: application/json'); // Ensure JSON header is sent
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al guardar los datos del cliente. Verifique los permisos del archivo data.json.'
            ]);
        }
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_sale') {
        $saleIdToDelete = $_POST['sale_id'] ?? null;

        if ($saleIdToDelete) {
            $data = getData(); // Re-fetch data to ensure it's fresh
            $sales = $data['sales'] ?? [];
            $updatedSales = [];
            $saleDeleted = false;

            foreach ($sales as $sale) {
                if ($sale['id'] === $saleIdToDelete) {
                    $saleDeleted = true;
                    // Return products to inventory ONLY if the sale was NOT annulled
                    if (($sale['status'] ?? 'Pendiente') !== 'Anulada') {
                        if (isset($sale['products']) && is_array($sale['products'])) {
                            foreach ($sale['products'] as $product) {
                                updateInventoryQuantity($data, $product['id'], $product['quantity']);
                            }
                        }
                    }
                    // Do NOT add this sale to updatedSales (effectively deleting it)
                } else {
                    $updatedSales[] = $sale; // Keep sales that are not being deleted
                }
            }

            if ($saleDeleted) {
                $data['sales'] = $updatedSales;
                saveData($data);
                header('Location: ?page=sales_report'); // Redirect back to sales report
                exit;
            }
        }
    }
}

?>

<h1 class="mt-4">Gestión de Ventas</h1>

<?php echo $message; ?>

<!-- Formulario para Registrar Nueva Venta -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-cash-register mr-1"></i>
        Registrar Nueva Venta
    </div>
    <div class="card-body">
        <form method="POST" id="saleForm">
            <input type="hidden" name="action" value="add_sale">
            <input type="hidden" name="selected_products" id="selected_products_input">

            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="customer_select">Seleccionar Cliente</label>
                    <select class="form-control" id="customer_select" name="customer_id" required onchange="updateCustomerFields()">
                        <option value="">-- Seleccione un cliente --</option>
                        <?php foreach ($data['customers'] as $customer): ?>
                            <option value="<?php echo htmlspecialchars($customer['id']); ?>"
                                    data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                    data-phone="<?php echo htmlspecialchars($customer['phone']); ?>"
                                    <?php echo ($customer['id'] === $regularCustomerId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['name']); ?> (<?php echo htmlspecialchars($customer['phone']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="customer_name" name="customer_name">
                    <input type="hidden" id="customer_phone" name="customer_phone">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <a href="?page=customers" class="btn btn-success">Agregar Nuevo Cliente</a>
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="sale_date">Fecha de Venta</label>
                    <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <h5 class="mt-4">Productos de la Venta</h5>
            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="barcode_scanner">Escanear Código de Barras</label>
                    <input type="text" class="form-control" id="barcode_scanner" placeholder="Escanea o introduce el código...">
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="product_search_name">Buscar Producto por Nombre</label>
                    <input type="text" class="form-control mb-2" id="product_search_name" placeholder="Escribe el nombre del producto...">
                    <label for="product_select">Seleccionar Producto</label>
                    <select class="form-control" id="product_select">
                        <option value="">-- Seleccione un producto --</option>
                        <?php foreach ($inventory as $product): ?>
                            <?php if ($product['quantity'] > 0): // Solo mostrar productos con stock ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-imei="<?php echo htmlspecialchars($product['imei']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                        data-cost-price="<?php echo htmlspecialchars($product['cost_price']); ?>"
                                        data-quantity="<?php echo htmlspecialchars($product['quantity']); ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['imei']); ?>) - Stock: <?php echo htmlspecialchars($product['quantity']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="product_quantity">Cantidad</label>
                    <input type="number" class="form-control" id="product_quantity" value="1" min="1">
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="button" class="btn btn-success" id="add_product_to_sale">Agregar Producto</button>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-bordered" id="selected_products_table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>IMEI/Código</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Productos seleccionados se añadirán aquí -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Total:</th>
                            <th id="sale_total" class="text-right">$0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button class="btn btn-primary mt-3" type="submit">Registrar Venta</button>
        </form>
    </div>
</div>



<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter mr-1"></i>
        Filtrar Ventas
    </div>
    <div class="card-body">
        <form method="GET" action="?page=sales">
            <input type="hidden" name="page" value="sales">
            <div class="form-row">
                <div class="col-md-5 mb-3">
                    <label for="start_date">Fecha de Inicio</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-5 mb-3">
                    <label for="end_date">Fecha Fin</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-10 mb-3">
                    <label for="invoice_search">Buscar por Número de Factura</label>
                    <input type="text" class="form-control" id="invoice_search" name="invoice_search" value="<?php echo htmlspecialchars($_GET['invoice_search'] ?? ''); ?>" placeholder="Ej: 000012">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button class="btn btn-secondary btn-block" type="submit">Buscar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i>
        Detalle de Ventas
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="salesDataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total Venta</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($filteredSales)): ?>
                        <?php foreach ($filteredSales as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sale['date'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($sale['customer_name'] ?? ''); ?></td>
                                <td>
                                    $<?php
                                    $saleTotal = 0;
                                    if (isset($sale['products']) && is_array($sale['products'])) {
                                        foreach ($sale['products'] as $product) {
                                            $saleTotal += ($product['price'] ?? 0) * ($product['quantity'] ?? 0);
                                        }
                                    }
                                    echo number_format($saleTotal, 2);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($sale['status'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="factura.php?id=<?php echo htmlspecialchars($sale['id'] ?? ''); ?>&context=reprint" target="_blank" class="btn btn-sm btn-info">Ver Factura</a>
                                    <?php if (($sale['status'] ?? 'Pendiente') !== 'Anulada'): ?>
                                        <form method="POST" action="?page=sales" style="display:inline-block;">
                                            <input type="hidden" name="action" value="annul_sale">
                                            <input type="hidden" name="sale_id" value="<?php echo htmlspecialchars($sale['id'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea anular esta factura? Esta acción no se puede deshacer y los productos volverán al inventario.');">Anular</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay ventas registradas para el rango de fechas seleccionado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<script>
    let selectedProducts = [];
    const allInventory = <?php echo json_encode($inventory); ?>; // Embed inventory data

    // Function to populate product select options
    function populateProductSelect(productsToDisplay) {
        const productSelect = document.getElementById('product_select');
        productSelect.innerHTML = '<option value="">-- Seleccione un producto --</option>'; // Clear existing options

        productsToDisplay.forEach(product => {
            if (product.quantity > 0) { // Only show products with stock
                const option = document.createElement('option');
                option.value = product.id;
                option.dataset.name = product.name;
                option.dataset.imei = product.imei;
                option.dataset.price = product.price;
                option.dataset.costPrice = product.cost_price;
                option.dataset.quantity = product.quantity;
                option.textContent = `${product.name} (${product.imei}) - Stock: ${product.quantity}`;
                productSelect.appendChild(option);
            }
        });
    }

    // Initial population of product select
    populateProductSelect(allInventory);

    // Product Name Search Listener
    document.getElementById('product_search_name').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredProducts = allInventory.filter(product =>
            product.name.toLowerCase().includes(searchTerm)
        );
        populateProductSelect(filteredProducts);
    });

    // Barcode Scanner Listener
    document.getElementById('barcode_scanner').addEventListener('change', function() {
        const barcode = this.value;
        const foundProduct = allInventory.find(product => product.imei === barcode);

        if (foundProduct) {
            // Set product select to found product and trigger add
            document.getElementById('product_select').value = foundProduct.id;
            document.getElementById('product_quantity').value = 1; // Default to 1
            document.getElementById('add_product_to_sale').click(); // Simulate click
            this.value = ''; // Clear barcode scanner
        } else {
            alert('Producto con código de barras ' + barcode + ' no encontrado.');
            this.value = ''; // Clear barcode scanner
        }
    });

    document.getElementById('add_product_to_sale').addEventListener('click', function() {
        const productSelect = document.getElementById('product_select');
        const quantityInput = document.getElementById('product_quantity');

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (!selectedOption.value) {
            alert('Por favor, seleccione un producto.');
            return;
        }

        const productId = selectedOption.value;
        const productName = selectedOption.dataset.name;
        const productImei = selectedOption.dataset.imei;
        const productPrice = parseFloat(selectedOption.dataset.price);
        const productCostPrice = parseFloat(selectedOption.dataset.costPrice); // Get cost_price
        const availableQuantity = parseInt(selectedOption.dataset.quantity);
        const quantityToAdd = parseInt(quantityInput.value);

        if (isNaN(quantityToAdd) || quantityToAdd <= 0) {
            alert('La cantidad debe ser un número positivo.');
            return;
        }

        if (quantityToAdd > availableQuantity) {
            alert('No hay suficiente stock. Cantidad disponible: ' + availableQuantity);
            return;
        }

        // Verificar si el producto ya está en la lista de seleccionados
        const existingProductIndex = selectedProducts.findIndex(p => p.id === productId);

        if (existingProductIndex > -1) {
            // Si ya existe, actualizar la cantidad
            const currentSelectedQuantity = selectedProducts[existingProductIndex].quantity;
            if (currentSelectedQuantity + quantityToAdd > availableQuantity) {
                alert('No puedes añadir más de la cantidad disponible para este producto.');
                return;
            }
            selectedProducts[existingProductIndex].quantity += quantityToAdd;
        } else {
            // Si no existe, añadirlo
            selectedProducts.push({
                id: productId,
                name: productName,
                imei: productImei,
                price: productPrice,
                cost_price: productCostPrice, // Add cost_price to selectedProducts
                quantity: quantityToAdd
            });
        }

        updateSelectedProductsTable();
        productSelect.value = ''; // Limpiar selección
        quantityInput.value = '1'; // Resetear cantidad
    });

    function updateSelectedProductsTable() {
        const tableBody = document.querySelector('#selected_products_table tbody');
        tableBody.innerHTML = '';
        let totalSale = 0;

        selectedProducts.forEach((product, index) => {
            const subtotal = product.price * product.quantity;
            totalSale += subtotal;

            const row = tableBody.insertRow();
            row.innerHTML = `
                <td>${product.name}</td>
                <td>${product.imei}</td>
                <td>${product.quantity}</td>
                <td>${product.price.toFixed(2)}</td>
                <td>${subtotal.toFixed(2)}</td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeProduct(${index})">Quitar</button></td>
            `;
        });

        document.getElementById('sale_total').textContent = `${totalSale.toFixed(2)}`;
        document.getElementById('selected_products_input').value = JSON.stringify(selectedProducts);
    }

    function removeProduct(index) {
        selectedProducts.splice(index, 1);
        updateSelectedProductsTable();
    }

    function updateCustomerFields() {
        const customerSelect = document.getElementById('customer_select');
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        document.getElementById('customer_name').value = selectedOption.dataset.name || '';
        document.getElementById('customer_phone').value = selectedOption.dataset.phone || '';
    }

    // Call updateCustomerFields on page load to set initial values if a customer is pre-selected
    document.addEventListener('DOMContentLoaded', updateCustomerFields);

    // Asegurarse de que el formulario envíe los datos correctos
    document.getElementById('saleForm').addEventListener('submit', function(event) {
        if (selectedProducts.length === 0) {
            alert('Debe seleccionar al menos un producto para registrar la venta.');
            event.preventDefault(); // Evitar el envío del formulario
        }
    });
</script>