<?php
/**
 * _header.php
 * Cabecera reutilizable con datos dinámicos del sitio.
 */
if (!isset($page_title))  $page_title = cfg('site_title');
if (!isset($page_desc))   $page_desc  = cfg('site_description');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?php echo $page_desc; ?>">
<title><?php echo htmlspecialchars($page_title); ?> | <?php echo cfg('site_title'); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Creepster&family=Cinzel+Decorative:wght@400;700;900&family=Cinzel:wght@400;600;900&family=IM+Fell+English:ital@0;1&family=Special+Elite&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="styles.css">
<?php require __DIR__ . '/_theme.php'; ?>
</head>
<body>

<nav>
  <a href="index.php" class="btn-secondary nav-back"><i class="fas fa-arrow-left"></i> Inicio</a>
  <?php render_logo('nav-logo', 'index.php'); ?>
  <div class="nav-social-mini">
    <?php if(cfgRaw('redes_youtube'))   echo '<a href="'.htmlspecialchars(cfgRaw('redes_youtube')).'"   target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>'; ?>
    <?php if(cfgRaw('redes_instagram')) echo '<a href="'.htmlspecialchars(cfgRaw('redes_instagram')).'" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>'; ?>
    <?php if(cfgRaw('redes_tiktok'))    echo '<a href="'.htmlspecialchars(cfgRaw('redes_tiktok')).'"    target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a>'; ?>
  </div>
</nav>
