<?php
/**
 * API pública de apariencia — usada por la vista previa en tiempo real del admin.
 * Solo expone datos visuales (sin datos sensibles).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/_theme.php';

$keys = [
    'site_title', 'logo_texto', 'logo_imagen', 'logo_mostrar_texto',
    'favicon', 'hero_imagen', 'hero_titulo', 'hero_subtitulo', 'hero_tag',
    'hero_btn_explorar', 'hero_btn_contactar',
    'color_primario', 'color_acento', 'color_fondo', 'color_nav',
    'config_version'
];

$out = [];
foreach ($keys as $k) {
    $out[$k] = cfgRaw($k);
}

$cp = theme_hex_normalize($out['color_primario'] ?: '#8b0000');
$out['color_primario'] = $cp;
$out['color_acento']   = theme_hex_normalize($out['color_acento'] ?: '#ff0000');
$out['color_fondo']    = theme_hex_normalize($out['color_fondo'] ?: '#e8e4dc');
$out['color_nav']      = theme_hex_normalize($out['color_nav'] ?: '#0a0a0a');
$out['rojo_oscuro']    = theme_adjust_brightness($cp, -35);
$out['rojo_claro']     = theme_adjust_brightness($cp, 25);

echo json_encode(['success' => true, 'data' => $out], JSON_UNESCAPED_UNICODE);
