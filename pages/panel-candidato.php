<?php
$titulo_pagina = 'Panel de Candidato';
require_once '../includes/header.php';

// Verificar autenticación y rol
requiereAutenticacion();
requiereRol('candidato');

// Obtener datos del candidato
$candidato = obtenerDatosCandidato($_SESSION['candidato_id']);

// Obtener postulaciones activas
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

// Contar entrevistas pendientes
$entrevistas = 0;
foreach ($postulaciones as $postulacion) {
    if ($postulacion['estado'] === 'entrevista') {
        $entrevistas++;
    }
}

// Calcular porcentaje de completado del perfil
$campos_requeridos = [
    !empty($candidato['objetivo_profesional']),
    !empty($candidato['disponibilidad']),
    !empty($candidato['formacion']),
    !empty($candidato['experiencia']),
    !empty($candidato['habilidades']),
    !empty($candidato['idiomas'])
];

$porcentaje_completado = round((count(array_filter($campos_requeridos)) / count($campos_requeridos)) * 100);

// Obtener ofertas recomendadas basadas en habilidades del candidato
$ofertas_recomendadas = [];
if (!empty($candidato['habilidades'])) {
    // Obtener palabras clave de las habilidades
    $keywords = [];
    foreach ($candidato['habilidades'] as $habilidad) {
        $keywords[] = $habilidad['habilidad'];
    }
    
    // Buscar ofertas con estas palabras clave en título o descripción
    if (!empty($keywords)) {
        $palabras_clave = implode('|', $keywords);
        
        $ofertas_recomendadas = obtenerRegistros(
            "SELECT o.id, o.titulo, o.ubicacion, o.fecha_publicacion, e.nombre as empresa_nombre
            FROM ofertas o
            INNER JOIN empresas e ON o.empresa_id = e.id
            WHERE o.estado = 'activa'
            AND (o.titulo REGEXP ? OR o.descripcion REGEXP ? OR o.requisitos REGEXP ?)
            AND o.id NOT IN (SELECT oferta_id FROM postulaciones WHERE candidato_id = ?)
            ORDER BY o.fecha_publicacion DESC
            LIMIT 5",
            [$palabras_clave, $palabras_clave, $palabras_clave, $_SESSION['candidato_id']]
        );
    }
}

