<?php
/**
 * procesar_transmision.php
 * Backend para el formulario de contacto de ARCHIVE_0 (v3).
 * 
 * Utiliza PDO con Prepared Statements para prevenir inyecciones SQL.
 * Valida los datos de entrada tanto en tipo como en longitud.
 * Responde siempre en formato JSON para consumo vía fetch().
 * 
 * REQUISITOS:
 * - PHP >= 7.4
 * - MySQL/MariaDB con la tabla 'transmisiones' creada (ver SQL abajo)
 * 
 * SQL PARA CREAR LA TABLA:
 * CREATE TABLE transmisiones (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   nombre VARCHAR(255) NOT NULL,
 *   tipo VARCHAR(100) NOT NULL,
 *   mensaje TEXT NOT NULL,
 *   fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
 *   ip_remitente VARCHAR(45)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */

// Cabeceras para respuesta JSON y CORS básico
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Solo aceptar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Método no permitido. Use POST.'
    ]);
    exit;
}

// =============================================
// CONFIGURACIÓN DE BASE DE DATOS
// En producción, mover estas credenciales a un
// archivo .env o config fuera del directorio público.
// =============================================
$dbHost = 'localhost';
$dbName = 'archivo0_db';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

// =============================================
// VALIDACIÓN DE DATOS DE ENTRADA
// Se valida en backend independientemente del frontend
// porque nunca se debe confiar solo en la validación del cliente.
// =============================================
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
$mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

// Verificar que los campos obligatorios no estén vacíos
if (empty($nombre) || empty($tipo) || empty($mensaje)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'All fields are required. Transmission rejected.'
    ]);
    exit;
}

// Verificar longitudes máximas para evitar datos excesivos
if (strlen($nombre) > 255) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Operative name exceeds maximum length (255 characters).'
    ]);
    exit;
}

if (strlen($mensaje) > 5000) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Message content exceeds maximum length (5000 characters).'
    ]);
    exit;
}

// Validar que el tipo de transmisión sea uno de los valores permitidos
$tiposPermitidos = ['General Inquiry', 'Evidence Submission', 'Technical Feedback', 'Media Request'];
if (!in_array($tipo, $tiposPermitidos)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Invalid transmission type. Possible tampering detected.'
    ]);
    exit;
}

// =============================================
// CONEXIÓN A BASE DE DATOS CON PDO
// Manejo de errores mediante excepciones para
// capturar fallos de conexión de forma controlada.
// =============================================
try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Usar prepared statements nativos
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $opciones);

} catch (PDOException $e) {
    // No exponer detalles del error de BD al cliente
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Secure channel unavailable. Try again later.'
    ]);
    // En producción, registrar el error en un log:
    // error_log('DB Connection Error: ' . $e->getMessage());
    exit;
}

// =============================================
// INSERCIÓN CON PREPARED STATEMENT
// Los placeholders (:nombre, :tipo, etc.) previenen
// inyecciones SQL al separar la consulta de los datos.
// =============================================
try {
    $sql = "INSERT INTO transmisiones (nombre, tipo, mensaje, ip_remitente) 
            VALUES (:nombre, :tipo, :mensaje, :ip)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nombre'  => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
        ':tipo'    => $tipo,
        ':mensaje' => htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'),
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);

    // Respuesta exitosa
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Transmission encrypted and archived successfully.',
        'id_transmision' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Archival process failed. Data integrity maintained.'
    ]);
    // error_log('Insert Error: ' . $e->getMessage());
    exit;
}
