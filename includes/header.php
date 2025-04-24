<?php 
// Determinar la ruta raíz del proyecto
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/Elevy';

// Incluir archivos con rutas absolutas
require_once $root_path . '/includes/config.php'; 
require_once $root_path . '/includes/functions.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="<?php echo SITE_URL; ?>/index.php" class="logo">E<span>levy</span></a>
            <ul class="nav-links">
                <li><a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/ofertas.php"><i class="fas fa-briefcase"></i> Ofertas</a></li>
                
                <?php if (estaAutenticado()): ?>
                    <?php if (esRol('candidato')): ?>
                        <li><a href="<?php echo SITE_URL; ?>/pages/panel-candidato.php" class="btn-nav btn-login"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/pages/panel-empresa.php" class="btn-nav btn-login"><i class="fas fa-user"></i> Mi Perfil</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>/pages/logout.php" class="btn-nav btn-register"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn-nav btn-login"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/registro-candidato.php" class="btn-nav btn-register"><i class="fas fa-user-plus"></i> Regístrate</a></li>
                <?php endif; ?>
            </ul>
            <button id="mobile-menu-btn" class="btn btn-outline d-md-none">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </header>