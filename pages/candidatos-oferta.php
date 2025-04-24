<?php
$titulo_pagina = 'Candidatos para Oferta';
require_once '../includes/header.php';

// Verificar autenticación y rol
requiereAutenticacion();
requiereRol('empresa');

// Verificar parámetro de id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: panel-empresa.php');
    exit;
}

$oferta_id = (int)$_GET['id'];

// Verificar que la oferta pertenezca a esta empresa
$oferta = obtenerRegistro(
    "SELECT o.*, e.nombre as empresa_nombre 
    FROM ofertas o 
    INNER JOIN empresas e ON o.empresa_id = e.id 
    WHERE o.id = ? AND o.empresa_id = ?",
    [$oferta_id, $_SESSION['empresa_id']]
);

if (!$oferta) {
    header('Location: panel-empresa.php');
    exit;
}

// Obtener candidatos que aplicaron a esta oferta
$candidatos = obtenerRegistros(
    "SELECT p.id as postulacion_id, p.fecha_postulacion, p.estado, 
            c.id as candidato_id, c.nombre, c.apellido, c.foto, c.telefono, c.cv_pdf,
            u.email
    FROM postulaciones p
    INNER JOIN candidatos c ON p.candidato_id = c.id
    INNER JOIN usuarios u ON c.usuario_id = u.id
    WHERE p.oferta_id = ?
    ORDER BY p.fecha_postulacion DESC",
    [$oferta_id]
);

// Manejar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $postulacion_id = (int)$_POST['postulacion_id'];
    $action = $_POST['action'];
    
    if ($action === 'cambiar_estado' && isset($_POST['estado'])) {
        $estado = limpiarInput($_POST['estado']);
        
        ejecutarConsulta(
            "UPDATE postulaciones SET estado = ? WHERE id = ?",
            [$estado, $postulacion_id]
        );
        
        // Redirigir para evitar reenvío de formulario
        header("Location: candidatos-oferta.php?id=$oferta_id&exito=estado_actualizado");
        exit;
    }
}
?>

<div class="container">
    <div style="margin: 30px 0;">
        <a href="panel-empresa.php" style="text-decoration: none; color: var(--gray-color);">< Volver al Panel</a>
        <h1 style="color: var(--primary-color); margin-top: 10px;">Candidatos para: <?php echo htmlspecialchars($oferta['titulo']); ?></h1>
        <p style="color: var(--gray-color);">Oferta publicada el <?php echo formatearFecha($oferta['fecha_publicacion']); ?> · <?php echo count($candidatos); ?> candidatos</p>
        
        <?php if (isset($_GET['exito']) && $_GET['exito'] === 'estado_actualizado'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 20px;">
                <p style="margin: 0;">Estado actualizado correctamente.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="color: var(--primary-color); margin-bottom: 20px;">Detalles de la oferta</h3>
        
        <div style="display: flex; flex-wrap: wrap; gap: 30px;">
            <div style="flex: 2; min-width: 300px;">
                <h4 style="margin-bottom: 15px;">Descripción</h4>
                <p style="margin-bottom: 20px;"><?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?></p>
                
                <h4 style="margin-bottom: 15px;">Requisitos</h4>
                <div style="margin-bottom: 20px;">
                    <?php echo nl2br(htmlspecialchars($oferta['requisitos'])); ?>
                </div>
            </div>
            
            <div style="flex: 1; min-width: 250px;">
                <div style="background-color: var(--light-color); padding: 20px; border-radius: 10px;">
                    <p style="margin-bottom: 10px;"><strong>Ubicación:</strong> <?php echo htmlspecialchars($oferta['ubicacion']); ?></p>
                    <p style="margin-bottom: 10px;"><strong>Tipo de Contrato:</strong> 
                        <?php 
                        $tipos_contrato = [
                            'tiempo_completo' => 'Tiempo Completo',
                            'medio_tiempo' => 'Medio Tiempo',
                            'temporal' => 'Temporal',
                            'proyecto' => 'Por Proyecto',
                            'practicas' => 'Prácticas Profesionales'
                        ];
                        echo $tipos_contrato[$oferta['tipo_contrato']] ?? $oferta['tipo_contrato'];
                        ?>
                    </p>
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
                    <?php if (!empty($oferta['fecha_cierre'])): ?>
                        <p><strong>Fecha de cierre:</strong> <?php echo formatearFecha($oferta['fecha_cierre']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <a href="publicar-oferta.php?editar=<?php echo $oferta['id']; ?>" class="btn btn-outline">Editar Oferta</a>
                </div>
            </div>
        </div>
    </div>
    
    <h2 style="color: var(--primary-color); margin-bottom: 20px;">Lista de Candidatos</h2>
    
    <?php if (empty($candidatos)): ?>
        <div class="card">
            <p style="text-align: center;">Aún no hay candidatos para esta oferta.</p>
        </div>
    <?php else: ?>
        <?php foreach ($candidatos as $candidato): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 0 0 100px;">
                        <?php if (!empty($candidato['foto'])): ?>
                            <img src="<?php echo SITE_URL . '/uploads/photos/' . $candidato['foto']; ?>" alt="Foto del candidato" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/100" alt="Foto del candidato" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    
                    <div style="flex: 1; min-width: 250px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 10px;"><?php echo htmlspecialchars($candidato['nombre'] . ' ' . $candidato['apellido']); ?></h3>
                        <p style="margin-bottom: 10px;"><strong>Correo:</strong> <?php echo htmlspecialchars($candidato['email']); ?></p>
                        <p style="margin-bottom: 10px;"><strong>Teléfono:</strong> <?php echo htmlspecialchars($candidato['telefono']); ?></p>
                        <p style="margin-bottom: 15px;"><strong>Aplicó:</strong> <?php echo formatearFecha($candidato['fecha_postulacion']); ?></p>
                        
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                            <a href="ver-cv.php?id=<?php echo $candidato['candidato_id']; ?>" class="btn btn-primary">Ver CV Completo</a>
                            <?php if (!empty($candidato['cv_pdf'])): ?>
                                <a href="<?php echo SITE_URL . '/uploads/cvs/' . $candidato['cv_pdf']; ?>" class="btn btn-outline" target="_blank">Ver PDF</a>
                            <?php endif; ?>
                        </div>
                        
                        <form action="candidatos-oferta.php?id=<?php echo $oferta_id; ?>" method="post" style="display: flex; align-items: center; gap: 10px;">
                            <input type="hidden" name="action" value="cambiar_estado">
                            <input type="hidden" name="postulacion_id" value="<?php echo $candidato['postulacion_id']; ?>">
                            
                            <label for="estado_<?php echo $candidato['postulacion_id']; ?>">Estado:</label>
                            <select id="estado_<?php echo $candidato['postulacion_id']; ?>" name="estado" class="form-control" style="width: auto; min-width: 150px;">
                                <option value="pendiente" <?php echo $candidato['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="revisada" <?php echo $candidato['estado'] === 'revisada' ? 'selected' : ''; ?>>Revisada</option>
                                <option value="entrevista" <?php echo $candidato['estado'] === 'entrevista' ? 'selected' : ''; ?>>Entrevista</option>
                                <option value="rechazada" <?php echo $candidato['estado'] === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                                <option value="aceptada" <?php echo $candidato['estado'] === 'aceptada' ? 'selected' : ''; ?>>Aceptada</option>
                            </select>
                            
                            <button type="submit" class="btn btn-outline btn-sm">Actualizar</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>