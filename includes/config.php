<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root'); // Cambia esto por tu usuario de MySQL
define('DB_PASS', ''); // Cambia esto por tu contraseña de MySQL
define('DB_NAME', 'elevy_db');

// Configuración de la aplicación
define('SITE_NAME', 'Elevy');
define('SITE_URL', 'http://localhost/Elevy'); // Ajusta según tu configuración

// Rutas de directorios
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('PHOTOS_PATH', UPLOADS_PATH . 'photos/');
define('CVS_PATH', UPLOADS_PATH . 'cvs/');

// Configuración de sesión
session_start();
?>