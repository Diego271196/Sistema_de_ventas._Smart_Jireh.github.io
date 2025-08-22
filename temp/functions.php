<?php

function getSettings() {
    $settingsFile = __DIR__ . '/settings.json';
    $defaultSettings = [
        "company_name" => "Smart Jireh",
        "company_phone" => "829-274-2141",
        "company_whatsapp" => "829-274-2141",
        "company_logo" => "uploads/logo.png",
        "company_address" => "Oviedo, Pedernales, RD",
        "system_title" => "Smart Jireh - Sistema de Venta e Inventario",
        "company_reason" => "Tecnología y accesorios",
        "primary_color" => "#007bff",
        "secondary_color" => "#6c757d",
        "policies" => "Política de devoluciones: 30 días. Política de garantía: 1 año.",
        "dashboard_welcome_message" => "Bienvenido al panel de control de Smart Jireh. Aquí puedes gestionar tu inventario y ventas de forma eficiente.",
        "system_function_description" => "Este sistema te permite gestionar el inventario de productos, registrar ventas, generar facturas, y mantener un control detallado de tu negocio.",
        "social_media_links" => [
            "facebook" => "",
            "twitter" => "",
            "instagram" => "",
            "website" => ""
        ]
    ];

    if (!file_exists($settingsFile)) {
        // If settings file doesn't exist, create it with default settings
        file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
        return $defaultSettings;
    }

    $currentSettings = json_decode(file_get_contents($settingsFile), true);
    // Merge current settings with default settings to ensure all keys are present
    return array_merge($defaultSettings, $currentSettings);
}

function getData() {
    $dataFile = __DIR__ . '/data.json';
    if (!file_exists($dataFile)) {
        return ['inventory' => [], 'sales' => []];
    }
    $jsonContent = file_get_contents($dataFile);
    if ($jsonContent === false) {
        return ['inventory' => [], 'sales' => []];
    }
    $data = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['inventory' => [], 'sales' => []];
    }
    // Ensure 'sales' and 'inventory' keys exist and are arrays
    if (!isset($data['sales']) || !is_array($data['sales'])) {
        $data['sales'] = [];
    }
    if (!isset($data['inventory']) || !is_array($data['inventory'])) {
        $data['inventory'] = [];
    }
    if (!isset($data['customers']) || !is_array($data['customers'])) {
        $data['customers'] = [];
    }
    if (!isset($data['suppliers']) || !is_array($data['suppliers'])) {
        $data['suppliers'] = [];
    }
    return $data;
}

function saveData($data) {
    $dataFile = __DIR__ . '/data.json';
    // file_put_contents returns the number of bytes that were written to the file, or FALSE on failure.
    return file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT)) !== FALSE;
}

function getInvoiceSettings() {
    $invoiceSettingsFile = __DIR__ . '/invoice_settings.json';
    $defaultInvoiceSettings = [
        "last_invoice_number" => 0
    ];

    if (!file_exists($invoiceSettingsFile)) {
        file_put_contents($invoiceSettingsFile, json_encode($defaultInvoiceSettings, JSON_PRETTY_PRINT));
        return $defaultInvoiceSettings;
    }

    $currentInvoiceSettings = json_decode(file_get_contents($invoiceSettingsFile), true);
    return array_merge($defaultInvoiceSettings, $currentInvoiceSettings);
}

function saveInvoiceSettings($settings) {
    $invoiceSettingsFile = __DIR__ . '/invoice_settings.json';
    file_put_contents($invoiceSettingsFile, json_encode($settings, JSON_PRETTY_PRINT));
}

function updateSaleStatus($saleId, $newStatus) {
    $data = getData();
    foreach ($data['sales'] as &$sale) {
        if ($sale['id'] === $saleId) {
            $sale['status'] = $newStatus;
            break;
        }
    }
    saveData($data);
}

function updateInventoryQuantity(&$data, $productId, $quantityChange) {
    foreach ($data['inventory'] as &$product) {
        if ($product['id'] === $productId) {
            $product['quantity'] += $quantityChange;
            break;
        }
    }
}

function getUsers() {
    $usersFile = __DIR__ . '/users.json';
    if (!file_exists($usersFile)) {
        return [];
    }
    $jsonContent = file_get_contents($usersFile);
    if ($jsonContent === false) {
        return [];
    }
    $users = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    return $users;
}

function saveUsers($users) {
    $usersFile = __DIR__ . '/users.json';
    return file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== FALSE;
}

function addUser($username, $password, $role) {
    $users = getUsers();
    $newUser = [
        'id' => uniqid(),
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role
    ];
    $users[] = $newUser;
    return saveUsers($users);
}

function editUser($userId, $newUsername, $newPassword, $newRole) {
    $users = getUsers();
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['username'] = $newUsername;
            if (!empty($newPassword)) {
                $user['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            } else {
                // If new password is empty, keep the old one
                // No change needed as $user['password'] already holds the old hash
            }
            $user['role'] = $newRole;
            return saveUsers($users);
        }
    }
    return false;
}

function deleteUser($userId) {
    $users = getUsers();
    $initialCount = count($users);
    $users = array_filter($users, function($user) use ($userId) {
        return $user['id'] !== $userId;
    });
    // Reindex array after filter
    $users = array_values($users);
    if (count($users) < $initialCount) {
        return saveUsers($users);
    }
    return false;
}

?>