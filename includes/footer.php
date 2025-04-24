<footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Elevy</h3>
                    <p>Conectando talento con oportunidades desde 2023.</p>
                </div>
                <div class="footer-section">
                    <h3>Enlaces Rápidos</h3>
                    <p><a href="<?php echo SITE_URL; ?>/index.php" style="color: #adb5bd; text-decoration: none;">Inicio</a></p>
                    <p><a href="<?php echo SITE_URL; ?>/pages/ofertas.php" style="color: #adb5bd; text-decoration: none;">Ofertas</a></p>
                    <?php if (estaAutenticado()): ?>
                        <?php if (esRol('candidato')): ?>
                            <p><a href="<?php echo SITE_URL; ?>/pages/panel-candidato.php" style="color: #adb5bd; text-decoration: none;">Mi Perfil</a></p>
                            <p><a href="<?php echo SITE_URL; ?>/pages/cv-form.php" style="color: #adb5bd; text-decoration: none;">Mi CV</a></p>
                        <?php else: ?>
                            <p><a href="<?php echo SITE_URL; ?>/pages/panel-empresa.php" style="color: #adb5bd; text-decoration: none;">Mi Empresa</a></p>
                            <p><a href="<?php echo SITE_URL; ?>/pages/publicar-oferta.php" style="color: #adb5bd; text-decoration: none;">Publicar Oferta</a></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><a href="<?php echo SITE_URL; ?>/pages/login.php" style="color: #adb5bd; text-decoration: none;">Iniciar Sesión</a></p>
                        <p><a href="<?php echo SITE_URL; ?>/pages/registro-candidato.php" style="color: #adb5bd; text-decoration: none;">Registro</a></p>
                    <?php endif; ?>
                </div>
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <p>info@elevy.com</p>
                    <p>+1 809 555 1234</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Elevy. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo SITE_URL; ?>/js/script.js"></script>
</body>
</html>