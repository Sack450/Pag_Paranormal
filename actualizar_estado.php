<?php
/**
 * actualizar_estado.php
 * Controlador backend para cambiar el estado de moderación de una evidencia.
 * 
 * Verifica que el usuario administrador tenga una sesión activa válida,
 * valida los datos de entrada e interactúa con la base de datos de manera segura
 * mediante Sentencias Preparadas (Prepared Statements) para prevenir inyecciones SQL.
 */

// Iniciar sesión para validar privilegios de administrador
session_start();

// Establecer cabeceras para retornar JSON y evitar problemas de caché
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// 1. SEGURIDAD: COMPROBAR AUTENTICACIÓN
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Acceso denegado. No tiene una sesión de administrador activa.'
    ]);
    exit;
}

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Método no permitido. Utilice POST.'
    ]);
    exit;
}

// 2. OBTENER Y VALIDAR DATOS DE ENTRADA
$id_reporte = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nuevo_estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

// Validar parámetros requeridos
if ($id_reporte <= 0 || empty($nuevo_estado)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Parámetros incompletos o no válidos para procesar la solicitud.'
    ]);
    exit;
}

// Restringir estados permitidos para evitar modificaciones maliciosas (ej: inyectar otros textos)
$estados_validos = ['aceptado', 'rechazado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El estado proporcionado no es válido.'
    ]);
    exit;
}

// 3. INCLUIR CONEXIÓN E INSERTAR CAMBIO DE ESTADO
require_once 'conexion.php';

// Validar que la conexión esté activa
if (!isset($conexion) || $conexion->connect_error) {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error de conexión interna con la base de datos.'
    ]);
    exit;
}

// Sentencia SQL para actualizar el estado del reporte por ID usando Prepared Statement (?)
$sql_actualizar = "UPDATE reportes_evidencia SET estado = ? WHERE id = ?";

$stmt = $conexion->prepare($sql_actualizar);

if ($stmt) {
    // Vincular parámetros: 's' para string (estado) e 'i' para entero (id)
    $stmt->bind_param("si", $nuevo_estado, $id_reporte);
    
    if ($stmt->execute()) {
        // Verificar si la fila realmente existía y fue modificada
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'exito' => true,
                'mensaje' => 'El estado de la evidencia ha sido actualizado a ' . $nuevo_estado . '.'
            ]);
        } else {
            // El ID podría no existir o el estado ya era el mismo
            http_response_code(404);
            echo json_encode([
                'exito' => false,
                'mensaje' => 'No se encontró la evidencia especificada o no hubo cambios en su estado.'
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Error al ejecutar la actualización en la base de datos: ' . $stmt->error
        ]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al preparar la consulta de actualización: ' . $conexion->error
    ]);
}

$conexion->close();
?>
