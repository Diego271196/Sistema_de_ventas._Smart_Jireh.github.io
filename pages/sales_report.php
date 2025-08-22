<?php
// pages/sales_report.php

require_once 'api/functions.php';

$data = getData();
$sales = $data['sales'] ?? [];

$searchTerm = $_GET['search'] ?? '';
$filteredSales = [];

if (!empty($searchTerm)) {
    $searchTermLower = strtolower($searchTerm);
    foreach ($sales as $sale) {
        $match = false;
        // Search by Sale ID
        if (isset($sale['id']) && strpos(strtolower($sale['id']), $searchTermLower) !== false) {
            $match = true;
        }
        // Search by Customer Name
        if (isset($sale['customer_name']) && strpos(strtolower($sale['customer_name']), $searchTermLower) !== false) {
            $match = true;
        }
        // Search by Product Name within the sale
        if (isset($sale['products']) && is_array($sale['products'])) {
            foreach ($sale['products'] as $product) {
                if (isset($product['name']) && strpos(strtolower($product['name']), $searchTermLower) !== false) {
                    $match = true;
                    break;
                }
            }
        }
        // Search by Invoice Number
        if (isset($sale['invoice_number']) && strpos(strtolower($sale['invoice_number']), $searchTermLower) !== false) {
            $match = true;
        }

        if ($match) {
            $filteredSales[] = $sale;
        }
    }
} else {
    $filteredSales = $sales;
}

// Sort sales by date, newest first
usort($filteredSales, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

?>

<h1 class="mt-4">Reporte de Ventas</h1>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-search mr-1"></i>
        Buscar Ventas
    </div>
    <div class="card-body">
        <form method="GET" action="?page=sales_report">
            <div class="input-group mb-3">
                <input type="hidden" name="page" value="sales_report">
                <input type="text" class="form-control" placeholder="Buscar por ID de Venta, Cliente, Producto o Número de Factura..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Buscar</button>
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
            <table class="table table-bordered" id="salesReportTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Productos</th>
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
                                <td><?php echo htmlspecialchars($sale['customer_phone'] ?? ''); ?></td>
                                <td>
                                    <ul>
                                        <?php
                                        $saleTotal = 0;
                                        if (isset($sale['products']) && is_array($sale['products'])) {
                                            foreach ($sale['products'] as $product) {
                                                echo '<li>' . htmlspecialchars($product['name'] ?? '') . ' (Cant: ' . htmlspecialchars($product['quantity'] ?? '') . ', Precio: $' . number_format($product['price'] ?? 0, 2) . ')</li>';
                                                $saleTotal += ($product['price'] ?? 0) * ($product['quantity'] ?? 0);
                                            }
                                        }
                                        ?>
                                    </ul>
                                </td>
                                <td>$<?php echo number_format($saleTotal, 2); ?></td>
                                <td><?php echo htmlspecialchars($sale['status'] ?? 'Pendiente'); ?></td>
                                <td>
                                    <a href="factura.php?id=<?php echo htmlspecialchars($sale['id'] ?? ''); ?>&context=reprint" target="_blank" class="btn btn-sm btn-info">Ver Factura</a>
                                    <?php if (($sale['status'] ?? 'Pendiente') !== 'Anulada'): ?>
                                        <form method="POST" action="?page=sales" style="display:inline-block;">
                                            <input type="hidden" name="action" value="annul_sale">
                                            <input type="hidden" name="sale_id" value="<?php echo htmlspecialchars($sale['id'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea anular esta factura? Esta acción no se puede deshacer y los productos volverán al inventario.');">Anular</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="?page=sales" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete_sale">
                                        <input type="hidden" name="sale_id" value="<?php echo htmlspecialchars($sale['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('¿Está seguro de que desea ELIMINAR esta venta? Esta acción es irreversible y los productos volverán al inventario si la venta no estaba anulada.');">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay ventas registradas que coincidan con la búsqueda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
