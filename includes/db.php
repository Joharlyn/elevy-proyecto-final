<?php
require_once 'config.php';

// Conexión a la base de datos
function conectarDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer charset
    $conn->set_charset("utf8");
    
    return $conn;
}

// Función para realizar consultas seguras
function prepararConsulta($conn, $sql, $params = [], $types = '') {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }
    
    if (!empty($params)) {
        // Si no se especifica el tipo, inferirlo
        if (empty($types)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }
        }
        
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt;
}

// Función para obtener un solo registro
function obtenerRegistro($sql, $params = [], $types = '') {
    $conn = conectarDB();
    $stmt = prepararConsulta($conn, $sql, $params, $types);
    $stmt->execute();
    $result = $stmt->get_result();
    $registro = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $registro;
}

// Función para obtener múltiples registros
function obtenerRegistros($sql, $params = [], $types = '') {
    $conn = conectarDB();
    $stmt = prepararConsulta($conn, $sql, $params, $types);
    $stmt->execute();
    $result = $stmt->get_result();
    $registros = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    
    return $registros;
}

// Función para insertar, actualizar o eliminar
function ejecutarConsulta($sql, $params = [], $types = '') {
    $conn = conectarDB();
    $stmt = prepararConsulta($conn, $sql, $params, $types);
    $result = $stmt->execute();
    
    if ($result && stripos($sql, 'INSERT') === 0) {
        $result = $stmt->insert_id;
    }
    
    $stmt->close();
    $conn->close();
    
    return $result;
}
?>