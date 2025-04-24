<?php
$titulo_pagina = 'Detalle de Oferta';
require_once '../includes/header.php';

// Verificar parámetro de id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ofertas.php');
    exit;
}

$oferta_id = (int)$_GET['id'];

// Obtener datos de la oferta
$oferta = obtenerRegistro(
    "SELECT o.*, e.nombre as empresa_nombre, e.logo as empresa_logo, e.sector as empresa_sector,
            e.ubicacion as empresa_ubicacion
    FROM ofertas o
    INNER JOIN empresas e ON o.empresa_id = e.id
    WHERE o.id = ? AND o.estado = 'activa'",
    [$oferta_id]
);

if (!$oferta) {
    header('Location: ofertas.php');
    exit;
}

// Obtener categorías de la oferta
$categorias = obtenerRegistros(
    "SELECT categoria FROM categorias_ofertas WHERE oferta_id = ?",
    [$oferta_id]
);

// Verificar si el usuario ha aplicado (si está autenticado y es candidato)
$ha_aplicado = false;
if (estaAutenticado() && esRol('candidato')) {
    $ha_aplicado = yaAplico($_SESSION['candidato_id'], $oferta_id);
}

// Manejar aplicación a la oferta
$exito = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar']) && estaAutenticado() && esRol('candidato')) {
    $candidato_id = $_SESSION['candidato_id'];
    
    // Verificar si ya aplicó
    if (yaAplico($candidato_id, $oferta_id)) {
        $error = "Ya has aplicado a esta oferta anteriormente.";
    } else {
        // Verificar si el candidato tiene CV completo
        $candidato = obtenerDatosCandidato($candidato_id);
        $cv_completo = !empty($candidato['objetivo_profesional']) && !empty($candidato['disponibilidad']);
        
        if (!$cv_completo) {
            $error = "Debes completar tu CV antes de aplicar a ofertas. <a href='cv-form.php'>Completar CV</a>";
        } else {
            // Registrar aplicación
            $resultado = ejecutarConsulta(
                "INSERT INTO postulaciones (candidato_id, oferta_id) VALUES (?, ?)",
                [$candidato_id, $oferta_id]
            );
            
            if ($resultado) {
                $exito = true;
                $ha_aplicado = true;
            } else {
                $error = "Ha ocurrido un error al procesar tu aplicación. Por favor, inténtalo de nuevo más tarde.";
            }
        }
    }
}

// Mapeo de tipos de contrato
$tipos_contrato = [
    'tiempo_completo' => 'Tiempo Completo',
    'medio_tiempo' => 'Medio Tiempo',
    'temporal' => 'Temporal',
    'proyecto' => 'Por Proyecto',
    'practicas' => 'Prácticas Profesionales'
];
?>

