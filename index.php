<?php
$titulo_pagina = 'Inicio';
require_once 'includes/header.php';
?>

<div class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Encuentra el trabajo perfecto para ti</h1>
            <p>Miles de ofertas de empleo te están esperando. Conecta con las mejores empresas y da el siguiente paso en tu carrera profesional.</p>
            <div>
                <a href="<?php echo SITE_URL; ?>/pages/ofertas.php" class="btn btn-accent">Buscar Empleos</a>
                <?php if (!estaAutenticado()): ?>
                <a href="<?php echo SITE_URL; ?>/pages/registro-candidato.php" class="btn btn-outline" style="margin-left: 10px; background-color: white;">Regístrate</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 50px; margin-bottom: 50px;">
    <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;">Encuentra tu próxima oportunidad</h2>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
        <?php
        // Obtener últimas ofertas
        $ofertas_recientes = obtenerOfertas([], 4);
        foreach ($ofertas_recientes as $oferta):
        ?>
        <div class="card" style="flex: 1; min-width: 250px; max-width: 350px;">
            <h3 class="card-title"><?php echo htmlspecialchars($oferta['titulo']); ?></h3>
            <p class="card-text"><?php echo htmlspecialchars($oferta['nombre_empresa']); ?></p>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($oferta['ubicacion']); ?></p>
            <p style="margin-top: 10px;"><?php echo substr(htmlspecialchars($oferta['descripcion']), 0, 100) . '...'; ?></p>
            <div style="margin-top: 15px;">
                <a href="<?php echo SITE_URL; ?>/pages/detalle-oferta.php?id=<?php echo $oferta['id']; ?>" class="btn btn-primary">Ver Detalles</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="<?php echo SITE_URL; ?>/pages/ofertas.php" class="btn btn-outline">Ver todas las ofertas</a>
    </div>
</div>

<div style="background-color: var(--light-color); padding: 50px 0;">
    <div class="container">
        <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;">¿Por qué elegir Elevy?</h2>
        
        <div style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center;">
            <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
                <i class="fas fa-search" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h3 class="card-title">Búsqueda Sencilla</h3>
                <p class="card-text">Encuentra las mejores ofertas de empleo con nuestra herramienta de búsqueda intuitiva.</p>
            </div>
            
            <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
                <i class="fas fa-file-alt" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h3 class="card-title">CV Digital</h3>
                <p class="card-text">Crea un perfil profesional completo que destaque tus habilidades y experiencia.</p>
            </div>
            
            <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
                <i class="fas fa-building" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h3 class="card-title">Empresas Top</h3>
                <p class="card-text">Conecta con las mejores empresas que buscan talento como el tuyo.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>