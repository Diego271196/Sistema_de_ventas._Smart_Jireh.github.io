<?php
// pages/settings.php

// Incluir funciones de configuración y datos
require_once 'api/functions.php';

$settings = getSettings();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actualizar la configuración con los datos del formulario
    $settings['company_name'] = $_POST['company_name'] ?? '';
    $settings['company_phone'] = $_POST['company_phone'] ?? '';
    $settings['company_whatsapp'] = $_POST['company_whatsapp'] ?? '';
    $settings['company_address'] = $_POST['company_address'] ?? '';
    $settings['system_title'] = $_POST['system_title'] ?? '';
    $settings['company_reason'] = $_POST['company_reason'] ?? '';
    $settings['primary_color'] = $_POST['primary_color'] ?? '';
    $settings['secondary_color'] = $_POST['secondary_color'] ?? '';
    $settings['policies'] = $_POST['policies'] ?? '';
    $settings['dashboard_welcome_message'] = $_POST['dashboard_welcome_message'] ?? '';
    $settings['system_function_description'] = $_POST['system_function_description'] ?? '';

    // Manejo de redes sociales
    $settings['social_media_links']['facebook'] = $_POST['social_media_facebook'] ?? '';
    $settings['social_media_links']['twitter'] = $_POST['social_media_twitter'] ?? '';
    $settings['social_media_links']['instagram'] = $_POST['social_media_instagram'] ?? '';
    $settings['social_media_links']['website'] = $_POST['social_media_website'] ?? '';

    // Manejo de la carga del logo
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
        $newFileName = 'logo_' . uniqid() . '.' . $fileExtension;
        $uploadFile = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadFile)) {
            $settings['company_logo'] = $uploadFile;
            $message = '<div class="alert alert-success">Configuración guardada y logo actualizado.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error al subir el logo.</div>';
        }
    } else {
        $message = '<div class="alert alert-success">Configuración guardada.</div>';
    }

    // Guardar la configuración actualizada
    file_put_contents('api/settings.json', json_encode($settings, JSON_PRETTY_PRINT));
}

?>

<h1 class="mt-4">Ajustes del Sistema</h1>

<?php echo $message; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="company_name">Nombre de la Empresa</label>
        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="system_title">Título del Sistema</label>
        <input type="text" class="form-control" id="system_title" name="system_title" value="<?php echo htmlspecialchars($settings['system_title'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="company_phone">Teléfono de la Empresa</label>
        <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="company_whatsapp">WhatsApp de la Empresa</label>
        <input type="text" class="form-control" id="company_whatsapp" name="company_whatsapp" value="<?php echo htmlspecialchars($settings['company_whatsapp'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="company_address">Dirección de la Empresa</label>
        <input type="text" class="form-control" id="company_address" name="company_address" value="<?php echo htmlspecialchars($settings['company_address'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="company_reason">Razón Social / Giro</label>
        <input type="text" class="form-control" id="company_reason" name="company_reason" value="<?php echo htmlspecialchars($settings['company_reason'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="dashboard_welcome_message">Mensaje de Bienvenida del Dashboard</label>
        <textarea class="form-control" id="dashboard_welcome_message" name="dashboard_welcome_message" rows="3"><?php echo htmlspecialchars($settings['dashboard_welcome_message'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <label for="system_function_description">Descripción de la Función del Sistema</label>
        <textarea class="form-control" id="system_function_description" name="system_function_description" rows="3"><?php echo htmlspecialchars($settings['system_function_description'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <label for="primary_color">Color Primario</label>
        <input type="color" class="form-control" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($settings['primary_color'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="secondary_color">Color Secundario</label>
        <input type="color" class="form-control" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($settings['secondary_color'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="policies">Políticas (HTML)</label>
        <textarea class="form-control" id="policies" name="policies" rows="5"><?php echo htmlspecialchars($settings['policies'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <label for="company_logo">Logo de la Empresa</label>
        <input type="file" class="form-control-file" id="company_logo" name="company_logo">
        <?php if (!empty($settings['company_logo'])): ?>
            <small class="form-text text-muted">Logo actual: <img src="<?php echo htmlspecialchars($settings['company_logo']); ?>" alt="Logo actual" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 50%;"></small>
        <?php endif; ?>
    </div>

    <h5 class="mt-4">Redes Sociales y Web</h5>
    <div class="form-group">
        <label for="social_media_facebook">Facebook URL</label>
        <input type="url" class="form-control" id="social_media_facebook" name="social_media_facebook" value="<?php echo htmlspecialchars($settings['social_media_links']['facebook'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="social_media_twitter">Twitter URL</label>
        <input type="url" class="form-control" id="social_media_twitter" name="social_media_twitter" value="<?php echo htmlspecialchars($settings['social_media_links']['twitter'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="social_media_instagram">Instagram URL</label>
        <input type="url" class="form-control" id="social_media_instagram" name="social_media_instagram" value="<?php echo htmlspecialchars($settings['social_media_links']['instagram'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="social_media_website">Sitio Web URL</label>
        <input type="url" class="form-control" id="social_media_website" name="social_media_website" value="<?php echo htmlspecialchars($settings['social_media_links']['website'] ?? ''); ?>">
    </div>

    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
</form>
