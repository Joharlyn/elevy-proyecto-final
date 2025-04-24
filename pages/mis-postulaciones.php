<?php
$titulo_pagina = 'Mis Postulaciones';
require_once '../includes/header.php';

// Verificar autenticación y rol
requiereAutenticacion();
requiereRol('candidato');

// Obtener postulaciones
$postulaciones = obtenerRegistros(
    "SELECT p.id, p.fecha_postulacion, p.estado, o.id as oferta_id, o.titulo, 
            o.ubicacion, o.tipo_contrato, o.fecha_publicacion, e.nombre as empresa_nombre
    FROM postulaciones p
    INNER JOIN ofertas o ON p.oferta_id = o.id
    INNER JOIN empresas e ON o.empresa_id = e.id
    WHERE p.candidato_id = ?
    ORDER BY p.fecha_postulacion DESC",
    [$_SESSION['candidato_id']]
);

// Mapeo de tipos de contrato
$tipos_contrato = [
    'tiempo_completo' => 'Tiempo Completo',
    'medio_tiempo' => 'Medio Tiempo',
    'temporal' => 'Temporal',
    'proyecto' => 'Por Proyecto',
    'practicas' => 'Prácticas Profesionales'
];

// Mapeo de estados de postulación
$estados_postulacion = [
    'pendiente' => 'Pendiente',
    'revisada' => 'Revisada',
    'entrevista' => 'Entrevista',
    'rechazada' => 'Rechazada',
    'aceptada' => 'Aceptada'
];

// Colores para los estados
$colores_estado = [
    'pendiente' => 'background-color: #fff3cd; color: #856404;',
    'revisada' => 'background-color: #d1ecf1; color: #0c5460;',
    'entrevista' => 'background-color: #d4edda; color: #155724;',
    'rechazada' => 'background-color: #f8d7da; color: #721c24;',
    'aceptada' => 'background-color: #d4edda; color: #155724;'
];
?>

<div class="container">
    <div style="margin: 30px 0;">
        <h1 style="color: var(--primary-color);">Mis Postulaciones</h1>
    </div>
    
    <div class="dashboard">
        <div class="sidebar">
            <div style="text-align: center; margin-bottom: 20px;">
                <?php
                // Obtener datos del candidato
                $candidato = obtenerDatosCandidato($_SESSION['candidato_id']);
                ?>
                
                <?php if (!empty($candidato['foto'])): ?>
                    <img src="<?php echo SITE_URL . '/uploads/photos/' . $candidato['foto']; ?>" alt="Foto de perfil" style="border-radius: 50%; width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" alt="Foto de perfil" style="border-radius: 50%; width: 120px; height: 120px; object-fit: cover;">
                <?php endif; ?>
                <h3 style="margin-top: 10px;"><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                <p style="color: var(--gray-color);">Candidato</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="panel-candidato.php">Dashboard</a></li>
                <li><a href="cv-form.php">Mi CV</a></li>
                <li><a href="ofertas.php">Buscar Ofertas</a></li>
                <li><a href="#" class="active">Mis Postulaciones</a></li>
                <li><a href="configuracion.php">Configuración</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <?php if (empty($postulaciones)): ?>
                <div class="card" style="text-align: center; padding: 30px;">
                    <p style="margin-bottom: 20px;">No tienes postulaciones activas.</p>
                    <a href="ofertas.php" class="btn btn-primary">Buscar Ofertas</a>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <p>Tienes <strong><?php echo count($postulaciones); ?></strong> postulaciones activas.</p>
                </div>
                
                <?php foreach ($postulaciones as $postulacion): ?>
                    <div class="card" style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($postulacion['titulo']); ?></h3>
                                <p style="color: var(--accent-color); margin-bottom: 10px;"><?php echo htmlspecialchars($postulacion['empresa_nombre']); ?></p>
                                <p style="margin-bottom: 10px;"><?php echo htmlspecialchars($postulacion['ubicacion']); ?> · <?php echo htmlspecialchars($tipos_contrato[$postulacion['tipo_contrato']] ?? $postulacion['tipo_contrato']); ?></p>
                                <p style="display: flex; gap: 15px; margin-bottom: 15px;">
                                    <span style="padding: 5px 10px; border-radius: 4px; <?php echo $colores_estado[$postulacion['estado']] ?? ''; ?>">
                                        <?php echo htmlspecialchars($estados_postulacion[$postulacion['estado']] ?? $postulacion['estado']); ?>
                                    </span>
                                    <span>Aplicada: <?php echo formatearFecha($postulacion['fecha_postulacion']); ?></span>
                                </p>
                                
                                <?php if ($postulacion['estado'] === 'entrevista'): ?>
                                    <div style="background-color: rgba(40, 167, 69, 0.1); padding: 10px; border-radius: 5px; border-left: 4px solid #28a745; margin-bottom: 15px;">
                                        <h4 style="margin-bottom: 5px; color: #155724;">¡Has sido seleccionado para una entrevista!</h4>
                                        <p style="margin: 0;">La empresa se pondrá en contacto contigo vía correo electrónico para coordinar la fecha y hora.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="detalle-oferta.php?id=<?php echo $postulacion['oferta_id']; ?>" class="btn btn-primary">Ver Oferta</a>
                            </div>
                            
                            <div style="text-align: right;">
                                <?php if ($postulacion['estado'] === 'rechazada'): ?>
                                    <div style="padding: 10px; border-radius: 5px; background-color: #f8d7da; color: #721c24; margin-bottom: 15px; text-align: center;">
                                        <p style="margin: 0;">Tu aplicación no ha sido seleccionada.</p>
                                    </div>
                                <?php elseif ($postulacion['estado'] === 'aceptada'): ?>
                                    <div style="padding: 10px; border-radius: 5px; background-color: #d4edda; color: #155724; margin-bottom: 15px; text-align: center;">
                                        <p style="margin: 0;">¡Felicidades! Has sido seleccionado para el puesto.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>