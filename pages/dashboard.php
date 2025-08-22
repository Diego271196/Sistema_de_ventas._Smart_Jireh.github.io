<?php
// pages/dashboard.php

require_once 'api/functions.php';
$settings = getSettings();

?>

<h1 class="mt-4">Dashboard</h1>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Mensaje de Bienvenida</h5>
        <p class="card-text"><?php echo htmlspecialchars($settings['dashboard_welcome_message'] ?? 'Bienvenido al panel de control.'); ?></p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body text-center">
        <?php if (!empty($settings['company_logo'])): ?>
            <img src="<?php echo htmlspecialchars($settings['company_logo']); ?>" alt="Logo de la Empresa" class="img-fluid rounded-circle mb-3" style="max-width: 150px; max-height: 150px; object-fit: cover;">
        <?php endif; ?>
        <h5 class="card-title">Función del Sistema</h5>
        <p class="card-text"><?php echo nl2br(htmlspecialchars($settings['system_function_description'] ?? 'Descripción de la función del sistema.')); ?></p>
    </div>
</div>
