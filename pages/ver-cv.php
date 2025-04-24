<?php
$titulo_pagina = 'Ver Currículum';
require_once '../includes/header.php';

// Verificar autenticación
requiereAutenticacion();

// Verificar parámetro de id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$candidato_id = (int)$_GET['id'];

// Si es candidato, solo puede ver su propio CV
if (esRol('candidato') && $candidato_id != $_SESSION['candidato_id']) {
    header('Location: panel-candidato.php');
    exit;
}

// Si es empresa, verificar que el candidato haya aplicado a alguna de sus ofertas
if (esRol('empresa')) {
    $aplicacion = obtenerRegistro(
        "SELECT 1 FROM postulaciones p
        INNER JOIN ofertas o ON p.oferta_id = o.id
        WHERE p.candidato_id = ? AND o.empresa_id = ?",
        [$candidato_id, $_SESSION['empresa_id']]
    );
    
    if (!$aplicacion) {
        header('Location: panel-empresa.php');
        exit;
    }
}

// Obtener datos completos del candidato
$candidato = obtenerDatosCandidato($candidato_id);

if (!$candidato) {
    header('Location: index.php');
    exit;
}

// Obtener datos del usuario
$usuario = obtenerRegistro(
    "SELECT u.email FROM usuarios u
    INNER JOIN candidatos c ON u.id = c.usuario_id
    WHERE c.id = ?",
    [$candidato_id]
);

// Obtener redes profesionales
$linkedin = '';
$portfolio = '';

if (!empty($candidato['redes'])) {
    foreach ($candidato['redes'] as $red) {
        if ($red['tipo'] === 'linkedin') {
            $linkedin = $red['url'];
        } else if ($red['tipo'] === 'portfolio') {
            $portfolio = $red['url'];
        }
    }
}
?>

