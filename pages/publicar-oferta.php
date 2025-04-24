<?php
$titulo_pagina = 'Publicar Oferta';
require_once '../includes/header.php';

// Verificar autenticación y rol
requiereAutenticacion();
requiereRol('empresa');

$errores = [];
$exito = false;
$oferta_id = null;

// Obtener datos de la empresa
$empresa = obtenerDatosEmpresa($_SESSION['empresa_id']);

// Procesar formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo_puesto = limpiarInput($_POST['titulo_puesto'] ?? '');
    $ubicacion = limpiarInput($_POST['ubicacion'] ?? '');
    $tipo_contrato = limpiarInput($_POST['tipo_contrato'] ?? '');
    $salario_min = !empty($_POST['salario_min']) ? floatval($_POST['salario_min']) : null;
    $salario_max = !empty($_POST['salario_max']) ? floatval($_POST['salario_max']) : null;
    $mostrar_salario = isset($_POST['mostrar_salario']) ? 1 : 0;
    $descripcion = limpiarInput($_POST['descripcion'] ?? '');
    $requisitos = limpiarInput($_POST['requisitos'] ?? '');
    $beneficios = limpiarInput($_POST['beneficios'] ?? '');
    $fecha_cierre = !empty($_POST['fecha_cierre']) ? $_POST['fecha_cierre'] : null;
    $categorias = $_POST['categorias'] ?? [];
    
    // Validaciones
    if (empty($titulo_puesto)) {
        $errores[] = "El título del puesto es obligatorio";
    }
    
    if (empty($ubicacion)) {
        $errores[] = "La ubicación es obligatoria";
    }
    
    if (empty($tipo_contrato)) {
        $errores[] = "El tipo de contrato es obligatorio";
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción es obligatoria";
    }
    
    if (empty($requisitos)) {
        $errores[] = "Los requisitos son obligatorios";
    }
    
    // Si no hay errores, guardar oferta
    if (empty($errores)) {
        $conn = conectarDB();
        $conn->begin_transaction();
        
        try {
            // Comprobar si estamos editando una oferta existente
            $editar_oferta = false;
            if (isset($_POST['oferta_id']) && !empty($_POST['oferta_id'])) {
                $oferta_id = (int)$_POST['oferta_id'];
                $editar_oferta = true;
                
                // Verificar que la oferta pertenezca a esta empresa
                $oferta_actual = obtenerRegistro(
                    "SELECT id FROM ofertas WHERE id = ? AND empresa_id = ?",
                    [$oferta_id, $_SESSION['empresa_id']]
                );
                
                if (!$oferta_actual) {
                    throw new Exception("No tienes permiso para editar esta oferta");
                }
            }
            
            if ($editar_oferta) {
                // Actualizar oferta existente
                $stmt = $conn->prepare("
                    UPDATE ofertas 
                    SET titulo = ?, ubicacion = ?, tipo_contrato = ?, salario_min = ?, 
                        salario_max = ?, mostrar_salario = ?, descripcion = ?, requisitos = ?, 
                        beneficios = ?, fecha_cierre = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "sssddissssi",
                    $titulo_puesto,
                    $ubicacion,
                    $tipo_contrato,
                    $salario_min,
                    $salario_max,
                    $mostrar_salario,
                    $descripcion,
                    $requisitos,
                    $beneficios,
                    $fecha_cierre,
                    $oferta_id
                );
                $stmt->execute();
                
                // Eliminar categorías actuales
                $stmt = $conn->prepare("DELETE FROM categorias_ofertas WHERE oferta_id = ?");
                $stmt->bind_param("i", $oferta_id);
                $stmt->execute();
            } else {
                // Insertar nueva oferta
                $stmt = $conn->prepare("
                    INSERT INTO ofertas 
                    (empresa_id, titulo, ubicacion, tipo_contrato, salario_min, salario_max, 
                     mostrar_salario, descripcion, requisitos, beneficios, fecha_cierre)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "isssddissss",
                    $_SESSION['empresa_id'],
                    $titulo_puesto,
                    $ubicacion,
                    $tipo_contrato,
                    $salario_min,
                    $salario_max,
                    $mostrar_salario,
                    $descripcion,
                    $requisitos,
                    $beneficios,
                    $fecha_cierre
                );
                $stmt->execute();
                
                $oferta_id = $stmt->insert_id;
            }
            
            // Insertar categorías
            if (!empty($categorias)) {
                $stmt = $conn->prepare("INSERT INTO categorias_ofertas (oferta_id, categoria) VALUES (?, ?)");
                
                foreach ($categorias as $categoria) {
                    $stmt->bind_param("is", $oferta_id, $categoria);
                    $stmt->execute();
                }
            }
            
            // Confirmar transacción
            $conn->commit();
            
            $exito = true;
            
            // Si no estamos editando, redirigir al panel
            if (!$editar_oferta) {
                header("Location: panel-empresa.php?exito=oferta_publicada");
                exit;
            }
        } catch (Exception $e) {
            // Revertir cambios si hay error
            $conn->rollback();
            $errores[] = "Error: " . $e->getMessage();
        }
        
        $conn->close();
    }
}

