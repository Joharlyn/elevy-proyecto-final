<?php
$titulo_pagina = 'Registro de Candidato';
require_once '../includes/header.php';

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar datos
    $nombre = limpiarInput($_POST['nombre'] ?? '');
    $apellido = limpiarInput($_POST['apellido'] ?? '');
    $email = limpiarInput($_POST['email'] ?? '');
    $telefono = limpiarInput($_POST['telefono'] ?? '');
    $ubicacion = limpiarInput($_POST['ubicacion'] ?? '');
    $direccion = limpiarInput($_POST['direccion'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $terminos = isset($_POST['terminos']);
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio";
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
            
            $stmt = $conn->prepare("INSERT INTO usuarios (email, password, tipo) VALUES (?, ?, 'candidato')");
            $stmt->bind_param("ss", $email, $password_hash);
            $stmt->execute();
            
            $usuario_id = $stmt->insert_id;
            
            // Insertar candidato
            $foto = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
                $foto = subirArchivo($_FILES['foto'], PHOTOS_PATH, ['jpg', 'jpeg', 'png'], 'candidato');
            }
            
            $cv_pdf = null;
            if (isset($_FILES['cv_pdf']) && $_FILES['cv_pdf']['size'] > 0) {
                $cv_pdf = subirArchivo($_FILES['cv_pdf'], CVS_PATH, ['pdf'], 'cv');
            }
            
            $stmt = $conn->prepare("INSERT INTO candidatos (usuario_id, nombre, apellido, telefono, ubicacion, direccion, foto, cv_pdf) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $usuario_id, $nombre, $apellido, $telefono, $ubicacion, $direccion, $foto, $cv_pdf);
            $stmt->execute();
            
            $candidato_id = $stmt->insert_id;
            
            // Confirmar transacción
            $conn->commit();
            
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario_id;
            $_SESSION['candidato_id'] = $candidato_id;
            $_SESSION['tipo'] = 'candidato';
            $_SESSION['nombre'] = $nombre . ' ' . $apellido;
            
            // Redirigir al panel
            header('Location: panel-candidato.php');
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
        <h2 class="form-title">Registro de Candidato</h2>
        
        <?php if (!empty($errores)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form id="candidate-register-form" action="registro-candidato.php" method="post" enctype="multipart/form-data">
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="nombre">Nombre(s)</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="apellido">Apellido(s)</label>
                    <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo isset($apellido) ? htmlspecialchars($apellido) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo isset($telefono) ? htmlspecialchars($telefono) : ''; ?>" required>
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="ubicacion">Ubicación</label>
                    <select class="form-control" id="ubicacion" name="ubicacion" required>
                        <option value="">Selecciona tu ubicación</option>
                        <option value="santo_domingo" <?php echo (isset($ubicacion) && $ubicacion === 'santo_domingo') ? 'selected' : ''; ?>>Santo Domingo</option>
                        <option value="santiago" <?php echo (isset($ubicacion) && $ubicacion === 'santiago') ? 'selected' : ''; ?>>Santiago</option>
                        <option value="otra_rd" <?php echo (isset($ubicacion) && $ubicacion === 'otra_rd') ? 'selected' : ''; ?>>Otra ciudad (Rep. Dominicana)</option>
                        <option value="internacional" <?php echo (isset($ubicacion) && $ubicacion === 'internacional') ? 'selected' : ''; ?>>Internacional</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo isset($direccion) ? htmlspecialchars($direccion) : ''; ?>" required>
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
                <label for="foto">Foto (opcional)</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="cv_pdf">CV en PDF (opcional)</label>
                <input type="file" class="form-control" id="cv_pdf" name="cv_pdf" accept=".pdf">
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
            <p>¿Eres una empresa? <a href="registro-empresa.php" style="color: var(--primary-color); text-decoration: none;">Regístrate como empresa</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>