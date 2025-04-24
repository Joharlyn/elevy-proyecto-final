// Función para agregar transición de página
function addPageTransition() {
    document.body.classList.add('page-transition');
}

// Función para validar formularios
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        if (input.hasAttribute('required') && !input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
            
            // Crear mensaje de error si no existe
            let errorMsg = input.nextElementSibling;
            if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'form-text error-message';
                errorMsg.textContent = 'Este campo es obligatorio';
                errorMsg.style.color = 'red';
                input.parentNode.insertBefore(errorMsg, input.nextSibling);
            }
        } else {
            input.classList.remove('is-invalid');
            
            // Eliminar mensaje de error si existe
            const errorMsg = input.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('error-message')) {
                errorMsg.remove();
            }
        }

        // Validar email
        if (input.type === 'email' && input.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value.trim())) {
                isValid = false;
                input.classList.add('is-invalid');
                
                let errorMsg = input.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'form-text error-message';
                    errorMsg.textContent = 'Ingresa un correo electrónico válido';
                    errorMsg.style.color = 'red';
                    input.parentNode.insertBefore(errorMsg, input.nextSibling);
                }
            }
        }

        // Validar que las contraseñas coincidan
        if (input.id === 'password_confirm') {
            const password = document.getElementById('password');
            if (password && input.value !== password.value) {
                isValid = false;
                input.classList.add('is-invalid');
                
                let errorMsg = input.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'form-text error-message';
                    errorMsg.textContent = 'Las contraseñas no coinciden';
                    errorMsg.style.color = 'red';
                    input.parentNode.insertBefore(errorMsg, input.nextSibling);
                }
            }
        }
    });

    return isValid;
}

// Inicializar todos los formularios con validación
document.addEventListener('DOMContentLoaded', function() {
    // Agregar clase para la transición de carga de página
    document.body.classList.add('page-transition');

    // Agregar navegación fluida a todos los enlaces
    const links = document.querySelectorAll('a');
    links.forEach(link => {
        // Solo para enlaces internos (no para # o enlaces externos)
        if (link.href && link.href.indexOf(window.location.origin) === 0 && !link.href.includes('#')) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                addPageTransition();
                
                // Pequeño retraso para la animación
                setTimeout(function() {
                    window.location = link.href;
                }, 300);
            });
        }
    });

    // Validación de formularios
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const formId = this.id;
            if (!validateForm(formId)) {
                event.preventDefault();
            }
        });
    });

    // Agregar eventos para navegación en móviles
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }
    
    // Detectar ubicación para mostrar ofertas locales/internacionales
    const locationSelectors = document.querySelectorAll('.location-selector');
    if (locationSelectors.length > 0) {
        // Aquí iría el código para detectar la ubicación del usuario
        // Por ahora usamos República Dominicana como predeterminado
        const currentLocation = 'República Dominicana';
        document.getElementById('current-location').textContent = currentLocation;
    }
});