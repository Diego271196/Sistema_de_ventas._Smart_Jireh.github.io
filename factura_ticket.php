<?php
// factura_ticket.php

require_once 'api/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$saleId = $_GET['id'] ?? null;
if (!$saleId) {
    die('ID de venta no proporcionado.');
}

$data = getData();
$saleData = null;
foreach ($data['sales'] as $s) {
    if ($s['id'] === $saleId) {
        $saleData = $s;
        break;
    }
}

if (!$saleData) {
    die('Venta no encontrada.');
}

$settings = getSettings();
$saleDate = isset($saleData['date']) ? date('d/m/Y H:i:s', strtotime($saleData['date'])) : 'N/A';
$totalSale = 0;
if (isset($saleData['products']) && is_array($saleData['products'])) {
    foreach ($saleData['products'] as $product) {
        $totalSale += ($product['price'] ?? 0) * ($product['quantity'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta - <?php echo htmlspecialchars($saleData['invoice_number'] ?? ''); ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .ticket-container {
            width: 58mm;
            padding: 5px;
            box-sizing: border-box;
        }
        .header, .footer {
            text-align: center;
        }
        .logo {
            max-width: 80%;
            margin: 5px auto;
        }
        .info {
            margin-top: 10px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .products-table th, .products-table td {
            padding: 2px 0;
        }
        .products-table .col-qty { text-align: center; }
        .products-table .col-price, .products-table .col-subtotal { text-align: right; }
        .total-section {
            margin-top: 5px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        .total-section .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
        }
        .print-button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: #fff;
            border: none;
            margin-top: 20px;
            cursor: pointer;
        }
        @media print {
            .no-print, .no-print * {
                display: none !important;
            }
            body, .ticket-container {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="header">
            <?php if (!empty($settings['company_logo'])): ?>
                <img src="<?php echo htmlspecialchars($settings['company_logo']); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <p>
                <strong><?php echo htmlspecialchars($settings['company_name'] ?? ''); ?></strong><br>
                <?php echo htmlspecialchars($settings['company_address'] ?? ''); ?><br>
                Tel: <?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>
            </p>
        </div>

        <div class="info">
            <p>
                Factura #: <?php echo htmlspecialchars($saleData['invoice_number'] ?? ''); ?><br>
                Fecha: <?php echo htmlspecialchars($saleDate); ?><br>
                Cliente: <?php echo htmlspecialchars($saleData['customer_name'] ?? ''); ?>
            </p>
        </div>

        <table class="products-table">
            <thead>
                <tr>
                    <th>Prod</th>
                    <th class="col-qty">Cant</th>
                    <th class="col-price">Precio</th>
                    <th class="col-subtotal">Subt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saleData['products'] as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                    <td class="col-qty"><?php echo htmlspecialchars($product['quantity'] ?? ''); ?></td>
                    <td class="col-price"><?php echo number_format($product['price'] ?? 0, 2); ?></td>
                    <td class="col-subtotal"><?php echo number_format(($product['price'] ?? 0) * ($product['quantity'] ?? 0), 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <span>TOTAL:</span>
                <span>$<?php echo number_format($totalSale, 2); ?></span>
            </div>
        </div>

        <div class="footer">
            <p>¡Gracias por su compra!</p>
            <?php if (!empty($settings['policies'])): ?>
                <p style="font-size: 10px;"><?php echo nl2br(htmlspecialchars($settings['policies'])); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <button class="print-button no-print" onclick="window.print()">Imprimir Ticket</button>

</body>
</html>