<?php
require_once 'site_config.php';
$page_title = 'Contacto';
$page_desc  = 'Redes sociales y formas de contacto del Investigador Oxlack.';

$redes = [
    ['key' => 'youtube',   'icon' => 'fab fa-youtube',   'nombre' => 'YouTube',   'url' => cfgRaw('redes_youtube'),   'texto' => cfgRaw('contacto_youtube_texto'),   'class' => 'youtube'],
    ['key' => 'instagram', 'icon' => 'fab fa-instagram', 'nombre' => 'Instagram', 'url' => cfgRaw('redes_instagram'), 'texto' => cfgRaw('contacto_instagram_texto'), 'class' => 'instagram'],
    ['key' => 'facebook',  'icon' => 'fab fa-facebook',  'nombre' => 'Facebook',  'url' => cfgRaw('redes_facebook'),  'texto' => cfgRaw('contacto_facebook_texto'),  'class' => 'facebook'],
    ['key' => 'tiktok',    'icon' => 'fab fa-tiktok',    'nombre' => 'TikTok',    'url' => cfgRaw('redes_tiktok'),    'texto' => cfgRaw('contacto_tiktok_texto'),    'class' => 'tiktok'],
    ['key' => 'twitter',   'icon' => 'fab fa-x-twitter', 'nombre' => 'Twitter/X', 'url' => cfgRaw('redes_twitter'),   'texto' => cfgRaw('contacto_twitter_texto'),   'class' => 'twitter'],
    ['key' => 'whatsapp',  'icon' => 'fab fa-whatsapp',  'nombre' => 'WhatsApp',  'url' => cfgRaw('redes_whatsapp'),  'texto' => cfgRaw('contacto_whatsapp_texto'),  'class' => 'whatsapp'],
];

include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('contacto_label'); ?></span>
      <h2 class="section-title"><?php echo cfg('contacto_titulo'); ?></h2>
      <div class="section-line"><i class="fas fa-share-alt"></i></div>
    </div>

    <div class="redes-grid">
      <?php foreach ($redes as $r): ?>
        <?php if ($r['url']): ?>
        <a href="<?php echo htmlspecialchars($r['url']); ?>" class="red-card <?php echo $r['class']; ?>" target="_blank" rel="noopener">
          <i class="<?php echo $r['icon']; ?>"></i>
          <h3><?php echo htmlspecialchars($r['nombre']); ?></h3>
          <p><?php echo htmlspecialchars($r['texto']); ?></p>
        </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <?php
    $activas = array_filter($redes, fn($r) => !empty($r['url']));
    if (empty($activas)):
    ?>
      <div class="empty-state"><i class="fas fa-share-alt"></i><p>Configura tus redes sociales desde el panel de administración.</p></div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:3rem;">
      <a href="evidencias.php" class="btn-primary"><i class="fas fa-envelope-open-text"></i> Enviar Evidencia Paranormal</a>
    </div>
  </div>
</section>

<?php include '_footer.php'; ?>
