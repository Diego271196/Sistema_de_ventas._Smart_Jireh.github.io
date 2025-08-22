<?php
// pages/caja.php

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
                        updateInventoryQuantity($product['id'], $product['quantity']);
                    }
                }
                break;
            }
        }
        if ($saleFound) {
            saveData($data);
            // Redirect to prevent form re-submission and refresh the page
            header('Location: ?page=caja');
            exit;
        }
    }
}


$data = getData();
$sales = $data['sales'] ?? [];

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$invoiceSearch = $_GET['invoice_search'] ?? '';

$filteredSales = [];
$totalRevenue = 0;
$totalProfit = 0; // Nueva variable para la ganancia
$totalCostOfGoodsSold = 0; // Nueva variable para lo invertido

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
        if (strpos($invoiceNumber, $invoiceSearch) === false) { // Case-sensitive search
            $includeSale = false;
        }
    }

    if ($includeSale) {
        $filteredSales[] = $sale;
        if (isset($sale['products']) && is_array($sale['products'])) {
            foreach ($sale['products'] as $product) {
                $productPrice = $product['price'] ?? 0;
                $productCostPrice = $product['cost_price'] ?? 0;
                $productQuantity = $product['quantity'] ?? 0;

                $totalRevenue += $productPrice * $productQuantity;
                $totalProfit += ($productPrice - $productCostPrice) * $productQuantity; // Calcular ganancia
                $totalCostOfGoodsSold += $productCostPrice * $productQuantity; // Calcular lo invertido
            }
        }
    }
}

// Sort sales by date, newest first
usort($filteredSales, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

?>

<h1 class="mt-4">Caja - Reporte de Ingresos</h1>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter mr-1"></i>
        Filtrar por Fecha
    </div>
    <div class="card-body">
        <form method="GET" action="?page=caja">
            <input type="hidden" name="page" value="caja">
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
                    <input type="text" class="form-control" id="invoice_search" name="invoice_search" value="<?php echo htmlspecialchars($invoiceSearch); ?>" placeholder="Ej: 000012">
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
        <i class="fas fa-dollar-sign mr-1"></i>
        Resumen de Ingresos
    </div>
    <div class="card-body">
        <h3 class="text-center">Total de Ingresos (Ventas): $<?php echo number_format($totalRevenue, 2); ?></h3>
        <h3 class="text-center">Total Invertido (Costo de Ventas): $<?php echo number_format($totalCostOfGoodsSold, 2); ?></h3>
        <h3 class="text-center">Ganancia Total: $<?php echo number_format($totalProfit, 2); ?></h3>
        <p class="text-muted text-center">Este total representa los ingresos brutos de las ventas. La ganancia total se calcula como la diferencia entre el precio de venta y el precio de costo de cada producto.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i>
        Detalle de Ventas Filtradas
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="cajaReportTable" width="100%" cellspacing="0">
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
                                    <a href="factura.php?id=<?php echo htmlspecialchars($sale['id'] ?? ''); ?>" target="_blank" class="btn btn-sm btn-info">Ver Factura</a>
                                    <?php if (($sale['status'] ?? 'Pendiente') !== 'Anulada'): ?>
                                        <form method="POST" action="?page=caja" style="display:inline-block;">
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