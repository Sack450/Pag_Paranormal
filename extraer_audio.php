<?php
/**
 * extraer_audio.php
 * Script backend para extraer y transmitir o redireccionar al flujo de audio de un video de YouTube.
 * Utiliza APIs públicas de Invidious para obtener el enlace directo del streaming de audio de forma dinámica.
 * 
 * Cumple con las reglas del proyecto: comentarios explicativos en español y validación rigurosa de entradas.
 */

// Cabeceras para permitir el uso en elementos <audio> y habilitar CORS si es necesario
header('Access-Control-Allow-Origin: *');

// 1. VALIDACIÓN DE ENTRADA:
// Obtener y sanitizar el ID del video. Los IDs de YouTube son de 11 caracteres y contienen letras, números, guiones y guiones bajos.
$video_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($video_id) || !preg_match('/^[a-zA-Z0-9_-]{11}$/', $video_id)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'ID de video no suministrado o no válido. Debe ser un ID de YouTube de 11 caracteres.'
    ]);
    exit;
}

// Lista de instancias públicas de Invidious para tener redundancia en caso de caída de alguna
$instancias_invidious = [
    'yewtu.be',
    'invidious.projectsegfau.lt',
    'invidious.nerd.gq',
    'vid.puffyan.us',
    'invidious.flokinet.to',
    'invidious.privacydev.net'
];

$audio_url_encontrado = '';

// 2. OBTENCIÓN DEL FLUJO DE AUDIO:
// Iterar sobre las instancias disponibles hasta encontrar una activa que responda con la información del video
foreach ($instancias_invidious as $instancia) {
    // URL de la API de Invidious. Usamos local=true para que la instancia actúe como proxy y evite problemas de firma de IP de Google
    $api_url = "https://{$instancia}/api/v1/videos/" . urlencode($video_id) . "?local=true";
    
    // Configuración del contexto de la petición HTTP (timeouts cortos para no ralentizar al usuario si una instancia está caída)
    $opciones_contexto = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
            'timeout' => 3 // 3 segundos de tiempo de espera por instancia
        ]
    ];
    
    $contexto = stream_context_create($opciones_contexto);
    
    try {
        $respuesta = @file_get_contents($api_url, false, $contexto);
        if ($respuesta !== false) {
            $datos_video = json_decode($respuesta, true);
            
            // Validar que la respuesta tenga formatos adaptables (donde se encuentran los flujos de solo audio)
            if (isset($datos_video['adaptiveFormats']) && is_array($datos_video['adaptiveFormats'])) {
                foreach ($datos_video['adaptiveFormats'] as $formato) {
                    // Buscar un formato que sea de solo audio (usualmente mimeType comienza con "audio/")
                    if (isset($formato['type']) && strpos($formato['type'], 'audio/') === 0) {
                        if (isset($formato['url'])) {
                            $audio_url_encontrado = $formato['url'];
                            break 2; // Salir de ambos bucles
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Ignorar errores y proceder a la siguiente instancia
        continue;
    }
}

// 3. RETORNO O REDIRECCIÓN AL FLUJO DE AUDIO:
if (!empty($audio_url_encontrado)) {
    // Si el enlace de audio es relativo a la instancia, anteponer el host de la instancia correspondiente
    if (strpos($audio_url_encontrado, '/') === 0) {
        $audio_url_encontrado = "https://{$instancia}" . $audio_url_encontrado;
    }

    // Redireccionar al usuario al flujo de audio directo. El navegador seguirá la redirección y reproducirá el audio en el reproductor HTML5
    header("Location: " . $audio_url_encontrado);
    exit;
} else {
    // Si fallan todas las instancias, retornar un estado de error 503 (Servicio no disponible)
    http_response_code(503);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'No fue posible extraer el audio en este momento. Todas las instancias de Invidious fallaron o no retornaron formatos de audio.'
    ]);
    exit;
}