// Si no hay suficientes ofertas recomendadas, completar con las más recientes
if (count($ofertas_recomendadas) < 2) {
    $ofertas_recientes = obtenerRegistros(
        "SELECT o.id, o.titulo, o.ubicacion, o.fecha_publicacion, e.nombre as empresa_nombre
        FROM ofertas o
        INNER JOIN empresas e ON o.empresa_id = e.id
        WHERE o.estado = 'activa'
        AND o.id NOT IN (SELECT oferta_id FROM postulaciones WHERE candidato_id = ?)
        ORDER BY o.fecha_publicacion DESC
        LIMIT ?",
        [$_SESSION['candidato_id'], 3 - count($ofertas_recomendadas)]
    );
    
    $ofertas_recomendadas = array_merge($ofertas_recomendadas, $ofertas_recientes);
}

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
    <div style="margin: 30px 0; display: flex; justify-content: space-between; align-items: center;">
        <h1 style="color: var(--primary-color);">Panel de Candidato</h1>
    </div>
    
    <div class="dashboard">
        <div class="sidebar">
            <div style="text-align: center; margin-bottom: 20px;">
                <?php if (!empty($candidato['foto'])): ?>
                    <img src="<?php echo SITE_URL . '/uploads/photos/' . $candidato['foto']; ?>" alt="Foto de perfil" style="border-radius: 50%; width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" alt="Foto de perfil" style="border-radius: 50%; width: 120px; height: 120px; object-fit: cover;">
                <?php endif; ?>
                <h3 style="margin-top: 10px;"><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                <p style="color: var(--gray-color);">Candidato</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="#" class="active">Dashboard</a></li>
                <li><a href="cv-form.php">Mi CV</a></li>
                <li><a href="ofertas.php">Buscar Ofertas</a></li>
                <li><a href="mis-postulaciones.php">Mis Postulaciones</a></li>
                <li><a href="configuracion.php">Configuración</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div style="margin-bottom: 30px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">Bienvenido, <?php echo htmlspecialchars($candidato['nombre']); ?></h2>
                <p>Desde aquí puedes gestionar tu perfil, ver tus postulaciones y buscar nuevas oportunidades laborales.</p>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
                    <h3 style="font-size: 2.5rem; color: var(--primary-color);"><?php echo count($postulaciones); ?></h3>
                    <p>Postulaciones Activas</p>
                </div>
                
                <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
                    <h3 style="font-size: 2.5rem; color: var(--secondary-color);"><?php echo $entrevistas; ?></h3>
                    <p>Entrevistas Pendientes</p>
                </div>
                
                <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
                    <h3 style="font-size: 2.5rem; color: var(--accent-color);"><?php echo $porcentaje_completado; ?>%</h3>
                    <p>Perfil Completado</p>
                </div>
            </div>
            
            <div style="margin-bottom: 40px;">
                <h3 style="color: var(--primary-color); margin-bottom: 15px;">Mis Últimas Postulaciones</h3>
                
                <?php if (empty($postulaciones)): ?>
                    <div class="card">
                        <p style="text-align: center;">No tienes postulaciones activas.</p>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="ofertas.php" class="btn btn-primary">Buscar Ofertas</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                    // Mostrar solo las últimas 3 postulaciones
                    $postulaciones_recientes = array_slice($postulaciones, 0, 3);
                    foreach ($postulaciones_recientes as $postulacion): 
                    ?>
                        <div class="card" style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h4 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($postulacion['titulo']); ?></h4>
                                    <p style="color: var(--accent-color); margin-bottom: 10px;"><?php echo htmlspecialchars($postulacion['empresa_nombre']); ?></p>
                                    <p style="margin-bottom: 10px;"><?php echo htmlspecialchars($postulacion['ubicacion']); ?> · <?php echo htmlspecialchars($tipos_contrato[$postulacion['tipo_contrato']] ?? $postulacion['tipo_contrato']); ?></p>
                                    <p>
                                        <span style="padding: 5px 10px; border-radius: 4px; <?php echo $colores_estado[$postulacion['estado']] ?? ''; ?>">
                                            <?php echo htmlspecialchars($estados_postulacion[$postulacion['estado']] ?? $postulacion['estado']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p style="color: var(--gray-color); margin-bottom: 10px;">Aplicada: <?php echo formatearFecha($postulacion['fecha_postulacion']); ?></p>
                                    <a href="detalle-oferta.php?id=<?php echo $postulacion['oferta_id']; ?>" class="btn btn-outline">Ver Oferta</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($postulaciones) > 3): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="mis-postulaciones.php" class="btn btn-outline">Ver Todas Mis Postulaciones</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div>
                <h3 style="color: var(--primary-color); margin-bottom: 15px;">Ofertas Recomendadas</h3>
                
                <?php if (empty($ofertas_recomendadas)): ?>
                    <div class="card">
                        <p style="text-align: center;">No hay ofertas recomendadas en este momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ofertas_recomendadas as $oferta): ?>
                        <div class="card" style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h4 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($oferta['titulo']); ?></h4>
                                    <p style="color: var(--accent-color); margin-bottom: 10px;"><?php echo htmlspecialchars($oferta['empresa_nombre']); ?></p>
                                    <p style="margin-bottom: 10px;"><strong>Ubicación:</strong> <?php echo htmlspecialchars($oferta['ubicacion']); ?></p>
                                    <p><strong>Publicada:</strong> <?php echo tiempoTranscurrido($oferta['fecha_publicacion']); ?></p>
                                </div>
                                <a href="detalle-oferta.php?id=<?php echo $oferta['id']; ?>" class="btn btn-primary">Ver Detalles</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="ofertas.php" class="btn btn-outline">Ver todas las ofertas</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>