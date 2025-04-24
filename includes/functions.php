<?php
require_once 'db.php';

// Limpiar input
function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Verificar si el usuario está autenticado
function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

// Verificar el rol del usuario
function esRol($rol) {
    return estaAutenticado() && $_SESSION['tipo'] === $rol;
}

// Redirigir si no está autenticado
function requiereAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

// Redirigir si no tiene el rol correcto
function requiereRol($rol) {
    if (!esRol($rol)) {
        header('Location: index.php');
        exit;
    }
}

// Generar un nombre de archivo único
function generarNombreArchivo($archivo, $prefijo = '') {
    $nombre_original = pathinfo($archivo['name'], PATHINFO_FILENAME);
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_nuevo = $prefijo . '_' . uniqid() . '_' . date('Ymd') . '.' . $extension;
    return $nombre_nuevo;
}

// Subir un archivo
function subirArchivo($archivo, $directorio, $tipos_permitidos = [], $prefijo = '') {
    // Verificar si hay un archivo
    if ($archivo['size'] <= 0) {
        return false;
    }
    
    // Verificar el tipo de archivo si se especificaron tipos permitidos
    if (!empty($tipos_permitidos)) {
        $tipo = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($tipo), $tipos_permitidos)) {
            return false;
        }
    }
    
    // Generar nombre único
    $nombre_nuevo = generarNombreArchivo($archivo, $prefijo);
    $ruta_destino = $directorio . $nombre_nuevo;
    
    // Mover archivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return $nombre_nuevo;
    }
    
    return false;
}

// Obtener datos del candidato
function obtenerDatosCandidato($id) {
    $candidato = obtenerRegistro(
        "SELECT * FROM candidatos WHERE id = ?",
        [$id]
    );
    
    if (!$candidato) {
        return null;
    }
    
    // Obtener formación académica
    $candidato['formacion'] = obtenerRegistros(
        "SELECT * FROM formacion_academica WHERE candidato_id = ? ORDER BY fecha_inicio DESC",
        [$id]
    );
    
    // Obtener experiencia laboral
    $candidato['experiencia'] = obtenerRegistros(
        "SELECT * FROM experiencia_laboral WHERE candidato_id = ? ORDER BY fecha_inicio DESC",
        [$id]
    );
    
    // Obtener habilidades
    $candidato['habilidades'] = obtenerRegistros(
        "SELECT * FROM habilidades WHERE candidato_id = ?",
        [$id]
    );
    
    // Obtener idiomas
    $candidato['idiomas'] = obtenerRegistros(
        "SELECT * FROM idiomas WHERE candidato_id = ?",
        [$id]
    );
    
    // Obtener logros
    $logro = obtenerRegistro(
        "SELECT * FROM logros_proyectos WHERE candidato_id = ?",
        [$id]
    );
    $candidato['logros'] = $logro ? $logro['descripcion'] : '';
    
    // Obtener redes
    $candidato['redes'] = obtenerRegistros(
        "SELECT * FROM redes_profesionales WHERE candidato_id = ?",
        [$id]
    );
    
    // Obtener referencias
    $candidato['referencias'] = obtenerRegistros(
        "SELECT * FROM referencias WHERE candidato_id = ?",
        [$id]
    );
    
    return $candidato;
}

// Obtener datos de la empresa
function obtenerDatosEmpresa($id) {
    return obtenerRegistro(
        "SELECT * FROM empresas WHERE id = ?",
        [$id]
    );
}

// Obtener ofertas
function obtenerOfertas($filtros = [], $limite = null, $offset = 0) {
    $sql = "SELECT o.*, e.nombre as nombre_empresa, e.logo as logo_empresa 
            FROM ofertas o
            INNER JOIN empresas e ON o.empresa_id = e.id
            WHERE o.estado = 'activa'";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtros)) {
        if (isset($filtros['ubicacion']) && !empty($filtros['ubicacion'])) {
            $sql .= " AND o.ubicacion = ?";
            $params[] = $filtros['ubicacion'];
        }
        
        if (isset($filtros['categoria']) && !empty($filtros['categoria'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM categorias_ofertas co WHERE co.oferta_id = o.id AND co.categoria = ?)";
            $params[] = $filtros['categoria'];
        }
        
        if (isset($filtros['tipo_contrato']) && !empty($filtros['tipo_contrato'])) {
            $sql .= " AND o.tipo_contrato = ?";
            $params[] = $filtros['tipo_contrato'];
        }
        
        if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
            $sql .= " AND (o.titulo LIKE ? OR o.descripcion LIKE ? OR e.nombre LIKE ?)";
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }
    }
    
    $sql .= " ORDER BY o.fecha_publicacion DESC";
    
    // Aplicar límite si se especifica
    if ($limite !== null) {
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limite;
    }
    
    return obtenerRegistros($sql, $params);
}

// Verificar si un candidato ya aplicó a una oferta
function yaAplico($candidato_id, $oferta_id) {
    $postulacion = obtenerRegistro(
        "SELECT id FROM postulaciones WHERE candidato_id = ? AND oferta_id = ?",
        [$candidato_id, $oferta_id]
    );
    
    return $postulacion !== null;
}

// Contar postulaciones por oferta
function contarPostulaciones($oferta_id) {
    $resultado = obtenerRegistro(
        "SELECT COUNT(*) as total FROM postulaciones WHERE oferta_id = ?",
        [$oferta_id]
    );
    
    return $resultado['total'];
}

// Formatear fecha
function formatearFecha($fecha) {
    if (empty($fecha)) {
        return '';
    }
    
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

// Calcular tiempo transcurrido
function tiempoTranscurrido($fecha) {
    $ahora = time();
    $tiempo = strtotime($fecha);
    $diferencia = $ahora - $tiempo;
    
    $segundos = $diferencia;
    $minutos = round($diferencia / 60);
    $horas = round($diferencia / 3600);
    $dias = round($diferencia / 86400);
    $semanas = round($diferencia / 604800);
    $meses = round($diferencia / 2419200);
    $años = round($diferencia / 29030400);
    
    if ($segundos < 60) {
        return "Hace un momento";
    } else if ($minutos < 60) {
        return $minutos === 1 ? "Hace 1 minuto" : "Hace $minutos minutos";
    } else if ($horas < 24) {
        return $horas === 1 ? "Hace 1 hora" : "Hace $horas horas";
    } else if ($dias < 7) {
        return $dias === 1 ? "Hace 1 día" : "Hace $dias días";
    } else if ($semanas < 4) {
        return $semanas === 1 ? "Hace 1 semana" : "Hace $semanas semanas";
    } else if ($meses < 12) {
        return $meses === 1 ? "Hace 1 mes" : "Hace $meses meses";
    } else {
        return $años === 1 ? "Hace 1 año" : "Hace $años años";
    }
}
?>