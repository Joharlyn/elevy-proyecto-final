<?php
// Definir rutas de directorios
$upload_dir = __DIR__ . '/uploads';
$photos_dir = $upload_dir . '/photos';
$cvs_dir = $upload_dir . '/cvs';

// Crear directorios si no existen
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "Directorio 'uploads' creado correctamente.<br>";
} else {
    echo "El directorio 'uploads' ya existe.<br>";
}

if (!file_exists($photos_dir)) {
    mkdir($photos_dir, 0755, true);
    echo "Directorio 'photos' creado correctamente.<br>";
} else {
    echo "El directorio 'photos' ya existe.<br>";
}

if (!file_exists($cvs_dir)) {
    mkdir($cvs_dir, 0755, true);
    echo "Directorio 'cvs' creado correctamente.<br>";
} else {
    echo "El directorio 'cvs' ya existe.<br>";
}

// Verificar permisos
$upload_writable = is_writable($upload_dir);
$photos_writable = is_writable($photos_dir);
$cvs_writable = is_writable($cvs_dir);

echo "<h2>Verificación de permisos:</h2>";
echo "Directorio 'uploads': " . ($upload_writable ? "<span style='color:green'>Escritura permitida</span>" : "<span style='color:red'>Sin permisos de escritura</span>") . "<br>";
echo "Directorio 'photos': " . ($photos_writable ? "<span style='color:green'>Escritura permitida</span>" : "<span style='color:red'>Sin permisos de escritura</span>") . "<br>";
echo "Directorio 'cvs': " . ($cvs_writable ? "<span style='color:green'>Escritura permitida</span>" : "<span style='color:red'>Sin permisos de escritura</span>") . "<br>";

// Sugerir comandos si es necesario
if (!$upload_writable || !$photos_writable || !$cvs_writable) {
    echo "<h3>Comandos para solucionar problemas de permisos:</h3>";
    echo "<pre>chmod -R 755 " . $upload_dir . "</pre>";
    echo "<pre>chown -R www-data:www-data " . $upload_dir . "</pre>";
    echo "Nota: el usuario 'www-data' puede ser diferente según tu configuración del servidor web.";
}
?>