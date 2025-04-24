<?php
$titulo_pagina = 'Iniciar Sesión';
require_once '../includes/header.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = limpiarInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember-me']);
    
    // Validaciones
    if (empty($email)) {
        $errores[] = "El correo electrónico es obligatorio";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    }
    
    // Si no hay errores, intentar login
    if (empty($errores)) {
        // Buscar el usuario
        $usuario = obtenerRegistro(
            "SELECT id, password, tipo FROM usuarios WHERE email = ?",
            [$email]
        );
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['tipo'] = $usuario['tipo'];
            
            // Obtener datos específicos según tipo
            if ($usuario['tipo'] === 'candidato') {
                $candidato = obtenerRegistro(
                    "SELECT id, nombre, apellido FROM candidatos WHERE usuario_id = ?",
                    [$usuario['id']]
                );
                
                if ($candidato) {
                    $_SESSION['candidato_id'] = $candidato['id'];
                    $_SESSION['nombre'] = $candidato['nombre'] . ' ' . $candidato['apellido'];
                }
                
                header('Location: panel-candidato.php');
            } else {
                $empresa = obtenerRegistro(
                    "SELECT id, nombre FROM empresas WHERE usuario_id = ?",
                    [$usuario['id']]
                );
                
                if ($empresa) {
                    $_SESSION['empresa_id'] = $empresa['id'];
                    $_SESSION['nombre'] = $empresa['nombre'];
                }
                
                header('Location: panel-empresa.php');
            }
            
            exit;
        } else {
            $errores[] = "Credenciales incorrectas";
        }
    }
}
?>

<div class="auth-background">
    <div class="auth-container">
        <h2 class="form-title">Iniciar Sesión</h2>
        
        <?php if (!empty($errores)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form id="login-form" action="login.php" method="post">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <input type="checkbox" id="remember-me" name="remember-me" <?php echo isset($remember) && $remember ? 'checked' : ''; ?>>
                    <label for="remember-me">Recordarme</label>
                </div>
                <a href="recuperar-password.php" style="color: var(--primary-color); text-decoration: none;">¿Olvidaste tu contraseña?</a>
            </div>
            
            <div style="margin-bottom: 20px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Iniciar Sesión</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <p>¿No tienes cuenta? <a href="registro-candidato.php" style="color: var(--primary-color); text-decoration: none;">Regístrate aquí</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>