<?php
$titulo_pagina = 'Ofertas de Empleo';
require_once '../includes/header.php';

// Parámetros de filtrado y paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$filtros = [];

if (isset($_GET['ubicacion']) && !empty($_GET['ubicacion'])) {
    $filtros['ubicacion'] = limpiarInput($_GET['ubicacion']);
}

if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
    $filtros['categoria'] = limpiarInput($_GET['categoria']);
}

if (isset($_GET['tipo_contrato']) && !empty($_GET['tipo_contrato'])) {
    $filtros['tipo_contrato'] = limpiarInput($_GET['tipo_contrato']);
}

if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $filtros['busqueda'] = limpiarInput($_GET['busqueda']);
}

// Obtener ofertas
$ofertas = obtenerOfertas($filtros, $por_pagina, $offset);

// Contar total para paginación
$total_ofertas = obtenerRegistro(
    "SELECT COUNT(*) as total FROM ofertas WHERE estado = 'activa'",
    []
)['total'];

$total_paginas = ceil($total_ofertas / $por_pagina);

// Obtener ubicaciones disponibles
$ubicaciones = obtenerRegistros(
    "SELECT DISTINCT ubicacion, COUNT(*) as total FROM ofertas WHERE estado = 'activa' GROUP BY ubicacion ORDER BY total DESC",
    []
);

// Obtener categorías disponibles
$categorias = obtenerRegistros(
    "SELECT DISTINCT co.categoria, COUNT(*) as total 
    FROM categorias_ofertas co 
    INNER JOIN ofertas o ON co.oferta_id = o.id 
    WHERE o.estado = 'activa' 
    GROUP BY co.categoria 
    ORDER BY total DESC",
    []
);

// Obtener tipos de contrato disponibles
$tipos_contrato = obtenerRegistros(
    "SELECT DISTINCT tipo_contrato, COUNT(*) as total FROM ofertas WHERE estado = 'activa' GROUP BY tipo_contrato ORDER BY total DESC",
    []
);

// Mapeo de tipos de contrato
$tipos_contrato_labels = [
    'tiempo_completo' => 'Tiempo Completo',
    'medio_tiempo' => 'Medio Tiempo',
    'temporal' => 'Temporal',
    'proyecto' => 'Por Proyecto',
    'practicas' => 'Prácticas Profesionales'
];
?>