<div class="container">
    <div style="margin: 30px 0; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <?php if (esRol('empresa')): ?>
                <a href="javascript:history.back()" style="text-decoration: none; color: var(--gray-color);">< Volver</a>
            <?php else: ?>
                <a href="panel-candidato.php" style="text-decoration: none; color: var(--gray-color);">< Volver al Panel</a>
            <?php endif; ?>
            <h1 style="color: var(--primary-color); margin-top: 10px;">Currículum Vitae</h1>
        </div>
        
        <?php if (esRol('candidato') && $candidato_id == $_SESSION['candidato_id']): ?>
            <a href="cv-form.php" class="btn btn-primary">Editar CV</a>
        <?php endif; ?>
    </div>
    
    <div class="card" style="margin-bottom: 30px; padding: 30px;">
        <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 30px;">
            <div style="flex: 0 0 200px;">
                <?php if (!empty($candidato['foto'])): ?>
                    <img src="<?php echo SITE_URL . '/uploads/photos/' . $candidato['foto']; ?>" alt="Foto de perfil" style="width: 100%; border-radius: 10px; margin-bottom: 20px;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/200" alt="Foto de perfil" style="width: 100%; border-radius: 10px; margin-bottom: 20px;">
                <?php endif; ?>
                
                <?php if (!empty($candidato['cv_pdf'])): ?>
                    <a href="<?php echo SITE_URL . '/uploads/cvs/' . $candidato['cv_pdf']; ?>" class="btn btn-outline" target="_blank" style="width: 100%; text-align: center;">
                        <i class="fas fa-file-pdf"></i> Ver CV en PDF
                    </a>
                <?php endif; ?>
            </div>
            
            <div style="flex: 1; min-width: 300px;">
                <h2 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($candidato['nombre']) . ' ' . htmlspecialchars($candidato['apellido']); ?></h2>
                
                <p style="color: var(--gray-color); margin-bottom: 20px;">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?> 
                    <?php if (!empty($candidato['telefono'])): ?>
                        &nbsp;&nbsp;|&nbsp;&nbsp; <i class="fas fa-phone"></i> <?php echo htmlspecialchars($candidato['telefono']); ?>
                    <?php endif; ?>
                </p>
                
                <?php if (!empty($candidato['objetivo_profesional'])): ?>
                    <h3 style="color: var(--primary-color); margin-bottom: 10px; border-bottom: 1px solid var(--light-gray); padding-bottom: 5px;">Objetivo Profesional</h3>
                    <p style="margin-bottom: 20px;"><?php echo nl2br(htmlspecialchars($candidato['objetivo_profesional'])); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($candidato['disponibilidad'])): ?>
                    <div style="margin-bottom: 20px;">
                        <strong>Disponibilidad:</strong> 
                        <?php
                        $disponibilidades = [
                            'inmediata' => 'Inmediata',
                            '15_dias' => 'En 15 días',
                            '30_dias' => 'En 30 días',
                            'negociable' => 'Negociable'
                        ];
                        echo $disponibilidades[$candidato['disponibilidad']] ?? $candidato['disponibilidad'];
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($linkedin) || !empty($portfolio)): ?>
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <?php if (!empty($linkedin)): ?>
                            <a href="<?php echo htmlspecialchars($linkedin); ?>" target="_blank" class="btn btn-outline btn-sm">
                                <i class="fab fa-linkedin"></i> LinkedIn
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($portfolio)): ?>
                            <a href="<?php echo htmlspecialchars($portfolio); ?>" target="_blank" class="btn btn-outline btn-sm">
                                <i class="fas fa-globe"></i> Portfolio
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Formación Académica -->
        <?php if (!empty($candidato['formacion'])): ?>
            <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Formación Académica</h3>
            
            <?php foreach ($candidato['formacion'] as $formacion): ?>
                <div style="margin-bottom: 25px;">
                    <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($formacion['titulo']); ?></h4>
                    <p style="color: var(--accent-color); margin-bottom: 5px;"><?php echo htmlspecialchars($formacion['institucion']); ?></p>
                    <p style="color: var(--gray-color);">
                        <?php echo formatearFecha($formacion['fecha_inicio']); ?> - 
                        <?php echo $formacion['actual'] ? 'Actualidad' : formatearFecha($formacion['fecha_fin']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Experiencia Laboral -->
        <?php if (!empty($candidato['experiencia'])): ?>
            <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Experiencia Laboral</h3>
            
            <?php foreach ($candidato['experiencia'] as $experiencia): ?>
                <div style="margin-bottom: 25px;">
                    <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($experiencia['puesto']); ?></h4>
                    <p style="color: var(--accent-color); margin-bottom: 5px;"><?php echo htmlspecialchars($experiencia['empresa']); ?></p>
                    <p style="color: var(--gray-color); margin-bottom: 10px;">
                        <?php echo formatearFecha($experiencia['fecha_inicio']); ?> - 
                        <?php echo $experiencia['actual'] ? 'Actualidad' : formatearFecha($experiencia['fecha_fin']); ?>
                    </p>
                    <p><?php echo nl2br(htmlspecialchars($experiencia['descripcion'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Habilidades -->
        <?php if (!empty($candidato['habilidades'])): ?>
            <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Habilidades</h3>
            
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                <?php foreach ($candidato['habilidades'] as $habilidad): ?>
                    <?php
                    $nivel_class = '';
                    switch ($habilidad['nivel']) {
                        case 'principiante':
                            $nivel_class = 'background-color: #f8d7da; color: #721c24;';
                            break;
                        case 'intermedio':
                            $nivel_class = 'background-color: #fff3cd; color: #856404;';
                            break;
                        case 'avanzado':
                            $nivel_class = 'background-color: #d1ecf1; color: #0c5460;';
                            break;
                        case 'experto':
                            $nivel_class = 'background-color: #d4edda; color: #155724;';
                            break;
                    }
                    ?>
                    <div style="padding: 8px 15px; border-radius: 20px; <?php echo $nivel_class; ?>">
                        <?php echo htmlspecialchars($habilidad['habilidad']); ?> - 
                        <?php 
                        $niveles = [
                            'principiante' => 'Principiante',
                            'intermedio' => 'Intermedio',
                            'avanzado' => 'Avanzado',
                            'experto' => 'Experto'
                        ];
                        echo $niveles[$habilidad['nivel']] ?? $habilidad['nivel'];
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Idiomas -->
        <?php if (!empty($candidato['idiomas'])): ?>
            <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Idiomas</h3>
            
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                <?php foreach ($candidato['idiomas'] as $idioma): ?>
                    <div style="padding: 8px 15px; border-radius: 5px; background-color: var(--light-color); color: var(--dark-color);">
                        <?php echo htmlspecialchars($idioma['idioma']); ?> - 
                        <?php 
                        $niveles = [
                            'basico' => 'Básico',
                            'intermedio' => 'Intermedio',
                            'avanzado' => 'Avanzado',
                            'nativo' => 'Nativo'
                        ];
                        echo $niveles[$idioma['nivel']] ?? $idioma['nivel'];
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Logros y Proyectos -->
        <?php if (!empty($candidato['logros'])): ?>
            <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Logros y Proyectos</h3>
            
            <div style="margin-bottom: 25px;">
                <?php echo nl2br(htmlspecialchars($candidato['logros'])); ?>
            </div>
        <?php endif; ?>
        
        <!-- Referencias -->
        <?php if (!empty($candidato['referencias'])): ?>
            <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Referencias</h3>
            
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px;">
                <?php foreach ($candidato['referencias'] as $referencia): ?>
                    <div style="flex: 1; min-width: 250px; padding: 15px; border-radius: 5px; background-color: var(--light-color);">
                        <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($referencia['nombre']); ?></h4>
                        <?php if (!empty($referencia['empresa'])): ?>
                            <p style="margin-bottom: 5px;"><?php echo htmlspecialchars($referencia['empresa']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($referencia['telefono'])): ?>
                            <p style="margin-bottom: 5px;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($referencia['telefono']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($referencia['email'])): ?>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($referencia['email']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>