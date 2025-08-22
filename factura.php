<?php
// factura.php

require_once 'api/functions.php';

// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$saleId = $_GET['id'] ?? null;
$context = $_GET['context'] ?? 'reprint'; // Default to reprint for safety
$invoiceTitle = ($context === 'new') ? 'FACTURA IMPRESA' : 'FACTURA REIMPRESA';

if (!$saleId) {
    die('ID de venta no proporcionado.');
}

$data = getData();
$saleData = null;

if (isset($data['sales']) && is_array($data['sales'])) {
    foreach ($data['sales'] as $s) {
        if ($s['id'] === $saleId) {
            $saleData = $s;
            break;
        }
    }
}

if (!$saleData) {
    die('Venta no encontrada.');
}

// If this is a reprint and the status is still 'Pendiente', update it.
if ($context === 'reprint' && ($saleData['status'] ?? '') === 'Pendiente') {
    if (function_exists('updateSaleStatus')) {
        updateSaleStatus($saleId, 'Reimpresa'); 
    
        // Reload data to reflect the change immediately
        $data = getData();
        foreach ($data['sales'] as $s) {
            if ($s['id'] === $saleId) {
                $saleData = $s;
                break;
            }
        }
    }
}



$settings = getSettings();

// Generar número de factura si no existe
if (!isset($saleData['invoice_number'])) {
    $invoiceSettings = getInvoiceSettings();
    $lastInvoiceNumber = $invoiceSettings['last_invoice_number'] ?? 0;
    $newInvoiceNumber = $lastInvoiceNumber + 1;
    $saleData['invoice_number'] = str_pad($newInvoiceNumber, 6, '0', STR_PAD_LEFT);

    // Guardar el nuevo número de factura en la configuración de facturas
    $invoiceSettings['last_invoice_number'] = $newInvoiceNumber;
    saveInvoiceSettings($invoiceSettings);

    // Actualizar la venta en los datos generales
    foreach ($data['sales'] as &$s) {
        if ($s['id'] === $saleId) {
            $s['invoice_number'] = $saleData['invoice_number'];
            break;
        }
    }
    saveData($data);
}

// Formatear la fecha
$saleDate = isset($saleData['date']) ? date('d/m/Y H:i:s', strtotime($saleData['date'])) : 'N/A';

// Calcular el total de la venta
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
    <title>Factura No. <?php echo htmlspecialchars($saleData['invoice_number'] ?? ''); ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 16px;
            line-height: 24px;
            color: #555;
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.item.last td {
            border-bottom: none;
        }
        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        .invoice-box .logo {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
        .text-center {
            text-align: center;
        }
        .policy-section {
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <h1 class="text-center" style="margin-bottom: 20px; font-weight: bold;"><?php echo $invoiceTitle; ?></h1>
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title">
                                <?php if (!empty($settings['company_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($settings['company_logo']); ?>" class="logo">
                                <?php endif; ?>
                            </td>
                            <td>
                                Factura #: <?php echo htmlspecialchars($saleData['invoice_number'] ?? ''); ?><br>
                                Creada: <?php echo htmlspecialchars($saleDate); ?><br>
                                <?php if (isset($saleData['status']) && $saleData['status'] !== 'Pendiente'): ?>
                                    <strong>Estado: <?php echo htmlspecialchars($saleData['status']); ?></strong><br>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($settings['company_name'] ?? ''); ?><br>
                                <?php echo htmlspecialchars($settings['company_address'] ?? ''); ?><br>
                                Tel: <?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?><br>
                                WhatsApp: <?php echo htmlspecialchars($settings['company_whatsapp'] ?? ''); ?><br>
                            </td>

                            <td>
                                Cliente: <?php echo htmlspecialchars($saleData['customer_name'] ?? ''); ?><br>
                                Teléfono: <?php echo htmlspecialchars($saleData['customer_phone'] ?? ''); ?><br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Producto</td>
                <td>IMEI/Código</td>
                <td class="text-center">Cantidad</td>
                <td class="text-right">Precio Unitario</td>
                <td class="text-right">Subtotal</td>
            </tr>

            <?php if (isset($saleData['products']) && is_array($saleData['products'])):
                foreach ($saleData['products'] as $product): ?>
                    <tr class="item">
                        <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($product['imei'] ?? ''); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($product['quantity'] ?? ''); ?></td>
                        <td class="text-right">$<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                        <td class="text-right">$<?php echo number_format(($product['price'] ?? 0) * ($product['quantity'] ?? 0), 2); ?></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr class="item last">
                    <td colspan="5" class="text-center">No hay productos en esta venta.</td>
                </tr>
            <?php endif; ?>

            <tr class="total">
                <td colspan="4"></td>
                <td class="text-right">Total: $<?php echo number_format($totalSale, 2); ?></td>
            </tr>
        </table>

        <div class="text-center policy-section">
            <p>Gracias por su compra.</p>
            <?php if (!empty($settings['policies'])): ?>
                <p><strong>Políticas:</strong><br><?php echo nl2br(htmlspecialchars($settings['policies'])); ?></p>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">Imprimir Factura A4</button>
            <a href="factura_ticket.php?id=<?php echo htmlspecialchars($saleId); ?>" target="_blank" class="btn btn-secondary">Imprimir Ticket 58mm</a>
        </div>
    </div>
</body>
</html>