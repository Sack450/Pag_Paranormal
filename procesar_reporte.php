<?php
/**
 * procesar_reporte.php
 * Controlador backend para procesar el formulario de subida de evidencias.
 * 
 * Valida los datos recibidos (frontend y backend) e inserta el caso en la base de datos
 * utilizando Sentencias Preparadas (Prepared Statements) para prevenir inyección SQL.
 * Gestiona de forma segura los archivos subidos, restringiendo extensiones y tamaños.
 */

// Establecer cabeceras para retornar JSON y evitar problemas de caché
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Método no permitido. Utilice POST.'
    ]);
    exit;
}

// 1. INCLUIR LA CONEXIÓN EXISTENTE A LA BASE DE DATOS
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

// 2. CAPTURA Y SANITIZACIÓN DE DATOS TEXTUALES
// Trim para remover espacios adicionales y evitar registros basura
$nombre_cliente = isset($_POST['nombre_cliente']) ? trim($_POST['nombre_cliente']) : '';
$email_cliente = isset($_POST['email_cliente']) ? trim($_POST['email_cliente']) : '';
$titulo_caso = isset($_POST['titulo_caso']) ? trim($_POST['titulo_caso']) : '';
$tipo_evidencia = isset($_POST['tipo_evidencia']) ? trim($_POST['tipo_evidencia']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

// 3. VALIDACIÓN DE CAMPOS OBLIGATORIOS (BACKEND)
if (empty($nombre_cliente) || empty($email_cliente) || empty($titulo_caso) || empty($tipo_evidencia) || empty($descripcion)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Todos los campos obligatorios (*) deben ser completados.'
    ]);
    exit;
}

// Validar formato de correo electrónico
if (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'La dirección de correo electrónico proporcionada no es válida.'
    ]);
    exit;
}

// Limitar longitud de caracteres para evitar saturar la base de datos (seguridad)
if (strlen($nombre_cliente) > 100 || strlen($titulo_caso) > 150) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El nombre o título excede el límite máximo de caracteres permitido.'
    ]);
    exit;
}

if (strlen($descripcion) > 5000) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'La descripción es demasiado larga (máximo 5000 caracteres).'
    ]);
    exit;
}

// Validar tipos de evidencia permitidos
$tipos_permitidos = ['Imagen', 'Audio', 'Relato', 'Otro'];
if (!in_array($tipo_evidencia, $tipos_permitidos)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El tipo de evidencia seleccionado no es válido.'
    ]);
    exit;
}

// 4. PROCESAR SUBIDA DE ARCHIVO (SI EXISTE)
$archivo_ruta = null;

if (isset($_FILES['evidencia_archivo']) && $_FILES['evidencia_archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $archivo = $_FILES['evidencia_archivo'];
    
    // Verificar si hubo un error en la subida a nivel de PHP/servidor
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Error al subir el archivo al servidor. Código de error: ' . $archivo['error']
        ]);
        exit;
    }
    
    // Validar el tamaño del archivo (Límite: 15MB)
    $max_size = 15 * 1024 * 1024; // 15 Megabytes en bytes
    if ($archivo['size'] > $max_size) {
        http_response_code(400);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El archivo supera el tamaño máximo permitido de 15MB.'
        ]);
        exit;
    }
    
    // Validar la extensión del archivo (Lista blanca estricta)
    $filename = $archivo['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp3', 'wav', 'ogg', 'pdf', 'txt'];
    
    if (!in_array($ext, $extensiones_permitidas)) {
        http_response_code(400);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Tipo de archivo no permitido. Solo se aceptan imágenes, audios, PDFs o relatos TXT.'
        ]);
        exit;
    }
    
    // Validar posibles extensiones dobles o intentos de evasión (bypass)
    if (preg_match('/\.(php|phtml|js|html|htaccess)/i', $filename)) {
        http_response_code(400);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El archivo contiene extensiones o patrones bloqueados por seguridad.'
        ]);
        exit;
    }
    
    // Crear el directorio de subidas si no existe
    $dir_subida = 'uploads/evidencias/';
    if (!is_dir($dir_subida)) {
        // Permisos de lectura/escritura (0755)
        mkdir($dir_subida, 0755, true);
    }
    
    // Generar un nombre único aleatorio para el archivo
    // Esto evita colisiones (archivos con el mismo nombre) y sanitiza el nombre
    $nuevo_nombre = 'ev_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $ruta_destino = $dir_subida . $nuevo_nombre;
    
    // Mover el archivo temporal a su destino definitivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        // Guardaremos la ruta relativa para el enlace público
        $archivo_ruta = $ruta_destino;
    } else {
        http_response_code(500);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'No se pudo guardar el archivo en el servidor. Revise permisos de escritura.'
        ]);
        exit;
    }
}

// 5. REGISTRAR EN LA BASE DE DATOS USANDO PREPARED STATEMENTS
// El uso de placeholders (?) asegura que los datos se separen de la sintaxis SQL
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$estado_inicial = 'pendiente'; // Toda evidencia subida inicia como pendiente

$sql_insertar = "INSERT INTO reportes_evidencia (nombre_cliente, email_cliente, titulo_caso, tipo_evidencia, descripcion, archivo_ruta, ip_cliente, estado) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql_insertar);

if ($stmt) {
    // Vincular parámetros de tipo cadena (s = string)
    $stmt->bind_param("ssssssss", $nombre_cliente, $email_cliente, $titulo_caso, $tipo_evidencia, $descripcion, $archivo_ruta, $ip_cliente, $estado_inicial);
    
    if ($stmt->execute()) {
        // Registro insertado correctamente
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Tu caso ha sido registrado con éxito. Actualmente se encuentra en estado PENDIENTE de revisión por el administrador.',
            'id_reporte' => $conexion->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Error al guardar el reporte en la base de datos: ' . $stmt->error
        ]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al preparar la consulta de inserción: ' . $conexion->error
    ]);
}

$conexion->close();
?>
