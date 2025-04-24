<?php
$titulo_pagina = 'Mi Currículum';
require_once '../includes/header.php';

// Verificar autenticación y rol
requiereAutenticacion();
requiereRol('candidato');

$errores = [];
$exito = false;

// Obtener datos del candidato
$candidato = obtenerDatosCandidato($_SESSION['candidato_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos personales
    $objetivo_profesional = limpiarInput($_POST['objetivo_profesional'] ?? '');
    $disponibilidad = limpiarInput($_POST['disponibilidad'] ?? '');
    
    // Iniciar transacción
    $conn = conectarDB();
    $conn->begin_transaction();
    
    try {
        // Actualizar datos personales
        $stmt = $conn->prepare("UPDATE candidatos SET objetivo_profesional = ?, disponibilidad = ? WHERE id = ?");
        $stmt->bind_param("ssi", $objetivo_profesional, $disponibilidad, $_SESSION['candidato_id']);
        $stmt->execute();
        
        // Subir foto si se proporciona
        if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
            $foto = subirArchivo($_FILES['foto'], PHOTOS_PATH, ['jpg', 'jpeg', 'png'], 'candidato');
            if ($foto) {
                $stmt = $conn->prepare("UPDATE candidatos SET foto = ? WHERE id = ?");
                $stmt->bind_param("si", $foto, $_SESSION['candidato_id']);
                $stmt->execute();
            }
        }
        
        // Subir CV PDF si se proporciona
        if (isset($_FILES['cv_pdf']) && $_FILES['cv_pdf']['size'] > 0) {
            $cv_pdf = subirArchivo($_FILES['cv_pdf'], CVS_PATH, ['pdf'], 'cv');
            if ($cv_pdf) {
                $stmt = $conn->prepare("UPDATE candidatos SET cv_pdf = ? WHERE id = ?");
                $stmt->bind_param("si", $cv_pdf, $_SESSION['candidato_id']);
                $stmt->execute();
            }
        }
        
        // Procesar formación académica
        if (isset($_POST['institucion']) && !empty($_POST['institucion'])) {
            $institucion = limpiarInput($_POST['institucion']);
            $titulo = limpiarInput($_POST['titulo']);
            $fecha_inicio = $_POST['fecha_inicio_educacion'];
            $fecha_fin = isset($_POST['actual_educacion']) ? null : $_POST['fecha_fin_educacion'];
            $actual = isset($_POST['actual_educacion']) ? 1 : 0;
            
            if (isset($_POST['formacion_id']) && !empty($_POST['formacion_id'])) {
                // Actualizar formación existente
                $stmt = $conn->prepare("UPDATE formacion_academica SET institucion = ?, titulo = ?, fecha_inicio = ?, fecha_fin = ?, actual = ? WHERE id = ? AND candidato_id = ?");
                $stmt->bind_param("ssssiis", $institucion, $titulo, $fecha_inicio, $fecha_fin, $actual, $_POST['formacion_id'], $_SESSION['candidato_id']);
            } else {
                // Insertar nueva formación
                $stmt = $conn->prepare("INSERT INTO formacion_academica (candidato_id, institucion, titulo, fecha_inicio, fecha_fin, actual) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssi", $_SESSION['candidato_id'], $institucion, $titulo, $fecha_inicio, $fecha_fin, $actual);
            }
            $stmt->execute();
        }
        
        // Procesar experiencia laboral
        if (isset($_POST['empresa']) && !empty($_POST['empresa'])) {
            $empresa = limpiarInput($_POST['empresa']);
            $puesto = limpiarInput($_POST['puesto']);
            $fecha_inicio = $_POST['fecha_inicio_experiencia'];
            $fecha_fin = isset($_POST['actual_experiencia']) ? null : $_POST['fecha_fin_experiencia'];
            $actual = isset($_POST['actual_experiencia']) ? 1 : 0;
            $descripcion = limpiarInput($_POST['descripcion_experiencia']);
            
            if (isset($_POST['experiencia_id']) && !empty($_POST['experiencia_id'])) {
                // Actualizar experiencia existente
                $stmt = $conn->prepare("UPDATE experiencia_laboral SET empresa = ?, puesto = ?, fecha_inicio = ?, fecha_fin = ?, actual = ?, descripcion = ? WHERE id = ? AND candidato_id = ?");
                $stmt->bind_param("ssssissi", $empresa, $puesto, $fecha_inicio, $fecha_fin, $actual, $descripcion, $_POST['experiencia_id'], $_SESSION['candidato_id']);
            } else {
                // Insertar nueva experiencia
                $stmt = $conn->prepare("INSERT INTO experiencia_laboral (candidato_id, empresa, puesto, fecha_inicio, fecha_fin, actual, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssi", $_SESSION['candidato_id'], $empresa, $puesto, $fecha_inicio, $fecha_fin, $actual, $descripcion);
            }
            $stmt->execute();
        }
        
        // Procesar habilidades
        if (isset($_POST['habilidad']) && !empty($_POST['habilidad'])) {
            $habilidad = limpiarInput($_POST['habilidad']);
            $nivel = limpiarInput($_POST['nivel_habilidad']);
            
            if (isset($_POST['habilidad_id']) && !empty($_POST['habilidad_id'])) {
                // Actualizar habilidad existente
                $stmt = $conn->prepare("UPDATE habilidades SET habilidad = ?, nivel = ? WHERE id = ? AND candidato_id = ?");
                $stmt->bind_param("ssii", $habilidad, $nivel, $_POST['habilidad_id'], $_SESSION['candidato_id']);
            } else {
                // Insertar nueva habilidad
                $stmt = $conn->prepare("INSERT INTO habilidades (candidato_id, habilidad, nivel) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $_SESSION['candidato_id'], $habilidad, $nivel);
            }
            $stmt->execute();
        }
        
        // Procesar idiomas
        if (isset($_POST['idioma']) && !empty($_POST['idioma'])) {
            $idioma = limpiarInput($_POST['idioma']);
            $nivel = limpiarInput($_POST['nivel_idioma']);
            
            if (isset($_POST['idioma_id']) && !empty($_POST['idioma_id'])) {
                // Actualizar idioma existente
                $stmt = $conn->prepare("UPDATE idiomas SET idioma = ?, nivel = ? WHERE id = ? AND candidato_id = ?");
                $stmt->bind_param("ssii", $idioma, $nivel, $_POST['idioma_id'], $_SESSION['candidato_id']);
            } else {
                // Insertar nuevo idioma
                $stmt = $conn->prepare("INSERT INTO idiomas (candidato_id, idioma, nivel) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $_SESSION['candidato_id'], $idioma, $nivel);
            }
            $stmt->execute();
        }
        
        // Procesar logros
        $logros = limpiarInput($_POST['logros_proyectos'] ?? '');
        $logro_actual = obtenerRegistro("SELECT id FROM logros_proyectos WHERE candidato_id = ?", [$_SESSION['candidato_id']]);
        
        if ($logro_actual) {
            // Actualizar logros existentes
            $stmt = $conn->prepare("UPDATE logros_proyectos SET descripcion = ? WHERE candidato_id = ?");
            $stmt->bind_param("si", $logros, $_SESSION['candidato_id']);
        } else {
            // Insertar nuevos logros
            $stmt = $conn->prepare("INSERT INTO logros_proyectos (candidato_id, descripcion) VALUES (?, ?)");
            $stmt->bind_param("is", $_SESSION['candidato_id'], $logros);
        }
        $stmt->execute();
        
        // Procesar redes profesionales
        if (isset($_POST['linkedin']) && !empty($_POST['linkedin'])) {
            $linkedin = limpiarInput($_POST['linkedin']);
            $red_actual = obtenerRegistro("SELECT id FROM redes_profesionales WHERE candidato_id = ? AND tipo = 'linkedin'", [$_SESSION['candidato_id']]);
            
            if ($red_actual) {
                // Actualizar red existente
                $stmt = $conn->prepare("UPDATE redes_profesionales SET url = ? WHERE id = ?");
                $stmt->bind_param("si", $linkedin, $red_actual['id']);
            } else {
                // Insertar nueva red
                $stmt = $conn->prepare("INSERT INTO redes_profesionales (candidato_id, tipo, url) VALUES (?, 'linkedin', ?)");
                $stmt->bind_param("is", $_SESSION['candidato_id'], $linkedin);
            }
            $stmt->execute();
        }
        
        if (isset($_POST['portfolio']) && !empty($_POST['portfolio'])) {
            $portfolio = limpiarInput($_POST['portfolio']);
            $red_actual = obtenerRegistro("SELECT id FROM redes_profesionales WHERE candidato_id = ? AND tipo = 'portfolio'", [$_SESSION['candidato_id']]);
            
            if ($red_actual) {
                // Actualizar red existente
                $stmt = $conn->prepare("UPDATE redes_profesionales SET url = ? WHERE id = ?");
                $stmt->bind_param("si", $portfolio, $red_actual['id']);
            } else {
                // Insertar nueva red
                $stmt = $conn->prepare("INSERT INTO redes_profesionales (candidato_id, tipo, url) VALUES (?, 'portfolio', ?)");
                $stmt->bind_param("is", $_SESSION['candidato_id'], $portfolio);
            }
            $stmt->execute();
        }
        
        // Procesar referencias
        if (isset($_POST['nombre_referencia']) && !empty($_POST['nombre_referencia'])) {
            $nombre = limpiarInput($_POST['nombre_referencia']);
            $empresa = limpiarInput($_POST['empresa_referencia'] ?? '');
            $telefono = limpiarInput($_POST['telefono_referencia'] ?? '');
            $email = limpiarInput($_POST['email_referencia'] ?? '');
            
            if (isset($_POST['referencia_id']) && !empty($_POST['referencia_id'])) {
                // Actualizar referencia existente
                $stmt = $conn->prepare("UPDATE referencias SET nombre = ?, empresa = ?, telefono = ?, email = ? WHERE id = ? AND candidato_id = ?");
                $stmt->bind_param("ssssii", $nombre, $empresa, $telefono, $email, $_POST['referencia_id'], $_SESSION['candidato_id']);
            } else {
                // Insertar nueva referencia
                $stmt = $conn->prepare("INSERT INTO referencias (candidato_id, nombre, empresa, telefono, email) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $_SESSION['candidato_id'], $nombre, $empresa, $telefono, $email);
            }
            $stmt->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        
        // Actualizar datos en memoria
        $candidato = obtenerDatosCandidato($_SESSION['candidato_id']);
        
        $exito = true;
    } catch (Exception $e) {
        // Revertir cambios si hay error
        $conn->rollback();
        $errores[] = "Error al guardar: " . $e->getMessage();
    }
    
    $conn->close();
}
?>

<div class="container">
    <div style="margin: 30px 0;">
        <h1 style="color: var(--primary-color);">Mi Currículum</h1>
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
            <p style="margin: 0;">Los cambios se han guardado correctamente.</p>
        </div>
    <?php endif; ?>
    
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
                <li><a href="panel-candidato.php">Dashboard</a></li>
                <li><a href="#" class="active">Mi CV</a></li>
                <li><a href="ofertas.php">Buscar Ofertas</a></li>
                <li><a href="mis-postulaciones.php">Mis Postulaciones</a></li>
                <li><a href="configuracion.php">Configuración</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <form id="cv-form" action="cv-form.php" method="post" enctype="multipart/form-data">
                <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 30px;">
                    <div style="flex: 0 0 200px;">
                        <?php if (!empty($candidato['foto'])): ?>
                            <img src="<?php echo SITE_URL . '/uploads/photos/' . $candidato['foto']; ?>" alt="Foto de perfil" style="width: 100%; border-radius: 10px;">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/200" alt="Foto de perfil" style="width: 100%; border-radius: 10px;">
                        <?php endif; ?>
                        <div style="margin-top: 10px;">
                            <input type="file" id="foto" name="foto" accept="image/*">
                        </div>
                    </div>
                    
                    <div style="flex: 1; min-width: 300px;">
                        <div class="form-group">
                            <label for="objetivo_profesional">Objetivo Profesional</label>
                            <textarea class="form-control" id="objetivo_profesional" name="objetivo_profesional" rows="4" required><?php echo htmlspecialchars($candidato['objetivo_profesional'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="disponibilidad">Disponibilidad</label>
                            <select class="form-control" id="disponibilidad" name="disponibilidad" required>
                                <option value="">Seleccionar...</option>
                                <option value="inmediata" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === 'inmediata') ? 'selected' : ''; ?>>Inmediata</option>
                                <option value="15_dias" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === '15_dias') ? 'selected' : ''; ?>>En 15 días</option>
                                <option value="30_dias" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === '30_dias') ? 'selected' : ''; ?>>En 30 días</option>
                                <option value="negociable" <?php echo (isset($candidato['disponibilidad']) && $candidato['disponibilidad'] === 'negociable') ? 'selected' : ''; ?>>Negociable</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Formación Académica</h3>
                
                <div id="formacion-container">
                    <?php if (!empty($candidato['formacion'])): ?>
                        <?php foreach ($candidato['formacion'] as $index => $formacion): ?>
                            <div class="card formacion-item" data-id="<?php echo $index; ?>">
                                <input type="hidden" name="formacion_id" value="<?php echo $formacion['id']; ?>">
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="institucion_<?php echo $index; ?>">Institución</label>
                                        <input type="text" class="form-control" id="institucion_<?php echo $index; ?>" name="institucion" value="<?php echo htmlspecialchars($formacion['institucion']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="titulo_<?php echo $index; ?>">Título</label>
                                        <input type="text" class="form-control" id="titulo_<?php echo $index; ?>" name="titulo" value="<?php echo htmlspecialchars($formacion['titulo']); ?>" required>
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="fecha_inicio_educacion_<?php echo $index; ?>">Fecha de Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio_educacion_<?php echo $index; ?>" name="fecha_inicio_educacion" value="<?php echo $formacion['fecha_inicio']; ?>" required>
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="fecha_fin_educacion_<?php echo $index; ?>">Fecha de Fin</label>
                                        <input type="date" class="form-control" id="fecha_fin_educacion_<?php echo $index; ?>" name="fecha_fin_educacion" value="<?php echo $formacion['fecha_fin']; ?>" <?php echo $formacion['actual'] ? 'disabled' : ''; ?>>
                                        <div style="margin-top: 10px;">
                                            <input type="checkbox" id="actual_educacion_<?php echo $index; ?>" name="actual_educacion" <?php echo $formacion['actual'] ? 'checked' : ''; ?>>
                                            <label for="actual_educacion_<?php echo $index; ?>">Actualmente cursando</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; margin-top: 10px;">
                                    <button type="button" class="btn btn-outline btn-eliminar-formacion" data-id="<?php echo $formacion['id']; ?>">Eliminar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card formacion-item" data-id="0">
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="institucion">Institución</label>
                                    <input type="text" class="form-control" id="institucion" name="institucion" required>
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="titulo">Título</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" required>
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="fecha_inicio_educacion">Fecha de Inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio_educacion" name="fecha_inicio_educacion" required>
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="fecha_fin_educacion">Fecha de Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin_educacion" name="fecha_fin_educacion">
                                    <div style="margin-top: 10px;">
                                        <input type="checkbox" id="actual_educacion" name="actual_educacion">
                                        <label for="actual_educacion">Actualmente cursando</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: right; margin: 10px 0 30px;">
                    <button type="button" id="agregar-formacion" class="btn btn-outline">+ Agregar otra formación</button>
                </div>
                
                <!-- Aquí seguiría el resto del formulario con experiencia laboral, habilidades, idiomas, etc. -->
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Experiencia Laboral</h3>
                
                <div id="experiencia-container">
                    <?php if (!empty($candidato['experiencia'])): ?>
                        <?php foreach ($candidato['experiencia'] as $index => $experiencia): ?>
                            <div class="card experiencia-item" data-id="<?php echo $index; ?>">
                                <input type="hidden" name="experiencia_id" value="<?php echo $experiencia['id']; ?>">
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="empresa_<?php echo $index; ?>">Empresa</label>
                                        <input type="text" class="form-control" id="empresa_<?php echo $index; ?>" name="empresa" value="<?php echo htmlspecialchars($experiencia['empresa']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="puesto_<?php echo $index; ?>">Puesto</label>
                                        <input type="text" class="form-control" id="puesto_<?php echo $index; ?>" name="puesto" value="<?php echo htmlspecialchars($experiencia['puesto']); ?>" required>
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="fecha_inicio_experiencia_<?php echo $index; ?>">Fecha de Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio_experiencia_<?php echo $index; ?>" name="fecha_inicio_experiencia" value="<?php echo $experiencia['fecha_inicio']; ?>" required>
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="fecha_fin_experiencia_<?php echo $index; ?>">Fecha de Fin</label>
                                        <input type="date" class="form-control" id="fecha_fin_experiencia_<?php echo $index; ?>" name="fecha_fin_experiencia" value="<?php echo $experiencia['fecha_fin']; ?>" <?php echo $experiencia['actual'] ? 'disabled' : ''; ?>>
                                        <div style="margin-top: 10px;">
                                            <input type="checkbox" id="actual_experiencia_<?php echo $index; ?>" name="actual_experiencia" <?php echo $experiencia['actual'] ? 'checked' : ''; ?>>
                                            <label for="actual_experiencia_<?php echo $index; ?>">Trabajo actual</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="descripcion_experiencia_<?php echo $index; ?>">Descripción</label>
                                    <textarea class="form-control" id="descripcion_experiencia_<?php echo $index; ?>" name="descripcion_experiencia" rows="3" required><?php echo htmlspecialchars($experiencia['descripcion']); ?></textarea>
                                </div>
                                
                                <div style="text-align: right; margin-top: 10px;">
                                    <button type="button" class="btn btn-outline btn-eliminar-experiencia" data-id="<?php echo $experiencia['id']; ?>">Eliminar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card experiencia-item" data-id="0">
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="empresa">Empresa</label>
                                    <input type="text" class="form-control" id="empresa" name="empresa" required>
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="puesto">Puesto</label>
                                    <input type="text" class="form-control" id="puesto" name="puesto" required>
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="fecha_inicio_experiencia">Fecha de Inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio_experiencia" name="fecha_inicio_experiencia" required>
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="fecha_fin_experiencia">Fecha de Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin_experiencia" name="fecha_fin_experiencia">
                                    <div style="margin-top: 10px;">
                                        <input type="checkbox" id="actual_experiencia" name="actual_experiencia">
                                        <label for="actual_experiencia">Trabajo actual</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="descripcion_experiencia">Descripción</label>
                                <textarea class="form-control" id="descripcion_experiencia" name="descripcion_experiencia" rows="3" required></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: right; margin: 10px 0 30px;">
                    <button type="button" id="agregar-experiencia" class="btn btn-outline">+ Agregar otra experiencia</button>
                </div>
                
                <!-- Seguimos con el resto de secciones del CV -->
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Habilidades</h3>
                
                <div id="habilidades-container">
                    <?php if (!empty($candidato['habilidades'])): ?>
                        <?php foreach ($candidato['habilidades'] as $index => $habilidad): ?>
                            <div class="card habilidad-item" data-id="<?php echo $index; ?>">
                                <input type="hidden" name="habilidad_id" value="<?php echo $habilidad['id']; ?>">
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="habilidad_<?php echo $index; ?>">Habilidad</label>
                                        <input type="text" class="form-control" id="habilidad_<?php echo $index; ?>" name="habilidad" value="<?php echo htmlspecialchars($habilidad['habilidad']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="nivel_habilidad_<?php echo $index; ?>">Nivel</label>
                                        <select class="form-control" id="nivel_habilidad_<?php echo $index; ?>" name="nivel_habilidad" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="principiante" <?php echo ($habilidad['nivel'] === 'principiante') ? 'selected' : ''; ?>>Principiante</option>
                                            <option value="intermedio" <?php echo ($habilidad['nivel'] === 'intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                                            <option value="avanzado" <?php echo ($habilidad['nivel'] === 'avanzado') ? 'selected' : ''; ?>>Avanzado</option>
                                            <option value="experto" <?php echo ($habilidad['nivel'] === 'experto') ? 'selected' : ''; ?>>Experto</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; margin-top: 10px;">
                                    <button type="button" class="btn btn-outline btn-eliminar-habilidad" data-id="<?php echo $habilidad['id']; ?>">Eliminar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card habilidad-item" data-id="0">
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="habilidad">Habilidad</label>
                                    <input type="text" class="form-control" id="habilidad" name="habilidad" required>
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="nivel_habilidad">Nivel</label>
                                    <select class="form-control" id="nivel_habilidad" name="nivel_habilidad" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="principiante">Principiante</option>
                                        <option value="intermedio">Intermedio</option>
                                        <option value="avanzado">Avanzado</option>
                                        <option value="experto">Experto</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: right; margin: 10px 0 30px;">
                    <button type="button" id="agregar-habilidad" class="btn btn-outline">+ Agregar otra habilidad</button>
                </div>
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Idiomas</h3>
                
                <div id="idiomas-container">
                    <?php if (!empty($candidato['idiomas'])): ?>
                        <?php foreach ($candidato['idiomas'] as $index => $idioma): ?>
                            <div class="card idioma-item" data-id="<?php echo $index; ?>">
                                <input type="hidden" name="idioma_id" value="<?php echo $idioma['id']; ?>">
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="idioma_<?php echo $index; ?>">Idioma</label>
                                        <input type="text" class="form-control" id="idioma_<?php echo $index; ?>" name="idioma" value="<?php echo htmlspecialchars($idioma['idioma']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="nivel_idioma_<?php echo $index; ?>">Nivel</label>
                                        <select class="form-control" id="nivel_idioma_<?php echo $index; ?>" name="nivel_idioma" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="basico" <?php echo ($idioma['nivel'] === 'basico') ? 'selected' : ''; ?>>Básico</option>
                                            <option value="intermedio" <?php echo ($idioma['nivel'] === 'intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                                            <option value="avanzado" <?php echo ($idioma['nivel'] === 'avanzado') ? 'selected' : ''; ?>>Avanzado</option>
                                            <option value="nativo" <?php echo ($idioma['nivel'] === 'nativo') ? 'selected' : ''; ?>>Nativo</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; margin-top: 10px;">
                                    <button type="button" class="btn btn-outline btn-eliminar-idioma" data-id="<?php echo $idioma['id']; ?>">Eliminar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card idioma-item" data-id="0">
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="idioma">Idioma</label>
                                    <input type="text" class="form-control" id="idioma" name="idioma" required>
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="nivel_idioma">Nivel</label>
                                    <select class="form-control" id="nivel_idioma" name="nivel_idioma" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="basico">Básico</option>
                                        <option value="intermedio">Intermedio</option>
                                        <option value="avanzado">Avanzado</option>
                                        <option value="nativo">Nativo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: right; margin: 10px 0 30px;">
                    <button type="button" id="agregar-idioma" class="btn btn-outline">+ Agregar otro idioma</button>
                </div>
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Logros y Proyectos</h3>
                
                <div class="form-group">
                    <textarea class="form-control" id="logros_proyectos" name="logros_proyectos" rows="4"><?php echo htmlspecialchars($candidato['logros'] ?? ''); ?></textarea>
                </div>
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Redes Profesionales</h3>
                
                <?php
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
                
                <div class="form-group">
                    <label for="linkedin">LinkedIn</label>
                    <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://www.linkedin.com/in/tu-perfil" value="<?php echo htmlspecialchars($linkedin); ?>">
                </div>
                
                <div class="form-group">
                    <label for="portfolio">Sitio Web / Portfolio</label>
                    <input type="url" class="form-control" id="portfolio" name="portfolio" placeholder="https://www.tuportfolio.com" value="<?php echo htmlspecialchars($portfolio); ?>">
                </div>
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">Referencias</h3>
                
                <div id="referencias-container">
                    <?php if (!empty($candidato['referencias'])): ?>
                        <?php foreach ($candidato['referencias'] as $index => $referencia): ?>
                            <div class="card referencia-item" data-id="<?php echo $index; ?>">
                                <input type="hidden" name="referencia_id" value="<?php echo $referencia['id']; ?>">
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="nombre_referencia_<?php echo $index; ?>">Nombre del Contacto</label>
                                        <input type="text" class="form-control" id="nombre_referencia_<?php echo $index; ?>" name="nombre_referencia" value="<?php echo htmlspecialchars($referencia['nombre']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="empresa_referencia_<?php echo $index; ?>">Empresa</label>
                                        <input type="text" class="form-control" id="empresa_referencia_<?php echo $index; ?>" name="empresa_referencia" value="<?php echo htmlspecialchars($referencia['empresa']); ?>">
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="telefono_referencia_<?php echo $index; ?>">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono_referencia_<?php echo $index; ?>" name="telefono_referencia" value="<?php echo htmlspecialchars($referencia['telefono']); ?>">
                                    </div>
                                    
                                    <div class="form-group" style="flex: 1; min-width: 250px;">
                                        <label for="email_referencia_<?php echo $index; ?>">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="email_referencia_<?php echo $index; ?>" name="email_referencia" value="<?php echo htmlspecialchars($referencia['email']); ?>">
                                    </div>
                                </div>
                                
                                <div style="text-align: right; margin-top: 10px;">
                                    <button type="button" class="btn btn-outline btn-eliminar-referencia" data-id="<?php echo $referencia['id']; ?>">Eliminar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card referencia-item" data-id="0">
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="nombre_referencia">Nombre del Contacto</label>
                                    <input type="text" class="form-control" id="nombre_referencia" name="nombre_referencia">
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="empresa_referencia">Empresa</label>
                                    <input type="text" class="form-control" id="empresa_referencia" name="empresa_referencia">
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="telefono_referencia">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono_referencia" name="telefono_referencia">
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 250px;">
                                    <label for="email_referencia">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email_referencia" name="email_referencia">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: right; margin: 10px 0 30px;">
                    <button type="button" id="agregar-referencia" class="btn btn-outline">+ Agregar otra referencia</button>
                </div>
                
                <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--light-gray); padding-bottom: 10px;">CV en PDF</h3>
                
                <div class="form-group">
                    <label for="cv_pdf">Adjuntar CV en PDF (opcional)</label>
                    <input type="file" class="form-control" id="cv_pdf" name="cv_pdf" accept=".pdf">
                    <?php if (!empty($candidato['cv_pdf'])): ?>
                        <p style="margin-top: 10px;">
                            CV actual: <a href="<?php echo SITE_URL . '/uploads/cvs/' . $candidato['cv_pdf']; ?>" target="_blank">Ver CV</a>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn btn-primary" style="width: 200px;">Guardar CV</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle para los checkboxes de "actual"
        const checkboxActualEducacion = document.querySelectorAll('[id^=actual_educacion]');
        checkboxActualEducacion.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const fechaFinInput = this.closest('.form-group').querySelector('[id^=fecha_fin_educacion]');
                fechaFinInput.disabled = this.checked;
                if (this.checked) {
                    fechaFinInput.value = '';
                }
            });
        });
        
        const checkboxActualExperiencia = document.querySelectorAll('[id^=actual_experiencia]');
        checkboxActualExperiencia.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const fechaFinInput = this.closest('.form-group').querySelector('[id^=fecha_fin_experiencia]');
                fechaFinInput.disabled = this.checked;
                if (this.checked) {
                    fechaFinInput.value = '';
                }
            });
        });
        
        // Agregar formación
        document.getElementById('agregar-formacion').addEventListener('click', function() {
            const container = document.getElementById('formacion-container');
            const index = container.querySelectorAll('.formacion-item').length;
            
            const template = `
                <div class="card formacion-item" data-id="${index}">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="institucion_${index}">Institución</label>
                            <input type="text" class="form-control" id="institucion_${index}" name="institucion" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="titulo_${index}">Título</label>
                            <input type="text" class="form-control" id="titulo_${index}" name="titulo" required>
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="fecha_inicio_educacion_${index}">Fecha de Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio_educacion_${index}" name="fecha_inicio_educacion" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="fecha_fin_educacion_${index}">Fecha de Fin</label>
                            <input type="date" class="form-control" id="fecha_fin_educacion_${index}" name="fecha_fin_educacion">
                            <div style="margin-top: 10px;">
                                <input type="checkbox" id="actual_educacion_${index}" name="actual_educacion">
                                <label for="actual_educacion_${index}">Actualmente cursando</label>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="btn btn-outline btn-eliminar-formacion">Eliminar</button>
                    </div>
                </div>
            `;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = template;
            container.appendChild(tempDiv.firstElementChild);
            
            // Agregar event listener para el nuevo checkbox
            const newCheckbox = container.querySelector(`#actual_educacion_${index}`);
            newCheckbox.addEventListener('change', function() {
                const fechaFinInput = this.closest('.form-group').querySelector(`#fecha_fin_educacion_${index}`);
                fechaFinInput.disabled = this.checked;
                if (this.checked) {
                    fechaFinInput.value = '';
                }
            });
            
            // Agregar event listener para el botón eliminar
            const newButton = container.querySelector(`.formacion-item[data-id="${index}"] .btn-eliminar-formacion`);
            newButton.addEventListener('click', function() {
                this.closest('.formacion-item').remove();
            });
        });
        
        // Agregar experiencia
        document.getElementById('agregar-experiencia').addEventListener('click', function() {
            const container = document.getElementById('experiencia-container');
            const index = container.querySelectorAll('.experiencia-item').length;
            
            const template = `
                <div class="card experiencia-item" data-id="${index}">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="empresa_${index}">Empresa</label>
                            <input type="text" class="form-control" id="empresa_${index}" name="empresa" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="puesto_${index}">Puesto</label>
                            <input type="text" class="form-control" id="puesto_${index}" name="puesto" required>
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="fecha_inicio_experiencia_${index}">Fecha de Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio_experiencia_${index}" name="fecha_inicio_experiencia" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="fecha_fin_experiencia_${index}">Fecha de Fin</label>
                            <input type="date" class="form-control" id="fecha_fin_experiencia_${index}" name="fecha_fin_experiencia">
                            <div style="margin-top: 10px;">
                                <input type="checkbox" id="actual_experiencia_${index}" name="actual_experiencia">
                                <label for="actual_experiencia_${index}">Trabajo actual</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion_experiencia_${index}">Descripción</label>
                        <textarea class="form-control" id="descripcion_experiencia_${index}" name="descripcion_experiencia" rows="3" required></textarea>
                    </div>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="btn btn-outline btn-eliminar-experiencia">Eliminar</button>
                    </div>
                </div>
            `;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = template;
            container.appendChild(tempDiv.firstElementChild);
            
            // Agregar event listener para el nuevo checkbox
            const newCheckbox = container.querySelector(`#actual_experiencia_${index}`);
            newCheckbox.addEventListener('change', function() {
                const fechaFinInput = this.closest('.form-group').querySelector(`#fecha_fin_experiencia_${index}`);
                fechaFinInput.disabled = this.checked;
                if (this.checked) {
                    fechaFinInput.value = '';
                }
            });
            
            // Agregar event listener para el botón eliminar
            const newButton = container.querySelector(`.experiencia-item[data-id="${index}"] .btn-eliminar-experiencia`);
            newButton.addEventListener('click', function() {
                this.closest('.experiencia-item').remove();
            });
        });
        
        // Agregar habilidad
        document.getElementById('agregar-habilidad').addEventListener('click', function() {
            const container = document.getElementById('habilidades-container');
            const index = container.querySelectorAll('.habilidad-item').length;
            
            const template = `
                <div class="card habilidad-item" data-id="${index}">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="habilidad_${index}">Habilidad</label>
                            <input type="text" class="form-control" id="habilidad_${index}" name="habilidad" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="nivel_habilidad_${index}">Nivel</label>
                            <select class="form-control" id="nivel_habilidad_${index}" name="nivel_habilidad" required>
                                <option value="">Seleccionar...</option>
                                <option value="principiante">Principiante</option>
                                <option value="intermedio">Intermedio</option>
                                <option value="avanzado">Avanzado</option>
                                <option value="experto">Experto</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="btn btn-outline btn-eliminar-habilidad">Eliminar</button>
                    </div>
                </div>
            `;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = template;
            container.appendChild(tempDiv.firstElementChild);
            
            // Agregar event listener para el botón eliminar
            const newButton = container.querySelector(`.habilidad-item[data-id="${index}"] .btn-eliminar-habilidad`);
            newButton.addEventListener('click', function() {
                this.closest('.habilidad-item').remove();
            });
        });
        
        // Agregar idioma
        document.getElementById('agregar-idioma').addEventListener('click', function() {
            const container = document.getElementById('idiomas-container');
            const index = container.querySelectorAll('.idioma-item').length;
            
            const template = `
                <div class="card idioma-item" data-id="${index}">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="idioma_${index}">Idioma</label>
                            <input type="text" class="form-control" id="idioma_${index}" name="idioma" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="nivel_idioma_${index}">Nivel</label>
                            <select class="form-control" id="nivel_idioma_${index}" name="nivel_idioma" required>
                                <option value="">Seleccionar...</option>
                                <option value="basico">Básico</option>
                                <option value="intermedio">Intermedio</option>
                                <option value="avanzado">Avanzado</option>
                                <option value="nativo">Nativo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="btn btn-outline btn-eliminar-idioma">Eliminar</button>
                    </div>
                </div>
            `;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = template;
            container.appendChild(tempDiv.firstElementChild);
            
            // Agregar event listener para el botón eliminar
            const newButton = container.querySelector(`.idioma-item[data-id="${index}"] .btn-eliminar-idioma`);
            newButton.addEventListener('click', function() {
                this.closest('.idioma-item').remove();
            });
        });
        
        // Agregar referencia
        document.getElementById('agregar-referencia').addEventListener('click', function() {
            const container = document.getElementById('referencias-container');
            const index = container.querySelectorAll('.referencia-item').length;
            
            const template = `
                <div class="card referencia-item" data-id="${index}">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="nombre_referencia_${index}">Nombre del Contacto</label>
                            <input type="text" class="form-control" id="nombre_referencia_${index}" name="nombre_referencia">
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="empresa_referencia_${index}">Empresa</label>
                            <input type="text" class="form-control" id="empresa_referencia_${index}" name="empresa_referencia">
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="telefono_referencia_${index}">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono_referencia_${index}" name="telefono_referencia">
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label for="email_referencia_${index}">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email_referencia_${index}" name="email_referencia">
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="btn btn-outline btn-eliminar-referencia">Eliminar</button>
                    </div>
                </div>
            `;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = template;
            container.appendChild(tempDiv.firstElementChild);
            
            // Agregar event listener para el botón eliminar
            const newButton = container.querySelector(`.referencia-item[data-id="${index}"] .btn-eliminar-referencia`);
            newButton.addEventListener('click', function() {
                this.closest('.referencia-item').remove();
            });
        });
        
        // Event listeners para los botones de eliminar existentes
        document.querySelectorAll('.btn-eliminar-formacion').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.formacion-item').remove();
            });
        });
        
        document.querySelectorAll('.btn-eliminar-experiencia').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.experiencia-item').remove();
            });
        });
        
        document.querySelectorAll('.btn-eliminar-habilidad').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.habilidad-item').remove();
            });
        });
        
        document.querySelectorAll('.btn-eliminar-idioma').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.idioma-item').remove();
            });
        });
        
        document.querySelectorAll('.btn-eliminar-referencia').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.referencia-item').remove();
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>