<div class="container">
    <div style="margin: 30px 0;">
        <div class="offers-header">
            <h1>Ofertas de Empleo</h1>
            
            <form action="ofertas.php" method="get" class="search-input" style="width: 300px;">
                <i class="fas fa-search"></i>
                <input type="text" name="busqueda" placeholder="Buscar ofertas..." value="<?php echo isset($filtros['busqueda']) ? htmlspecialchars($filtros['busqueda']) : ''; ?>">
            </form>
        </div>
        
        <?php if (!empty($filtros)): ?>
            <div class="filters-bar">
                <div class="active-filters">
                    <?php if (isset($filtros['ubicacion'])): ?>
                        <span class="filter-tag">
                            <?php echo htmlspecialchars($filtros['ubicacion']); ?>
                            <a href="<?php echo removeQueryParam('ubicacion'); ?>" style="color: inherit;"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (isset($filtros['categoria'])): ?>
                        <span class="filter-tag">
                            <?php echo htmlspecialchars($filtros['categoria']); ?>
                            <a href="<?php echo removeQueryParam('categoria'); ?>" style="color: inherit;"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (isset($filtros['tipo_contrato'])): ?>
                        <span class="filter-tag">
                            <?php echo htmlspecialchars($tipos_contrato_labels[$filtros['tipo_contrato']] ?? $filtros['tipo_contrato']); ?>
                            <a href="<?php echo removeQueryParam('tipo_contrato'); ?>" style="color: inherit;"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (isset($filtros['busqueda'])): ?>
                        <span class="filter-tag">
                            "<?php echo htmlspecialchars($filtros['busqueda']); ?>"
                            <a href="<?php echo removeQueryParam('busqueda'); ?>" style="color: inherit;"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endif; ?>
                    
                    <span class="filter-count"><?php echo count($ofertas); ?> ofertas</span>
                </div>
                <a href="ofertas.php" class="btn-reset-filter"><i class="fas fa-redo"></i> Restablecer filtros</a>
            </div>
        <?php endif; ?>
        
        <div class="filters-container">
            <div class="filters-sidebar">
                <div class="filter-group">
                    <h3>Ubicación</h3>
                    <div class="filter-options">
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                            <label class="checkbox-container">
                                <input type="checkbox" <?php echo isset($filtros['ubicacion']) && $filtros['ubicacion'] === $ubicacion['ubicacion'] ? 'checked' : ''; ?> 
                                       onclick="window.location.href='<?php echo addOrRemoveQueryParam('ubicacion', $ubicacion['ubicacion']); ?>'">
                                <span class="checkmark"></span>
                                <?php echo htmlspecialchars($ubicacion['ubicacion']); ?>
                                <span class="count"><?php echo $ubicacion['total']; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-group">
                    <h3>Categorías</h3>
                    <div class="filter-options">
                        <?php foreach ($categorias as $categoria): ?>
                            <label class="checkbox-container">
                                <input type="checkbox" <?php echo isset($filtros['categoria']) && $filtros['categoria'] === $categoria['categoria'] ? 'checked' : ''; ?> 
                                       onclick="window.location.href='<?php echo addOrRemoveQueryParam('categoria', $categoria['categoria']); ?>'">
                                <span class="checkmark"></span>
                                <?php echo htmlspecialchars(ucfirst($categoria['categoria'])); ?>
                                <span class="count"><?php echo $categoria['total']; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-group">
                    <h3>Tipo de Contrato</h3>
                    <div class="filter-options">
                        <?php foreach ($tipos_contrato as $tipo): ?>
                            <label class="checkbox-container">
                                <input type="checkbox" <?php echo isset($filtros['tipo_contrato']) && $filtros['tipo_contrato'] === $tipo['tipo_contrato'] ? 'checked' : ''; ?> 
                                       onclick="window.location.href='<?php echo addOrRemoveQueryParam('tipo_contrato', $tipo['tipo_contrato']); ?>'">
                                <span class="checkmark"></span>
                                <?php echo htmlspecialchars($tipos_contrato_labels[$tipo['tipo_contrato']] ?? $tipo['tipo_contrato']); ?>
                                <span class="count"><?php echo $tipo['total']; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="jobs-list">
                <?php if (empty($ofertas)): ?>
                    <div class="job-card" style="text-align: center;">
                        <p>No se encontraron ofertas con los filtros aplicados.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ofertas as $oferta): ?>
                        <div class="job-card">
                            <div class="job-content">
                                <div class="job-header">
                                    <h3 class="job-title"><?php echo htmlspecialchars($oferta['titulo']); ?></h3>
                                    <div class="company-name"><?php echo htmlspecialchars($oferta['nombre_empresa']); ?></div>
                                </div>
                                <div class="job-details">
                                    <div class="job-detail"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($oferta['ubicacion']); ?></div>
                                    <div class="job-detail"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($tipos_contrato_labels[$oferta['tipo_contrato']] ?? $oferta['tipo_contrato']); ?></div>
                                    <div class="job-detail"><i class="fas fa-clock"></i> <?php echo tiempoTranscurrido($oferta['fecha_publicacion']); ?></div>
                                </div>
                                <p class="job-description"><?php echo htmlspecialchars(substr($oferta['descripcion'], 0, 150) . '...'); ?></p>
                            </div>
                            <div class="job-action">
                                <a href="detalle-oferta.php?id=<?php echo $oferta['id']; ?>" class="btn-apply">Ver Detalles</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($total_paginas > 1): ?>
                        <div class="pagination">
                            <?php if ($pagina > 1): ?>
                                <a href="<?php echo addQueryParam('pagina', $pagina - 1); ?>" class="page-prev">Anterior</a>
                            <?php endif; ?>
                            
                            <?php
                            // Mostrar un número limitado de páginas
                            $start = max(1, $pagina - 2);
                            $end = min($total_paginas, $pagina + 2);
                            
                            // Asegurar que se muestren al menos 5 páginas si hay suficientes
                            if ($end - $start + 1 < 5 && $total_paginas >= 5) {
                                if ($start == 1) {
                                    $end = min($total_paginas, 5);
                                } else {
                                    $start = max(1, $total_paginas - 4);
                                }
                            }
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="<?php echo addQueryParam('pagina', $i); ?>" class="page-number <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="<?php echo addQueryParam('pagina', $pagina + 1); ?>" class="page-next">Siguiente</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Función para agregar o quitar un parámetro de la URL
function addOrRemoveQueryParam($param, $value) {
    $params = $_GET;
    
    // Si el parámetro ya existe y tiene el mismo valor, eliminarlo
    if (isset($params[$param]) && $params[$param] == $value) {
        unset($params[$param]);
    } else {
        // Si no, establecerlo
        $params[$param] = $value;
    }
    
    // Reiniciar paginación al cambiar filtros
    if ($param !== 'pagina') {
        unset($params['pagina']);
    }
    
    // Construir URL
    $query = http_build_query($params);
    $url = 'ofertas.php';
    if ($query) {
        $url .= '?' . $query;
    }
    
    return $url;
}

// Función para eliminar un parámetro de la URL
function removeQueryParam($param) {
    $params = $_GET;
    unset($params[$param]);
    
    // Reiniciar paginación al cambiar filtros
    if ($param !== 'pagina') {
        unset($params['pagina']);
    }
    
    // Construir URL
    $query = http_build_query($params);
    $url = 'ofertas.php';
    if ($query) {
        $url .= '?' . $query;
    }
    
    return $url;
}

// Función para agregar un parámetro a la URL
function addQueryParam($param, $value) {
    $params = $_GET;
    $params[$param] = $value;
    
    // Construir URL
    $query = http_build_query($params);
    $url = 'ofertas.php';
    if ($query) {
        $url .= '?' . $query;
    }
    
    return $url;
}
?>

<?php require_once '../includes/footer.php'; ?>