<div class="container">
    <div style="margin: 30px 0;">
        <a href="ofertas.php" style="text-decoration: none; color: var(--gray-color);">< Volver a Ofertas</a>
        <h1 style="color: var(--primary-color); margin-top: 10px;"><?php echo htmlspecialchars($oferta['titulo']); ?></h1>
        
        <?php if ($exito): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 20px;">
                <p style="margin: 0;">¡Has aplicado correctamente a esta oferta!</p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 20px;">
                <p style="margin: 0;"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 50px;">
        <div style="flex: 2; min-width: 300px;">
            <div class="card" style="margin-bottom: 30px;">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <?php if (!empty($oferta['empresa_logo'])): ?>
                        <img src="<?php echo SITE_URL . '/uploads/photos/' . $oferta['empresa_logo']; ?>" alt="Logo de la empresa" style="width: 80px; height: 80px; border-radius: 5px; object-fit: cover; margin-right: 20px;">
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; border-radius: 5px; background-color: var(--light-gray); display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                            <i class="fas fa-building" style="font-size: 30px; color: var(--gray-color);"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h2 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($oferta['titulo']); ?></h2>
                        <p style="color: var(--gray-color);">
                            <a href="#empresa" style="color: var(--accent-color); text-decoration: none;"><?php echo htmlspecialchars($oferta['empresa_nombre']); ?></a> · 
                            <?php echo htmlspecialchars($oferta['ubicacion']); ?> · 
                            Publicada <?php echo tiempoTranscurrido($oferta['fecha_publicacion']); ?>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($categorias)): ?>
                    <div style="margin-bottom: 20px;">
                        <?php foreach ($categorias as $categoria): ?>
                            <span style="display: inline-block; padding: 5px 10px; background-color: var(--light-gray); color: var(--dark-color); border-radius: 5px; margin-right: 10px; margin-bottom: 10px;">
                                <?php echo htmlspecialchars(ucfirst($categoria['categoria'])); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <h3 style="color: var(--primary-color); margin-bottom: 15px;">Descripción</h3>
                <div style="margin-bottom: 30px;">
                    <?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?>
                </div>
                
                <h3 style="color: var(--primary-color); margin-bottom: 15px;">Requisitos</h3>
                <div style="margin-bottom: 30px;">
                    <?php echo nl2br(htmlspecialchars($oferta['requisitos'])); ?>
                </div>
                
                <?php if (!empty($oferta['beneficios'])): ?>
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">Beneficios</h3>
                    <div>
                        <?php echo nl2br(htmlspecialchars($oferta['beneficios'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="empresa" class="card">
                <h3 style="color: var(--primary-color); margin-bottom: 20px;">Acerca de la empresa</h3>
                
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <?php if (!empty($oferta['empresa_logo'])): ?>
                        <img src="<?php echo SITE_URL . '/uploads/photos/' . $oferta['empresa_logo']; ?>" alt="Logo de la empresa" style="width: 60px; height: 60px; border-radius: 5px; object-fit: cover; margin-right: 15px;">
                    <?php else: ?>
                        <div style="width: 60px; height: 60px; border-radius: 5px; background-color: var(--light-gray); display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                            <i class="fas fa-building" style="font-size: 24px; color: var(--gray-color);"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($oferta['empresa_nombre']); ?></h4>
                        <p style="color: var(--gray-color);"><?php echo htmlspecialchars($oferta['empresa_sector']); ?></p>
                    </div>
                </div>
                
                <p style="margin-bottom: 15px;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($oferta['empresa_ubicacion']); ?></p>
                
                <?php
                // Contar ofertas de esta empresa
                $ofertas_empresa = obtenerRegistro(
                    "SELECT COUNT(*) as total FROM ofertas WHERE empresa_id = ? AND estado = 'activa'",
                    [$oferta['empresa_id']]
                );
                ?>
                
                <p><i class="fas fa-briefcase"></i> <?php echo $ofertas_empresa['total']; ?> ofertas activas</p>
            </div>
        </div>
        
        <div style="flex: 1; min-width: 250px;">
            <div class="card" style="position: sticky; top: 100px;">
                <h3 style="color: var(--primary-color); margin-bottom: 20px;">Detalles de la oferta</h3>
                
                <div style="margin-bottom: 15px;">
                    <p style="margin-bottom: 10px;"><strong>Tipo de contrato:</strong> <?php echo htmlspecialchars($tipos_contrato[$oferta['tipo_contrato']] ?? $oferta['tipo_contrato']); ?></p>
                    
                    <?php if ($oferta['mostrar_salario'] && ($oferta['salario_min'] || $oferta['salario_max'])): ?>
                        <p style="margin-bottom: 10px;"><strong>Salario:</strong> 
                            <?php
                            if ($oferta['salario_min'] && $oferta['salario_max']) {
                                echo number_format($oferta['salario_min'], 2) . ' - ' . number_format($oferta['salario_max'], 2) . ' DOP';
                            } elseif ($oferta['salario_min']) {
                                echo 'Desde ' . number_format($oferta['salario_min'], 2) . ' DOP';
                            } elseif ($oferta['salario_max']) {
                                echo 'Hasta ' . number_format($oferta['salario_max'], 2) . ' DOP';
                            }
                            ?>
                        </p>
                    <?php endif; ?>
                    
                    <p style="margin-bottom: 10px;"><strong>Ubicación:</strong> <?php echo htmlspecialchars($oferta['ubicacion']); ?></p>
                    
                    <?php if (!empty($oferta['fecha_cierre'])): ?>
                        <p style="margin-bottom: 10px;"><strong>Fecha límite:</strong> <?php echo formatearFecha($oferta['fecha_cierre']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (!estaAutenticado()): ?>
                    <p style="margin-bottom: 20px;">Para aplicar a esta oferta, debes iniciar sesión o registrarte como candidato.</p>
                    <a href="login.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">Iniciar Sesión</a>
                    <a href="registro-candidato.php" class="btn btn-outline" style="width: 100%;">Registrarse</a>
                <?php elseif (esRol('candidato')): ?>
                    <?php if ($ha_aplicado): ?>
                        <div style="background-color: var(--light-gray); padding: 15px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                            <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <p style="margin: 0;">Ya has aplicado a esta oferta</p>
                        </div>
                        <a href="mis-postulaciones.php" class="btn btn-outline" style="width: 100%;">Ver Mis Postulaciones</a>
                    <?php else: ?>
                        <form action="detalle-oferta.php?id=<?php echo $oferta_id; ?>" method="post">
                            <input type="hidden" name="aplicar" value="1">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Aplicar Ahora</button>
                        </form>
                    <?php endif; ?>
                <?php elseif (esRol('empresa')): ?>
                    <p style="text-align: center; color: var(--gray-color);">Esta es una vista previa de cómo ven los candidatos tu oferta.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>