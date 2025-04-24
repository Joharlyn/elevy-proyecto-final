<?php
$titulo_pagina = 'Panel de Empresa';
require_once '../includes/header.php';

// Verificar autenticación y rol
requiereAutenticacion();
requiereRol('empresa');

// Obtener datos de la empresa
$empresa = obtenerDatosEmpresa($_SESSION['empresa_id']);

// Obtener ofertas activas de la empresa
$ofertas = obtenerRegistros(
    "SELECT * FROM ofertas WHERE empresa_id = ? ORDER BY fecha_publicacion DESC",
    [$_SESSION['empresa_id']]
);

// Contar candidatos nuevos
$candidatos_nuevos = obtenerRegistro(
    "SELECT COUNT(*) as total FROM postulaciones p 
    INNER JOIN ofertas o ON p.oferta_id = o.id 
    WHERE o.empresa_id = ? AND p.estado = 'pendiente'",
    [$_SESSION['empresa_id']]
);

// Contar entrevistas programadas
$entrevistas = obtenerRegistro(
    "SELECT COUNT(*) as total FROM postulaciones p 
    INNER JOIN ofertas o ON p.oferta_id = o.id 
    WHERE o.empresa_id = ? AND p.estado = 'entrevista'",
    [$_SESSION['empresa_id']]
);
?>

<div class="container">
    <div style="margin: 30px 0;">
        <h1 style="color: var(--primary-color);">Panel de Empresa</h1>
        
        <?php if (isset($_GET['exito']) && $_GET['exito'] === 'oferta_publicada'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 20px;">
                <p style="margin: 0;">La oferta se ha publicado correctamente.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="dashboard">
        <div class="sidebar">
            <div style="text-align: center; margin-bottom: 20px;">
                <?php if (!empty($empresa['logo'])): ?>
                    <img src="<?php echo SITE_URL . '/uploads/photos/' . $empresa['logo']; ?>" alt="Logo de empresa" style="width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" alt="Logo de empresa" style="width: 120px; height: 120px; object-fit: cover;">
                <?php endif; ?>
                <h3 style="margin-top: 10px;"><?php echo htmlspecialchars($empresa['nombre']); ?></h3>
                <p style="color: var(--gray-color);"><?php echo htmlspecialchars($empresa['sector']); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="#" class="active">Dashboard</a></li>
                <li><a href="publicar-oferta.php">Publicar Oferta</a></li>
                <li><a href="mis-ofertas.php">Mis Ofertas</a></li>
                <li><a href="candidatos.php">Candidatos</a></li>
                <li><a href="perfil-empresa.php">Perfil de Empresa</a></li>
                <li><a href="configuracion.php">Configuración</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div style="margin-bottom: 30px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">Bienvenido, <?php echo htmlspecialchars($empresa['nombre']); ?></h2>
                <p>Desde aquí puedes gestionar tus ofertas de empleo y revisar los candidatos que han aplicado.</p>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
                    <h3 style="font-size: 2.5rem; color: var(--primary-color);"><?php echo count($ofertas); ?></h3>
                    <p>Ofertas Activas</p>
                </div>
                
                <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
                    <h3 style="font-size: 2.5rem; color: var(--secondary-color);"><?php echo $candidatos_nuevos['total']; ?></h3>
                    <p>Nuevos Candidatos</p>
                </div>
                
                <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
                    <h3 style="font-size: 2.5rem; color: var(--accent-color);"><?php echo $entrevistas['total']; ?></h3>
                    <p>Entrevistas Programadas</p>
                </div>
            </div>
            
            <div>
                <h3 style="color: var(--primary-color); margin-bottom: 15px;">Mis Ofertas Publicadas</h3>
                
                <?php if (empty($ofertas)): ?>
                    <div class="card">
                        <p style="text-align: center;">No has publicado ofertas aún.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ofertas as $oferta): ?>
                        <?php
                        // Contar postulaciones por oferta
                        $postulaciones = obtenerRegistro(
                            "SELECT COUNT(*) as total FROM postulaciones WHERE oferta_id = ?",
                            [$oferta['id']]
                        );
                        ?>
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h4 style="color: var(--primary-color); margin-bottom: 10px;"><?php echo htmlspecialchars($oferta['titulo']); ?></h4>
                                    <p style="margin-bottom: 10px;"><strong>Ubicación:</strong> <?php echo htmlspecialchars($oferta['ubicacion']); ?></p>
                                    <p style="margin-bottom: 10px;"><strong>Publicada:</strong> <?php echo formatearFecha($oferta['fecha_publicacion']); ?></p>
                                    <p><span style="background-color: var(--light-color); padding: 3px 8px; border-radius: 4px;"><?php echo $postulaciones['total']; ?> candidatos</span></p>
                                </div>
                                <div>
                                    <a href="candidatos-oferta.php?id=<?php echo $oferta['id']; ?>" class="btn btn-primary" style="margin-bottom: 10px; display: block;">Ver Candidatos</a>
                                    <a href="publicar-oferta.php?editar=<?php echo $oferta['id']; ?>" class="btn btn-outline" style="display: block;">Editar</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="publicar-oferta.php" class="btn btn-accent">Publicar Nueva Oferta</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>