// Si estamos editando, cargar los datos de la oferta
$oferta = null;
$categorias_seleccionadas = [];

if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $oferta_id = (int)$_GET['editar'];
    
    // Verificar que la oferta pertenezca a esta empresa
    $oferta = obtenerRegistro(
        "SELECT * FROM ofertas WHERE id = ? AND empresa_id = ?",
        [$oferta_id, $_SESSION['empresa_id']]
    );
    
    if ($oferta) {
        // Cargar categorías
        $categorias = obtenerRegistros(
            "SELECT categoria FROM categorias_ofertas WHERE oferta_id = ?",
            [$oferta_id]
        );
        
        foreach ($categorias as $cat) {
            $categorias_seleccionadas[] = $cat['categoria'];
        }
    } else {
        // Si no es una oferta válida, redirigir al panel
        header("Location: panel-empresa.php");
        exit;
    }
}
?>

<div class="container">
    <div style="margin: 30px 0;">
        <h1 style="color: var(--primary-color);"><?php echo $oferta ? 'Editar Oferta de Empleo' : 'Publicar Oferta de Empleo'; ?></h1>
    </div>
    
    <?php if (!empty($errores)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($exito): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <p style="margin: 0;">La oferta se ha <?php echo $oferta ? 'actualizado' : 'publicado'; ?> correctamente.</p>
        </div>
    <?php endif; ?>
    
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
                <li><a href="panel-empresa.php">Dashboard</a></li>
                <li><a href="#" class="active">Publicar Oferta</a></li>
                <li><a href="mis-ofertas.php">Mis Ofertas</a></li>
                <li><a href="candidatos.php">Candidatos</a></li>
                <li><a href="perfil-empresa.php">Perfil de Empresa</a></li>
                <li><a href="configuracion.php">Configuración</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <form id="publish-job-form" action="publicar-oferta.php" method="post">
                <?php if ($oferta): ?>
                    <input type="hidden" name="oferta_id" value="<?php echo $oferta_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="titulo_puesto">Título del Puesto</label>
                    <input type="text" class="form-control" id="titulo_puesto" name="titulo_puesto" value="<?php echo $oferta ? htmlspecialchars($oferta['titulo']) : (isset($titulo_puesto) ? htmlspecialchars($titulo_puesto) : ''); ?>" required>
                </div>
                
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?php echo $oferta ? htmlspecialchars($oferta['ubicacion']) : (isset($ubicacion) ? htmlspecialchars($ubicacion) : ''); ?>" required>
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="tipo_contrato">Tipo de Contrato</label>
                        <select class="form-control" id="tipo_contrato" name="tipo_contrato" required>
                            <option value="">Seleccionar...</option>
                            <option value="tiempo_completo" <?php echo ($oferta && $oferta['tipo_contrato'] === 'tiempo_completo') || (isset($tipo_contrato) && $tipo_contrato === 'tiempo_completo') ? 'selected' : ''; ?>>Tiempo Completo</option>
                            <option value="medio_tiempo" <?php echo ($oferta && $oferta['tipo_contrato'] === 'medio_tiempo') || (isset($tipo_contrato) && $tipo_contrato === 'medio_tiempo') ? 'selected' : ''; ?>>Medio Tiempo</option>
                            <option value="temporal" <?php echo ($oferta && $oferta['tipo_contrato'] === 'temporal') || (isset($tipo_contrato) && $tipo_contrato === 'temporal') ? 'selected' : ''; ?>>Temporal</option>
                            <option value="proyecto" <?php echo ($oferta && $oferta['tipo_contrato'] === 'proyecto') || (isset($tipo_contrato) && $tipo_contrato === 'proyecto') ? 'selected' : ''; ?>>Por Proyecto</option>
                            <option value="practicas" <?php echo ($oferta && $oferta['tipo_contrato'] === 'practicas') || (isset($tipo_contrato) && $tipo_contrato === 'practicas') ? 'selected' : ''; ?>>Prácticas Profesionales</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="salario_min">Salario Mínimo</label>
                        <input type="number" class="form-control" id="salario_min" name="salario_min" value="<?php echo $oferta ? $oferta['salario_min'] : (isset($salario_min) ? $salario_min : ''); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="salario_max">Salario Máximo</label>
                        <input type="number" class="form-control" id="salario_max" name="salario_max" value="<?php echo $oferta ? $oferta['salario_max'] : (isset($salario_max) ? $salario_max : ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="mostrar_salario">Mostrar Salario en la Oferta</label>
                    <div>
                        <input type="checkbox" id="mostrar_salario" name="mostrar_salario" <?php echo (($oferta && $oferta['mostrar_salario']) || (isset($mostrar_salario) && $mostrar_salario)) ? 'checked' : ''; ?>>
                        <label for="mostrar_salario">Sí, mostrar el rango salarial</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción del Puesto</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php echo $oferta ? htmlspecialchars($oferta['descripcion']) : (isset($descripcion) ? htmlspecialchars($descripcion) : ''); ?></textarea>
                    <small class="form-text">Describe las responsabilidades, el día a día y lo que se espera del candidato.</small>
                </div>
                
                <div class="form-group">
                    <label for="requisitos">Requisitos</label>
                    <textarea class="form-control" id="requisitos" name="requisitos" rows="5" required><?php echo $oferta ? htmlspecialchars($oferta['requisitos']) : (isset($requisitos) ? htmlspecialchars($requisitos) : ''); ?></textarea>
                    <small class="form-text">Incluye formación académica, experiencia, habilidades técnicas y blandas necesarias.</small>
                </div>
                
                <div class="form-group">
                    <label for="beneficios">Beneficios (opcional)</label>
                    <textarea class="form-control" id="beneficios" name="beneficios" rows="3"><?php echo $oferta ? htmlspecialchars($oferta['beneficios']) : (isset($beneficios) ? htmlspecialchars($beneficios) : ''); ?></textarea>
                    <small class="form-text">Menciona los beneficios adicionales como seguro médico, horario flexible, etc.</small>
                </div>
                
                <div class="form-group">
                    <label>Categorías</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                        <div>
                            <input type="checkbox" id="cat_tecnologia" name="categorias[]" value="tecnologia" <?php echo (($oferta && in_array('tecnologia', $categorias_seleccionadas)) || (isset($categorias) && in_array('tecnologia', $categorias))) ? 'checked' : ''; ?>>
                            <label for="cat_tecnologia">Tecnología</label>
                        </div>
                        <div>
                            <input type="checkbox" id="cat_marketing" name="categorias[]" value="marketing" <?php echo (($oferta && in_array('marketing', $categorias_seleccionadas)) || (isset($categorias) && in_array('marketing', $categorias))) ? 'checked' : ''; ?>>
                            <label for="cat_marketing">Marketing</label>
                        </div>
                        <div>
                            <input type="checkbox" id="cat_ventas" name="categorias[]" value="ventas" <?php echo (($oferta && in_array('ventas', $categorias_seleccionadas)) || (isset($categorias) && in_array('ventas', $categorias))) ? 'checked' : ''; ?>>
                            <label for="cat_ventas">Ventas</label>
                        </div>
                        <div>
                            <input type="checkbox" id="cat_finanzas" name="categorias[]" value="finanzas" <?php echo (($oferta && in_array('finanzas', $categorias_seleccionadas)) || (isset($categorias) && in_array('finanzas', $categorias))) ? 'checked' : ''; ?>>
                            <label for="cat_finanzas">Finanzas</label>
                        </div>
                        <div>
                            <input type="checkbox" id="cat_rrhh" name="categorias[]" value="rrhh" <?php echo (($oferta && in_array('rrhh', $categorias_seleccionadas)) || (isset($categorias) && in_array('rrhh', $categorias))) ? 'checked' : ''; ?>>
                            <label for="cat_rrhh">RRHH</label>
                        </div>
                        <div>
                            <input type="checkbox" id="cat_diseno" name="categorias[]" value="diseno" <?php echo (($oferta && in_array('diseno', $categorias_seleccionadas)) || (isset($categorias) && in_array('diseno', $categorias))) ? 'checked' : ''; ?>>
                            <label for="cat_diseno">Diseño</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fecha_cierre">Fecha de Cierre de la Oferta</label>
                    <input type="date" class="form-control" id="fecha_cierre" name="fecha_cierre" value="<?php echo $oferta ? $oferta['fecha_cierre'] : (isset($fecha_cierre) ? $fecha_cierre : ''); ?>">
                </div>
                
                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn btn-primary" style="width: 200px;"><?php echo $oferta ? 'Actualizar Oferta' : 'Publicar Oferta'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>