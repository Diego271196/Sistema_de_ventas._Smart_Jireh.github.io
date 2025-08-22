<?php
// backups.php - Página de gestión de respaldos

// Asegurarse de que solo usuarios logueados puedan acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$backupDir = __DIR__ . '/../backups/';
$dataDir = __DIR__ . '/../api/';
$backupFiles = [];
$message = '';
$messageType = ''; // 'success' or 'danger'

// Ensure the backups directory exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $timestamp = date('Ymd_His');
    $backupFileName = 'backup_' . $timestamp . '.zip';
    $backupFilePath = $backupDir . $backupFileName;

    $zip = new ZipArchive();
    if ($zip->open($backupFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $zip->addFile($dataDir . 'settings.json', 'settings.json');
        $zip->addFile($dataDir . 'data.json', 'data.json');
        $zip->addFile($dataDir . 'invoice_settings.json', 'invoice_settings.json');
        $zip->close();
        $message = 'Respaldo creado exitosamente: ' . $backupFileName;
        $messageType = 'success';
    } else {
        $message = 'Error al crear el respaldo.';
        $messageType = 'danger';
    }
}

// Handle backup deletion
if (isset($_GET['delete_backup'])) {
    $fileToDelete = basename($_GET['delete_backup']); // Sanitize input
    $filePath = $backupDir . $fileToDelete;
    if (file_exists($filePath) && unlink($filePath)) {
        $message = 'Respaldo "' . htmlspecialchars($fileToDelete) . '" eliminado exitosamente.';
        $messageType = 'success';
    } else {
        $message = 'Error al eliminar el respaldo o el archivo no existe.';
        $messageType = 'danger';
    }
}

// Handle backup restoration
if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] == UPLOAD_ERR_OK) {
    $uploadedFile = $_FILES['restore_file']['tmp_name'];
    $fileName = basename($_FILES['restore_file']['name']);
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

    if ($fileExtension === 'zip') {
        $zip = new ZipArchive;
        if ($zip->open($uploadedFile) === TRUE) {
            // Extract only the specific JSON files
            $extracted = false;
            if ($zip->extractTo($dataDir, ['settings.json', 'data.json', 'invoice_settings.json'])) {
                $extracted = true;
            }
            $zip->close();

            if ($extracted) {
                $message = 'Respaldo "' . htmlspecialchars($fileName) . '" restaurado exitosamente.';
                $messageType = 'success';
            } else {
                $message = 'Error al extraer los archivos del respaldo. Asegúrate de que el ZIP contenga settings.json, data.json e invoice_settings.json.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Error al abrir el archivo ZIP. Asegúrate de que sea un archivo ZIP válido.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Tipo de archivo no permitido. Por favor, sube un archivo ZIP.';
        $messageType = 'danger';
    }
} else if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] != UPLOAD_ERR_NO_FILE) {
    $message = 'Error al subir el archivo: Código ' . $_FILES['restore_file']['error'];
    $messageType = 'danger';
}


// List existing backup files
$scannedFiles = scandir($backupDir);
foreach ($scannedFiles as $file) {
    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
        $backupFiles[] = $file;
    }
}
rsort($backupFiles); // Sort by newest first

?>

<div class="container-fluid">
    <h1 class="mt-4">Gestión de Respaldo</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            Crear Nuevo Respaldo
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <button type="submit" name="create_backup" class="btn btn-primary">Crear Respaldo Ahora</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Restaurar Respaldo
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="restore_file">Seleccionar archivo de respaldo (.zip):</label>
                    <input type="file" class="form-control-file" id="restore_file" name="restore_file" accept=".zip" required>
                </div>
                <button type="submit" class="btn btn-warning" onclick="return confirm('¿Estás seguro de que quieres restaurar este respaldo? Esto sobrescribirá los datos actuales del sistema.');">Restaurar Respaldo</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Respaldos Existentes
        </div>
        <div class="card-body">
            <?php if (empty($backupFiles)): ?>
                <p>No hay respaldos disponibles.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nombre del Archivo</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backupFiles as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', filemtime($backupDir . $file)); ?></td>
                                    <td>
                                        <a href="<?php echo 'backups/' . urlencode($file); ?>" class="btn btn-info btn-sm" download>Descargar</a>
                                        <a href="?page=backups&delete_backup=<?php echo urlencode($file); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres eliminar este respaldo? Esta acción no se puede deshacer.');">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Optional: Add more sophisticated JS for confirmations or dynamic updates
</script>
