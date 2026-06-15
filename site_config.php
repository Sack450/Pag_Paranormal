<?php
/**
 * site_config.php
 * Carga la configuración del sitio desde la BD.
 * Se incluye en TODAS las páginas públicas.
 */
require_once __DIR__ . '/conexion.php';

// Valores por defecto (fallback si la BD falla)
$cfg = [
    'site_title'       => 'Investigador Oxlack',
    'site_tagline'     => 'Investigando lo inexplicable desde 2015',
    'site_description' => 'Portal oficial de investigación paranormal',
    'hero_titulo'      => 'INVESTIGADOR OXLACK',
    'hero_subtitulo'   => '"No busco fantasmas. Busco la verdad que nadie quiere encontrar."',
    'hero_tag'         => 'Investigador Paranormal',
    'hero_btn_explorar'=> 'Explorar Contenido',
    'hero_btn_contactar'=> 'Contactar',
    'footer_texto'     => 'Investigando lo inexplicable desde 2015...',
    'footer_copyright' => '© 2025 Investigador Oxlack Paranormal',
    'logo_texto'       => 'Oxlack',
    'color_primario'   => '#8b0000',
    'color_acento'     => '#ff0000',
    'color_fondo'      => '#e8e4dc',
    'color_nav'        => '#0a0a0a',
    'logo_imagen'      => 'imagenes/logos/logo oxlack png.png',
    'logo_mostrar_texto' => '0',
    'favicon'          => '',
    'hero_imagen'      => '',
    'config_version'   => '1',
    'redes_youtube'    => '',
    'redes_instagram'  => '',
    'redes_facebook'   => '',
    'redes_spotify'    => '',
    'redes_tiktok'     => '',
    'redes_whatsapp'   => '',
    'redes_twitter'    => '',
    'paypal_link'      => '',
    'patreon_link'     => '',
    'amazon_link'      => '',
    'sec_blog_label'   => 'Artículos y Análisis',
    'sec_noticias_label'=> 'Actualidad',
    'sec_podcast_label'=> 'Escucha lo Inexplicable',
    'sec_exploraciones_label' => 'Lugares Malditos',
    'sec_galeria_label'=> 'Momentos Capturados',
    'sec_tienda_label' => 'Productos Oficiales',
    'sec_donaciones_label' => 'Apoya la Investigación',
    'donacion_paypal_texto' => 'Realiza una donación directa de cualquier monto. Tu apoyo nos ayuda a continuar investigando lo inexplicable.',
    'donacion_patreon_texto' => 'Únete como miembro y obtén acceso exclusivo a contenido especial, videos anticipados y más beneficios.',
    'donacion_amazon_texto' => 'Compra equipo de investigación directamente de nuestra lista de deseos.',
    'sobremi_label'    => 'Conoce al Investigador',
    'sobremi_nombre'   => 'Investigador Oxlack',
    'sobremi_parrafo1' => 'Desde 2015 me dedico a investigar fenómenos paranormales en México y Latinoamérica.',
    'sobremi_parrafo2' => 'He investigado más de 200 casos, desde casas embrujadas hasta cementerios abandonados.',
    'sobremi_stat1_num'=> '200+',
    'sobremi_stat1_label' => 'Casos',
    'sobremi_stat2_num'=> '50+',
    'sobremi_stat2_label' => 'Videos',
    'sobremi_stat3_num'=> '100K+',
    'sobremi_stat3_label' => 'Seguidores',
    'sobremi_foto'     => '',
    'contacto_label'   => 'Conecta Conmigo',
    'contacto_titulo'  => 'REDES SOCIALES',
    'contacto_youtube_texto' => 'Suscríbete para ver todas las investigaciones',
    'contacto_instagram_texto' => 'Fotos exclusivas detrás de cámaras',
    'contacto_facebook_texto' => 'Únete a la comunidad paranormal',
    'contacto_tiktok_texto' => 'Videos cortos de fenómenos',
    'contacto_twitter_texto' => 'Noticias y actualizaciones',
    'contacto_whatsapp_texto' => 'Contacto directo para casos',
    'evidencias_titulo'=> 'ENVÍA TU EVIDENCIA',
    'evidencias_intro' => '¿Captaste algo inexplicable? Comparte tu evidencia con nosotros y la revisaremos.',
];

if (isset($conexion) && $conexion !== null) {
    // Asegurar la configuración de logos e imágenes en base de datos
    $conexion->query("INSERT INTO configuracion_sitio (clave, valor) VALUES ('logo_mostrar_texto', '0') ON DUPLICATE KEY UPDATE valor='0'");
    $conexion->query("INSERT INTO configuracion_sitio (clave, valor) VALUES ('logo_imagen', 'imagenes/logos/logo oxlack png.png') ON DUPLICATE KEY UPDATE valor='imagenes/logos/logo oxlack png.png'");
    $conexion->query("INSERT INTO configuracion_sitio (clave, valor) VALUES ('logo_texto', 'Oxlack') ON DUPLICATE KEY UPDATE valor='Oxlack'");

    $res = $conexion->query("SELECT clave, valor FROM configuracion_sitio");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cfg[$row['clave']] = $row['valor'];
        }
    }
}

function cfg(string $key): string {
    global $cfg;
    return htmlspecialchars($cfg[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

function cfgRaw(string $key): string {
    global $cfg;
    return $cfg[$key] ?? '';
}
?>
