<?php
/**
 * _theme.php — Colores, favicon y variables CSS dinámicas desde el admin.
 * Requiere site_config.php cargado previamente.
 */

function theme_hex_normalize(string $hex): string {
    $hex = trim($hex);
    if ($hex === '') return '#8b0000';
    if ($hex[0] !== '#') $hex = '#' . $hex;
    if (strlen($hex) === 4) {
        $hex = '#' . $hex[1].$hex[1].$hex[2].$hex[2].$hex[3].$hex[3];
    }
    return (preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) ? strtolower($hex) : '#8b0000';
}

function theme_adjust_brightness(string $hex, int $percent): string {
    $hex = theme_hex_normalize($hex);
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    $r = max(0, min(255, $r + (int)round(255 * ($percent / 100))));
    $g = max(0, min(255, $g + (int)round(255 * ($percent / 100))));
    $b = max(0, min(255, $b + (int)round(255 * ($percent / 100))));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function theme_hex_rgba(string $hex, float $alpha): string {
    $hex = theme_hex_normalize($hex);
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    return "rgba($r,$g,$b,$alpha)";
}

function theme_cache_ver(): string {
    return preg_replace('/[^0-9]/', '', cfgRaw('config_version') ?: '1');
}

function theme_asset_url(string $path): string {
    if ($path === '') return '';
    $sep = (strpos($path, '?') !== false) ? '&' : '?';
    return htmlspecialchars($path . $sep . 'v=' . theme_cache_ver(), ENT_QUOTES, 'UTF-8');
}

function render_logo(string $class = 'nav-logo', string $href = 'index.php'): void {
    $img = cfgRaw('logo_imagen');
    $txt = cfg('logo_texto');
    echo '<a class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
    if ($img !== '') {
        echo '<img src="' . theme_asset_url($img) . '" alt="' . $txt . '" class="nav-logo-img">';
        if (cfgRaw('logo_mostrar_texto') === '1') {
            echo '<span class="nav-logo-text">' . $txt . '</span>';
        }
    } else {
        echo $txt;
    }
    echo '</a>';
}

$color_p     = theme_hex_normalize(cfgRaw('color_primario') ?: '#8b0000');
$color_a     = theme_hex_normalize(cfgRaw('color_acento') ?: '#ff0000');
$color_fondo = theme_hex_normalize(cfgRaw('color_fondo') ?: '#e8e4dc');
$color_nav   = theme_hex_normalize(cfgRaw('color_nav') ?: '#0a0a0a');
$rojo_oscuro = theme_adjust_brightness($color_p, -35);
$rojo_claro  = theme_adjust_brightness($color_p, 25);
$sombra      = theme_hex_rgba($color_p, 0.35);
$ver         = theme_cache_ver();
?>
<style id="site-theme">
  :root {
    --rojo: <?php echo $color_p; ?>;
    --rojo-brillante: <?php echo $color_a; ?>;
    --neon-rojo: <?php echo $color_a; ?>;
    --rojo-oscuro: <?php echo $rojo_oscuro; ?>;
    --rojo-claro: <?php echo $rojo_claro; ?>;
    --fondo-principal: <?php echo $color_fondo; ?>;
    --negro: <?php echo $color_nav; ?>;
    --sombra-roja: <?php echo $sombra; ?>;
  }
</style>
<?php if (cfgRaw('favicon')): ?>
<link rel="icon" type="image/png" href="<?php echo theme_asset_url(cfgRaw('favicon')); ?>">
<?php endif; ?>
