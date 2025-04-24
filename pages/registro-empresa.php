<?php
$titulo_pagina = 'Registro de Empresa';
require_once '../includes/header.php';

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar datos
    $nombre_empresa = limpiarInput($_POST['nombre_empresa'] ?? '');
    $email = limpiarInput($_POST['email'] ?? '');
    $telefono = limpiarInput($_POST['telefono'] ?? '');
    $ubicacion = limpiarInput($_POST['ubicacion_empresa'] ?? '');
    $direccion = limpiarInput($_POST['direccion'] ?? '');
    $sector = limpiarInput($_POST['sector'] ?? '');
    $descripcion = limpiarInput($_POST['descripcion'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $terminos = isset($_POST['terminos']);
    
    // Validaciones
    if (empty($nombre_empresa)) {
        $errores[] = "El nombre de la empresa es obligatorio";
    }
    
    if (empty($email)) {
        $errores[] = "El correo electrónico es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico es inválido";
    } else {
        // Verificar si el correo ya está registrado
        $usuario_existente = obtenerRegistro("SELECT id FROM usuarios WHERE email = ?", [$email]);
        if ($usuario_existente) {
            $errores[] = "Este correo electrónico ya está registrado";
        }
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    } elseif ($password !== $password_confirm) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    if (!$terminos) {
        $errores[] = "Debes aceptar los términos y condiciones";
    }
    
    // Si no hay errores, proceder al registro
    if (empty($errores)) {
        // Iniciar transacción
        $conn = conectarDB();
        $conn->begin_transaction();
        
        try {
            // Insertar usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO usuarios (email, password, tipo) VALUES (?, ?, 'empresa')");
            $stmt->bind_param("ss", $email, $password_hash);
            $stmt->execute();
            
            $usuario_id = $stmt->insert_id;
            
            // Insertar empresa
            $logo = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
                $logo = subirArchivo($_FILES['logo'], PHOTOS_PATH, ['jpg', 'jpeg', 'png'], 'empresa');
            }
            
            $stmt = $conn->prepare("INSERT INTO empresas (usuario_id, nombre, telefono, ubicacion, direccion, sector, descripcion, logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $usuario_id, $nombre_empresa, $telefono, $ubicacion, $direccion, $sector, $descripcion, $logo);
            $stmt->execute();
            
            $empresa_id = $stmt->insert_id;
            
            // Confirmar transacción
            $conn->commit();
            
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario_id;
            $_SESSION['empresa_id'] = $empresa_id;
            $_SESSION['tipo'] = 'empresa';
            $_SESSION['nombre'] = $nombre_empresa;
            
            // Redirigir al panel
            header('Location: panel-empresa.php');
            exit;
        } catch (Exception $e) {
            // Revertir cambios si hay error
            $conn->rollback();
            $errores[] = "Error al registrar: " . $e->getMessage();
        }
        
        $conn->close();
    }
}
?>

<div class="auth-background">
    <div class="auth-container" style="max-width: 700px;">
        <h2 class="form-title">Registro de Empresa</h2>
        
        <?php if (!empty($errores)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form id="company-register-form" action="registro-empresa.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nombre_empresa">Nombre de la Empresa</label>
                <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="<?php echo isset($nombre_empresa) ? htmlspecialchars($nombre_empresa) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="ubicacion_empresa">Ubicación Principal</label>
                    <select class="form-control" id="ubicacion_empresa" name="ubicacion_empresa" required>
                        <option value="">Selecciona la ubicación</option>
                        <option value="santo_domingo" <?php echo (isset($ubicacion) && $ubicacion === 'santo_domingo') ? 'selected' : ''; ?>>Santo Domingo</option>
                        <option value="santiago" <?php echo (isset($ubicacion) && $ubicacion === 'santiago') ? 'selected' : ''; ?>>Santiago</option>
                        <option value="otra_rd" <?php echo (isset($ubicacion) && $ubicacion === 'otra_rd') ? 'selected' : ''; ?>>Otra ciudad (Rep. Dominicana)</option>
                        <option value="internacional" <?php echo (isset($ubicacion) && $ubicacion === 'internacional') ? 'selected' : ''; ?>>Internacional</option>
                    </select>
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="telefono">Teléfono de Contacto</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo isset($telefono) ? htmlspecialchars($telefono) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo isset($direccion) ? htmlspecialchars($direccion) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="sector">Sector de la Empresa</label>
                <select class="form-control" id="sector" name="sector" required>
                    <option value="">Selecciona un sector</option>
                    <option value="tecnologia" <?php echo (isset($sector) && $sector === 'tecnologia') ? 'selected' : ''; ?>>Tecnología</option>
                    <option value="salud" <?php echo (isset($sector) && $sector === 'salud') ? 'selected' : ''; ?>>Salud</option>
                    <option value="educacion" <?php echo (isset($sector) && $sector === 'educacion') ? 'selected' : ''; ?>>Educación</option>
                    <option value="finanzas" <?php echo (isset($sector) && $sector === 'finanzas') ? 'selected' : ''; ?>>Finanzas</option>
                    <option value="comercio" <?php echo (isset($sector) && $sector === 'comercio') ? 'selected' : ''; ?>>Comercio</option>
                    <option value="manufactura" <?php echo (isset($sector) && $sector === 'manufactura') ? 'selected' : ''; ?>>Manufactura</option>
                    <option value="servicios" <?php echo (isset($sector) && $sector === 'servicios') ? 'selected' : ''; ?>>Servicios</option>
                    <option value="otro" <?php echo (isset($sector) && $sector === 'otro') ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="password">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <small class="form-text">La contraseña debe tener al menos 8 caracteres</small>
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="logo">Logo de la Empresa (opcional)</label>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción de la Empresa</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?php echo isset($descripcion) ? htmlspecialchars($descripcion) : ''; ?></textarea>
            </div>
            
            <div style="margin-top: 20px;">
                <input type="checkbox" id="terminos" name="terminos" required>
                <label for="terminos">Acepto los términos y condiciones</label>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <button type="submit" class="btn btn-primary" style="width: 200px;">Registrarse</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <p>¿Ya tienes cuenta? <a href="login.php" style="color: var(--primary-color); text-decoration: none;">Inicia sesión aquí</a></p>
            <p>¿Eres un candidato? <a href="registro-candidato.php" style="color: var(--primary-color); text-decoration: none;">Regístrate como candidato</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>