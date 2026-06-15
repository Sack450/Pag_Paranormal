<?php
require_once 'site_config.php';
$page_title = 'Sobre Mí';
$page_desc  = 'Conoce al Investigador Oxlack.';

$foto = cfgRaw('sobremi_foto');
include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sobremi_label'); ?></span>
      <h2 class="section-title">SOBRE <span class="rojo">MÍ</span></h2>
      <div class="section-line"><i class="fas fa-user"></i></div>
    </div>

    <div class="sobremi-content">
      <div class="sobremi-img">
        <?php if ($foto): ?>
          <img src="<?php echo htmlspecialchars($foto); ?>" alt="<?php echo cfg('sobremi_nombre'); ?>" loading="lazy">
        <?php else: ?>
          <div class="inv-img-placeholder" style="aspect-ratio:1;border-radius:8px;"><i class="fas fa-user-secret" style="font-size:4rem;"></i></div>
        <?php endif; ?>
      </div>
      <div class="sobremi-texto">
        <h3><?php echo cfg('sobremi_nombre'); ?></h3>
        <p><?php echo cfg('sobremi_parrafo1'); ?></p>
        <p><?php echo cfg('sobremi_parrafo2'); ?></p>
        <div class="stats">
          <div class="stat"><span class="num"><?php echo cfg('sobremi_stat1_num'); ?></span><span class="label"><?php echo cfg('sobremi_stat1_label'); ?></span></div>
          <div class="stat"><span class="num"><?php echo cfg('sobremi_stat2_num'); ?></span><span class="label"><?php echo cfg('sobremi_stat2_label'); ?></span></div>
          <div class="stat"><span class="num"><?php echo cfg('sobremi_stat3_num'); ?></span><span class="label"><?php echo cfg('sobremi_stat3_label'); ?></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '_footer.php'; ?>
